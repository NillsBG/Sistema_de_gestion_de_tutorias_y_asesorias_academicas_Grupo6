<?php
// api/disponibilidad.php - Publicación de tutorías con varios días seleccionados.
// Los días se guardan en los campos fechaFin y diasSeleccionados de la BD original.

date_default_timezone_set('America/Guatemala');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

$pdo->exec("SET time_zone = '-06:00'");

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function nombresDias() {
    return [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
}

function diasNumericos($dias) {
    if (!is_array($dias)) return [];

    $numeros = [];
    foreach ($dias as $dia) {
        $numero = filter_var($dia, FILTER_VALIDATE_INT);
        if ($numero !== false && $numero >= 1 && $numero <= 7) {
            $numeros[$numero] = $numero;
        }
    }
    sort($numeros, SORT_NUMERIC);
    return array_values($numeros);
}

function diasGuardadosANumeros($cadena, $fecha) {
    $mapa = nombresDias();
    $numeros = [];
    $textos = array_map('trim', explode(',', (string)$cadena));

    foreach ($textos as $texto) {
        foreach ($mapa as $numero => $nombre) {
            if (strtolower($texto) === strtolower($nombre)) {
                $numeros[] = $numero;
                break;
            }
        }
    }

    // Registros antiguos: antes solo se guardaba la fecha inicial.
    if (!$numeros && $fecha) {
        $fechaObj = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if ($fechaObj) $numeros[] = (int)$fechaObj->format('N');
    }
    return diasNumericos($numeros);
}

function nombreDiasSeleccionados($dias) {
    $mapa = nombresDias();
    return implode(', ', array_map(function ($numero) use ($mapa) {
        return $mapa[$numero];
    }, $dias));
}

function fechaValida($fecha) {
    $obj = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$fecha);
    return $obj && $obj->format('Y-m-d') === $fecha ? $obj : null;
}

function rangoValido($fecha, $fechaFin, $dias) {
    $inicio = fechaValida($fecha);
    $fin = fechaValida($fechaFin);

    if (!$inicio || !$fin || !$dias || $fin < $inicio) return false;
    if (!in_array((int)$inicio->format('N'), $dias, true) ||
        !in_array((int)$fin->format('N'), $dias, true)) return false;
    // Limitar una publicación a 90 días para evitar rangos accidentales.
    if ((int)$inicio->diff($fin)->format('%a') > 90) return false;

    $diasEncontrados = [];
    for ($dia = $inicio; $dia <= $fin; $dia = $dia->modify('+1 day')) {
        $numero = (int)$dia->format('N');
        if (in_array($numero, $dias, true)) {
            $diasEncontrados[$numero] = true;
        }
    }
    return count($diasEncontrados) === count($dias);
}

function calcularHoras($fecha, $horaInicio, $duracion) {
    if ($duracion < 15 || $duracion > 240) return null;
    $inicio = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $fecha . ' ' . substr($horaInicio, 0, 5));
    if (!$inicio || $inicio->format('Y-m-d H:i') !== $fecha . ' ' . substr($horaInicio, 0, 5)) {
        return null;
    }
    $fin = $inicio->modify('+' . $duracion . ' minutes');
    if ($fin->format('Y-m-d') !== $inicio->format('Y-m-d')) return null;
    return [$inicio->format('H:i:s'), $fin->format('H:i:s')];
}

