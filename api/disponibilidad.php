<?php
// api/disponibilidad.php - CRUD de Horarios: publicar (varios días/semana), modificar y eliminar

// Usar la fecha y hora locales de Guatemala para crear y modificar tutorías.
date_default_timezone_set('America/Guatemala');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}


// Unificar las comparaciones SQL con la hora local de Guatemala.
// PHP usa America/Guatemala y MySQL debe usar el mismo horario.
$pdo->exec("SET time_zone = '-06:00'");

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
                ELT(WEEKDAY(d.fecha) + 1,
                    'Lunes', 'Martes', 'Miércoles', 'Jueves',
                    'Viernes', 'Sábado', 'Domingo') AS diasSemana,
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
              AND TIMESTAMP(d.fecha, d.horaInicio) > NOW()
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

} 

 elseif ($metodo === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $idTutor    = $esAdmin ? (int)($input['idTutor'] ?? 0) : $idTutorSesion;
        $idCurso    = (int)($input['idCurso'] ?? 0);
        $fecha      = trim($input['fecha'] ?? '');          // Fecha de inicio
        $fechaFin   = trim($input['fechaFin'] ?? '');       // Fecha de finalización
        $horaInicio = trim($input['horaInicio'] ?? '');
        $duracion   = (int)($input['duracionMinutos'] ?? 45);
        $diasSemana = array_values(array_unique(array_map('intval', (array)($input['diasSemana'] ?? []))));
        $diasSemana = array_filter($diasSemana, fn($d) => $d >= 1 && $d <= 7);

        if ($idTutor <= 0 || $idCurso <= 0 || empty($fecha) || empty($horaInicio)) {
            responder(['exito' => false, 'mensaje' => 'Todos los campos son obligatorios.'], 400);
        }

        $stmtC = $pdo->prepare("SELECT nombreCurso FROM tblCursos WHERE idCurso = ?");
        $stmtC->execute([$idCurso]);
        $cursoObj = $stmtC->fetch();
        if (!$cursoObj) {
            responder(['exito' => false, 'mensaje' => 'El curso seleccionado no existe.'], 400);
        }
        $nombreMateria = $cursoObj['nombreCurso'];

        // Calcular horaFin
        $horas = calcularHoras($fecha, $horaInicio, $duracion);
        if (!$horas) {
            responder(['exito' => false, 'mensaje' => 'La hora de inicio o la duración no son válidas.'], 400);
        }

        // Validar cruces de tutoría en esa fecha/hora
        if (hayCruceTutor($pdo, $idTutor, $fecha, $horas[0], $horas[1])) {
            responder(['exito' => false, 'mensaje' => 'Ya tienes una tutoría programada que se cruza con este horario.'], 400);
        }

        // INSERTAR UNA SOLA TUTORÍA (1 SOLO REGISTRO)
        $stmtIns = $pdo->prepare("
            INSERT INTO tblDisponibilidad (idTutor, idCurso, nombreMateria, fecha, horaInicio, horaFin, duracionMinutos, cupoMaximo, estadoDisponibilidad)
            VALUES (:idTutor, :idCurso, :materia, :fecha, :hInicio, :hFin, :duracion, 30, 'disponible')
        ");

        $stmtIns->execute([
            ':idTutor'  => $idTutor,
            ':idCurso'  => $idCurso,
            ':materia'  => $nombreMateria,
            ':fecha'    => $fecha,
            ':hInicio'  => $horas[0],
            ':hFin'     => $horas[1],
            ':duracion' => $duracion
        ]);

        responder(['exito' => true, 'mensaje' => 'Se publicó el espacio de tutoría exitosamente.']);
    } catch (Exception $e) {
        responder(['exito' => false, 'mensaje' => 'Error al guardar disponibilidad: ' . $e->getMessage()], 500);
    }
}

elseif ($metodo === 'PUT') {
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