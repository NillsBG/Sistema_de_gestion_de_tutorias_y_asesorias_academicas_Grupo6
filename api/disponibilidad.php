<?php
// api/disponibilidad.php - CRUD de Horarios: publicar (varios días/semana), modificar y eliminar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// Hay cruce si otro horario del mismo tutor, en la misma fecha, se traslapa
function hayCruceTutor($pdo, $idTutor, $fecha, $hInicio, $hFin, $excluirId = 0) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tblDisponibilidad
        WHERE idTutor = ? AND fecha = ?
          AND horaInicio < ? AND horaFin > ?
          AND idDisponibilidad <> ?
    ");
    $stmt->execute([$idTutor, $fecha, $hFin, $hInicio, $excluirId]);
    return (int)$stmt->fetchColumn() > 0;
}

// Devuelve [horaInicio, horaFin] o null si la duración cruza la medianoche
function calcularHoras($fecha, $horaInicio, $duracion) {
    $ini = strtotime("$fecha $horaInicio");
    if ($ini === false) return null;
    $fin = $ini + ($duracion * 60);
    if (date('Y-m-d', $fin) !== date('Y-m-d', $ini)) return null;
    return [date('H:i:s', $ini), date('H:i:s', $fin)];
}

$rolSesion     = strtolower($_SESSION['rol'] ?? '');
$idTutorSesion = (int)($_SESSION['idTutor'] ?? 0);
$esAdmin       = in_array($rolSesion, ['administrador', 'admin']);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    try {
        $codigoCarrera = trim($_GET['codigoCarrera'] ?? '');
        $idTutor       = (int)($_GET['idTutor'] ?? 0);

        $sql = "
            SELECT DISTINCT
                d.idDisponibilidad,
                d.idTutor,
                t.nombreTutor,
                d.idCurso,
                d.nombreMateria,
                d.fecha,
                d.horaInicio,
                d.horaFin,
                d.duracionMinutos,
                d.cupoMaximo,
                d.estadoDisponibilidad,
                c.codigoCarrera,
                c.nombreCarrera,
                (SELECT COUNT(*) FROM tblSolicitudes s WHERE s.idDisponibilidad = d.idDisponibilidad AND s.estadoSolicitud IN ('Pendiente', 'Aprobada')) AS cuposOcupados
            FROM tblDisponibilidad d
            INNER JOIN tblTutores t ON d.idTutor = t.idTutor
            INNER JOIN tblCursos cur ON d.idCurso = cur.idCurso
            INNER JOIN tblCarreras c ON cur.idCarrera = c.idCarrera
            WHERE d.estadoDisponibilidad = 'disponible'
              AND CONCAT(d.fecha, ' ', d.horaFin) >= NOW()
        ";

        $params = [];

        if (!empty($codigoCarrera)) {
            $sql .= " AND c.codigoCarrera = :codCarrera";
            $params[':codCarrera'] = $codigoCarrera;
        }

        if ($idTutor > 0) {
            $sql .= " AND d.idTutor = :idTutor";
            $params[':idTutor'] = $idTutor;
        }

        $sql .= " ORDER BY d.fecha ASC, d.horaInicio ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        responder(['exito' => true, 'datos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        responder(['exito' => false, 'mensaje' => 'Error al obtener disponibilidades: ' . $e->getMessage()], 500);
    }

} elseif ($metodo === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        // El tutor siempre publica a su propio nombre (no se confía en el idTutor del cliente)
        $idTutor    = $esAdmin ? (int)($input['idTutor'] ?? 0) : $idTutorSesion;
        $idCurso    = (int)($input['idCurso'] ?? 0);
        $fecha      = trim($input['fecha'] ?? '');          // fecha de inicio
        $horaInicio = trim($input['horaInicio'] ?? '');
        $duracion   = (int)($input['duracionMinutos'] ?? 45);
        $semanas    = max(1, min(16, (int)($input['semanas'] ?? 1)));
        $diasSemana = array_values(array_unique(array_map('intval', (array)($input['diasSemana'] ?? []))));
        $diasSemana = array_filter($diasSemana, fn($d) => $d >= 1 && $d <= 7); // 1=Lunes ... 7=Domingo

        if ($idTutor <= 0 || $idCurso <= 0 || empty($fecha) || empty($horaInicio)) {
            responder(['exito' => false, 'mensaje' => 'Todos los campos son obligatorios.'], 400);
        }
        if ($duracion < 15 || $duracion > 240) {
            responder(['exito' => false, 'mensaje' => 'La duración debe estar entre 15 y 240 minutos.'], 400);
        }

        // El curso debe estar autorizado por el administrador para este tutor
        $stmtA = $pdo->prepare("SELECT COUNT(*) FROM tblTutorCursos WHERE idTutor = ? AND idCurso = ?");
        $stmtA->execute([$idTutor, $idCurso]);
        if ((int)$stmtA->fetchColumn() === 0) {
            responder(['exito' => false, 'mensaje' => 'Ese curso no está asignado a este tutor.'], 403);
        }

        $stmtC = $pdo->prepare("SELECT nombreCurso FROM tblCursos WHERE idCurso = ?");
        $stmtC->execute([$idCurso]);
        $cursoObj = $stmtC->fetch();
        if (!$cursoObj) {
            responder(['exito' => false, 'mensaje' => 'El curso seleccionado no existe.'], 400);
        }
        $nombreMateria = $cursoObj['nombreCurso'];

        // Construir la lista de fechas
        $fechas = [];
        if (empty($diasSemana)) {
            $fechas[] = $fecha; // compatibilidad: una sola fecha
        } else {
            $inicio = new DateTime($fecha);
            for ($i = 0; $i < $semanas * 7; $i++) {
                $d = (clone $inicio)->modify("+$i days");
                if (in_array((int)$d->format('N'), $diasSemana)) {
                    $fechas[] = $d->format('Y-m-d');
                }
            }
        }

        $stmtIns = $pdo->prepare("
            INSERT INTO tblDisponibilidad (idTutor, idCurso, nombreMateria, fecha, horaInicio, horaFin, duracionMinutos, cupoMaximo, estadoDisponibilidad)
            VALUES (:idTutor, :idCurso, :materia, :fecha, :hInicio, :hFin, :duracion, 30, 'disponible')
        ");

        $creadas = 0;
        $omitidas = 0;
        $ahora = time();

        $pdo->beginTransaction();
        foreach ($fechas as $f) {
            $horas = calcularHoras($f, $horaInicio, $duracion);
            if (!$horas || strtotime("$f {$horas[0]}") < $ahora || hayCruceTutor($pdo, $idTutor, $f, $horas[0], $horas[1])) {
                $omitidas++;
                continue;
            }
            $stmtIns->execute([
                ':idTutor'  => $idTutor,
                ':idCurso'  => $idCurso,
                ':materia'  => $nombreMateria,
                ':fecha'    => $f,
                ':hInicio'  => $horas[0],
                ':hFin'     => $horas[1],
                ':duracion' => $duracion
            ]);
            $creadas++;
        }
        $pdo->commit();

        if ($creadas === 0) {
            responder(['exito' => false, 'mensaje' => 'No se creó ninguna tutoría: las fechas ya pasaron o se cruzan con otras tuyas.'], 400);
        }

        $msg = "Se publicaron $creadas tutoría(s) exitosamente.";
        if ($omitidas > 0) $msg .= " Se omitieron $omitidas por fecha pasada o cruce de horario.";
        responder(['exito' => true, 'mensaje' => $msg, 'creadas' => $creadas, 'omitidas' => $omitidas]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        responder(['exito' => false, 'mensaje' => 'Error al guardar disponibilidad: ' . $e->getMessage()], 500);
    }

} elseif ($metodo === 'PUT') {
    // MODIFICAR una tutoría publicada (fecha, hora de inicio y duración)
    try {
        $input      = json_decode(file_get_contents('php://input'), true) ?? [];
        $idDisp     = (int)($input['idDisponibilidad'] ?? 0);
        $fecha      = trim($input['fecha'] ?? '');
        $horaInicio = trim($input['horaInicio'] ?? '');
        $duracion   = (int)($input['duracionMinutos'] ?? 0);

        if ($idDisp <= 0 || empty($fecha) || empty($horaInicio) || $duracion < 15 || $duracion > 240) {
            responder(['exito' => false, 'mensaje' => 'Datos inválidos para modificar la tutoría.'], 400);
        }

        $stmt = $pdo->prepare("SELECT idTutor FROM tblDisponibilidad WHERE idDisponibilidad = ?");
        $stmt->execute([$idDisp]);
        $disp = $stmt->fetch();
        if (!$disp) {
            responder(['exito' => false, 'mensaje' => 'La tutoría no existe.'], 404);
        }
        if (!$esAdmin && (int)$disp['idTutor'] !== $idTutorSesion) {
            responder(['exito' => false, 'mensaje' => 'No puedes modificar tutorías de otro tutor.'], 403);
        }

        $horas = calcularHoras($fecha, $horaInicio, $duracion);
        if (!$horas) {
            responder(['exito' => false, 'mensaje' => 'La hora o la duración no son válidas (no puede pasar de medianoche).'], 400);
        }
        if (strtotime("$fecha {$horas[0]}") < time()) {
            responder(['exito' => false, 'mensaje' => 'No puedes programar una tutoría en una fecha u hora pasada.'], 400);
        }
        if (hayCruceTutor($pdo, (int)$disp['idTutor'], $fecha, $horas[0], $horas[1], $idDisp)) {
            responder(['exito' => false, 'mensaje' => 'Ese horario se cruza con otra de tus tutorías.'], 400);
        }

        $stmtU = $pdo->prepare("
            UPDATE tblDisponibilidad
            SET fecha = ?, horaInicio = ?, horaFin = ?, duracionMinutos = ?
            WHERE idDisponibilidad = ?
        ");
        $stmtU->execute([$fecha, $horas[0], $horas[1], $duracion, $idDisp]);

        responder(['exito' => true, 'mensaje' => 'Tutoría modificada exitosamente.']);
    } catch (Exception $e) {
        responder(['exito' => false, 'mensaje' => 'Error al modificar tutoría: ' . $e->getMessage()], 500);
    }

} elseif ($metodo === 'DELETE') {
    // ELIMINAR una tutoría publicada
    try {
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $idDisp = (int)($input['idDisponibilidad'] ?? 0);
        $forzar = !empty($input['forzar']);

        if ($idDisp <= 0) {
            responder(['exito' => false, 'mensaje' => 'Tutoría no válida.'], 400);
        }

        $stmt = $pdo->prepare("SELECT idTutor FROM tblDisponibilidad WHERE idDisponibilidad = ?");
        $stmt->execute([$idDisp]);
        $disp = $stmt->fetch();
        if (!$disp) {
            responder(['exito' => false, 'mensaje' => 'La tutoría no existe.'], 404);
        }
        if (!$esAdmin && (int)$disp['idTutor'] !== $idTutorSesion) {
            responder(['exito' => false, 'mensaje' => 'No puedes eliminar tutorías de otro tutor.'], 403);
        }

        // Si ya hay estudiantes inscritos, pedir confirmación extra
        $stmtN = $pdo->prepare("SELECT COUNT(*) FROM tblSolicitudes WHERE idDisponibilidad = ? AND estadoSolicitud IN ('Pendiente','Aprobada')");
        $stmtN->execute([$idDisp]);
        $inscriptos = (int)$stmtN->fetchColumn();

        if ($inscriptos > 0 && !$forzar) {
            responder([
                'exito' => false,
                'requiereConfirmacion' => true,
                'inscriptos' => $inscriptos,
                'mensaje' => "Esta tutoría tiene $inscriptos estudiante(s) con solicitud activa."
            ]);
        }

        // Las solicitudes se eliminan por ON DELETE CASCADE
        $stmtD = $pdo->prepare("DELETE FROM tblDisponibilidad WHERE idDisponibilidad = ?");
        $stmtD->execute([$idDisp]);

        responder(['exito' => true, 'mensaje' => 'Tutoría eliminada exitosamente.']);
    } catch (Exception $e) {
        responder(['exito' => false, 'mensaje' => 'Error al eliminar tutoría: ' . $e->getMessage()], 500);
    }
}
?>