// Verifica traslapes reales de fechas, días de semana y horas.
function hayCruceTutor($pdo, $idTutor, $fecha, $fechaFin, $dias, $hInicio, $hFin, $excluirId = 0) {
    $stmt = $pdo->prepare("
        SELECT fecha, COALESCE(fechaFin, fecha) AS fechaFinal,
               diasSeleccionados
        FROM tblDisponibilidad
        WHERE idTutor = ?
          AND idDisponibilidad <> ?
          AND fecha <= ?
          AND COALESCE(fechaFin, fecha) >= ?
          AND horaInicio < ?
          AND horaFin > ?
    ");
    $stmt->execute([$idTutor, $excluirId, $fechaFin, $fecha, $hFin, $hInicio]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $existente) {
        $diasExistentes = diasGuardadosANumeros($existente['diasSeleccionados'], $existente['fecha']);
        $inicioCom = max($fecha, $existente['fecha']);
        $finCom = min($fechaFin, $existente['fechaFinal']);
        $inicioObj = fechaValida($inicioCom);
        $finObj = fechaValida($finCom);

        for ($dia = $inicioObj; $dia && $dia <= $finObj; $dia = $dia->modify('+1 day')) {
            $numero = (int)$dia->format('N');
            if (in_array($numero, $dias, true) && in_array($numero, $diasExistentes, true)) {
                return true;
            }
        }
    }
    return false;
}

$rolSesion = strtolower($_SESSION['rol'] ?? '');
$idTutorSesion = (int)($_SESSION['idTutor'] ?? 0);
$esAdmin = in_array($rolSesion, ['administrador', 'admin'], true);
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    try {
        $codigoCarrera = trim($_GET['codigoCarrera'] ?? '');
        $idTutor = (int)($_GET['idTutor'] ?? 0);

        $sql = "
            SELECT DISTINCT
                d.idDisponibilidad,
                d.idTutor,
                t.nombreTutor,
                d.idCurso,
                d.nombreMateria,
                d.fecha,
                COALESCE(d.fechaFin, d.fecha) AS fechaFin,
                d.diasSeleccionados,
                d.horaInicio,
                d.horaFin,
                d.duracionMinutos,
                d.cupoMaximo,
                d.estadoDisponibilidad,
                c.codigoCarrera,
                c.nombreCarrera,
                (SELECT COUNT(*) FROM tblSolicitudes s
                 WHERE s.idDisponibilidad = d.idDisponibilidad
                   AND s.estadoSolicitud IN ('Pendiente', 'Aprobada')) AS cuposOcupados
            FROM tblDisponibilidad d
            INNER JOIN tblTutores t ON d.idTutor = t.idTutor
            INNER JOIN tblCursos cur ON d.idCurso = cur.idCurso
            INNER JOIN tblCarreras c ON cur.idCarrera = c.idCarrera
            WHERE d.estadoDisponibilidad = 'disponible'
              AND TIMESTAMP(d.fecha, d.horaInicio) > NOW()
        ";

        $params = [];
        if ($codigoCarrera !== '') {
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
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados as &$fila) {
            // Respetar los días publicados; solo inferir el día en registros antiguos.
            if (trim((string)$fila['diasSeleccionados']) === '') {
                $fila['diasSeleccionados'] = nombreDiasSeleccionados(
                    diasGuardadosANumeros(null, $fila['fecha'])
                );
            }
            $fila['diasSemana'] = $fila['diasSeleccionados'];
        }
        unset($fila);

        responder(['exito' => true, 'datos' => $resultados]);
    } catch (Exception $e) {
        responder(['exito' => false, 'mensaje' => 'Error al obtener disponibilidades: ' . $e->getMessage()], 500);
    }
}

if ($metodo === 'POST' || $metodo === 'PUT') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $esEditar = ($metodo === 'PUT');
        $idDisp = (int)($input['idDisponibilidad'] ?? 0);
        $idTutor = $esAdmin ? (int)($input['idTutor'] ?? 0) : $idTutorSesion;
        $idCurso = (int)($input['idCurso'] ?? 0);
        $fecha = trim($input['fecha'] ?? '');
        $fechaFin = trim($input['fechaFin'] ?? $fecha);
        $horaInicio = trim($input['horaInicio'] ?? '');
        $duracion = (int)($input['duracionMinutos'] ?? 45);
        $dias = diasNumericos($input['diasSemana'] ?? []);

        if ($esEditar) {
            $consulta = $pdo->prepare("SELECT idTutor, idCurso, fecha, fechaFin, diasSeleccionados FROM tblDisponibilidad WHERE idDisponibilidad = ?");
            $consulta->execute([$idDisp]);
            $actual = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$actual) responder(['exito' => false, 'mensaje' => 'La tutoría no existe.'], 404);
            if (!$esAdmin && (int)$actual['idTutor'] !== $idTutorSesion) {
                responder(['exito' => false, 'mensaje' => 'No puedes modificar tutorías de otro tutor.'], 403);
            }
            $idTutor = (int)$actual['idTutor'];
            $idCurso = (int)$actual['idCurso'];
            if (!array_key_exists('fechaFin', $input)) {
                $fechaFin = $actual['fechaFin'] ?: $fecha;
            }
            if (!array_key_exists('diasSemana', $input)) {
                $dias = diasGuardadosANumeros($actual['diasSeleccionados'], $actual['fecha']);
            }
        }

        if ($idTutor <= 0 || $idCurso <= 0 || !rangoValido($fecha, $fechaFin, $dias) || $horaInicio === '') {
            responder(['exito' => false, 'mensaje' => 'Verifica la fecha inicial, final y los días seleccionados.'], 400);
        }

        $horas = calcularHoras($fecha, $horaInicio, $duracion);
        if (!$horas) {
            responder(['exito' => false, 'mensaje' => 'La hora de inicio o la duración no son válidas.'], 400);
        }
        if (strtotime($fecha . ' ' . $horas[0]) <= time()) {
            responder(['exito' => false, 'mensaje' => 'La fecha y hora de inicio deben ser futuras.'], 400);
        }

        if (hayCruceTutor($pdo, $idTutor, $fecha, $fechaFin, $dias, $horas[0], $horas[1], $esEditar ? $idDisp : 0)) {
            responder(['exito' => false, 'mensaje' => 'Ya tienes una tutoría programada que se cruza con los días y horarios seleccionados.'], 400);
        }

        $diasTexto = nombreDiasSeleccionados($dias);
        if ($esEditar) {
            $stmt = $pdo->prepare("
                UPDATE tblDisponibilidad
                SET fecha = ?, fechaFin = ?, diasSeleccionados = ?,
                    horaInicio = ?, horaFin = ?, duracionMinutos = ?
                WHERE idDisponibilidad = ?
            ");
            $stmt->execute([$fecha, $fechaFin, $diasTexto, $horas[0], $horas[1], $duracion, $idDisp]);
            responder(['exito' => true, 'mensaje' => 'Tutoría modificada exitosamente.']);
        }

        $stmtCurso = $pdo->prepare("SELECT nombreCurso FROM tblCursos WHERE idCurso = ?");
        $stmtCurso->execute([$idCurso]);
        $curso = $stmtCurso->fetch(PDO::FETCH_ASSOC);
        if (!$curso) responder(['exito' => false, 'mensaje' => 'El curso seleccionado no existe.'], 400);

        $stmt = $pdo->prepare("
            INSERT INTO tblDisponibilidad
                (idTutor, idCurso, nombreMateria, fecha, fechaFin, diasSeleccionados,
                 horaInicio, horaFin, duracionMinutos, cupoMaximo, estadoDisponibilidad)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 30, 'disponible')
        ");
        $stmt->execute([
            $idTutor, $idCurso, $curso['nombreCurso'], $fecha, $fechaFin,
            $diasTexto, $horas[0], $horas[1], $duracion
        ]);
        responder(['exito' => true, 'mensaje' => 'Se publicó la tutoría con todos los días seleccionados.']);
    } catch (Exception $e) {
        responder(['exito' => false, 'mensaje' => 'Error al guardar tutoría: ' . $e->getMessage()], 500);
    }
}

if ($metodo === 'DELETE') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $idDisp = (int)($input['idDisponibilidad'] ?? 0);
        $forzar = !empty($input['forzar']);

        if ($idDisp <= 0) {
            responder(['exito' => false, 'mensaje' => 'Tutoría no válida.'], 400);
        }
        $stmt = $pdo->prepare("SELECT idTutor FROM tblDisponibilidad WHERE idDisponibilidad = ?");
        $stmt->execute([$idDisp]);
        $disp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$disp) {
            responder(['exito' => false, 'mensaje' => 'La tutoría no existe.'], 404);
        }
        if (!$esAdmin && (int)$disp['idTutor'] !== $idTutorSesion) {
            responder(['exito' => false, 'mensaje' => 'No puedes eliminar tutorías de otro tutor.'], 403);
        }

        $stmtN = $pdo->prepare("SELECT COUNT(*) FROM tblSolicitudes WHERE idDisponibilidad = ? AND estadoSolicitud IN ('Pendiente','Aprobada')");
        $stmtN->execute([$idDisp]);
        $inscritos = (int)$stmtN->fetchColumn();

        if ($inscritos > 0 && !$forzar) {
            responder([
                'exito' => false,
                'requiereConfirmacion' => true,
                'inscriptos' => $inscritos,
                'mensaje' => "Esta tutoría tiene $inscritos estudiante(s) con solicitud activa."
            ]);
        }

        $stmtD = $pdo->prepare("DELETE FROM tblDisponibilidad WHERE idDisponibilidad = ?");
        $stmtD->execute([$idDisp]);
        responder(['exito' => true, 'mensaje' => 'Tutoría eliminada exitosamente.']);
    } catch (Exception $e) {
        responder(['exito' => false, 'mensaje' => 'Error al eliminar tutoría: ' . $e->getMessage()], 500);
    }
}
?>
