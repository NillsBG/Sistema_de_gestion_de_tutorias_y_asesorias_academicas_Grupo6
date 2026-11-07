<?php
require_once __DIR__ . '/../php/auth_middleware.php';
verificarRol(['Administrador', 'Tutor', 'Estudiante']);
// api/solicitudes.php - Gestión de Solicitudes y Reservas de Tutoría con Días y Expiración
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}

// Comparar fechas usando la hora de Guatemala en MySQL.
$pdo->exec("SET time_zone = '-06:00'");

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    try {
        $noCarnet = trim($_GET['noCarnetEstudiante'] ?? '');
        $idTutor  = (int)($_GET['idTutor'] ?? 0);
        $soloActivas = (int)($_GET['soloActivas'] ?? 0);
        if (rolActual() === 'estudiante') {
            $noCarnet = carnetSesion();
            $idTutor = 0;
        } elseif (rolActual() === 'tutor') {
            $noCarnet = '';
            $idTutor = tutorSesion();
        }

        $sql = "
            SELECT 
                s.idSolicitud,
                s.noCarnetEstudiante,
                s.estadoSolicitud,
                s.solicitadoEn,
                d.idDisponibilidad,
                d.nombreMateria,
                d.fecha,
                COALESCE(d.fechaFin, d.fecha) AS fechaFin,
                d.horaInicio,
                d.horaFin,
                d.duracionMinutos,
                COALESCE(NULLIF(d.diasSeleccionados, ''),
                    ELT(WEEKDAY(d.fecha) + 1,
                        'Lunes', 'Martes', 'Miércoles', 'Jueves',
                        'Viernes', 'Sábado', 'Domingo')) AS diasSemana,
                d.cupoMaximo,
                (SELECT COUNT(*) FROM tblSolicitudes s2 WHERE s2.idDisponibilidad = d.idDisponibilidad AND s2.estadoSolicitud IN ('Pendiente', 'Aprobada')) AS cuposOcupados,
                t.nombreTutor,
                e.nombreEstudiante
            FROM tblSolicitudes s
            INNER JOIN tblDisponibilidad d ON s.idDisponibilidad = d.idDisponibilidad
            INNER JOIN tblTutores t ON d.idTutor = t.idTutor
            LEFT JOIN tblEstudiantes e ON s.noCarnetEstudiante = e.noCarnetCompleto
            WHERE 1=1
        ";

        $params = [];
        if (!empty($noCarnet)) {
            $sql .= " AND s.noCarnetEstudiante = :carnet";
            $params[':carnet'] = $noCarnet;
        }

        if ($idTutor > 0) {
            $sql .= " AND d.idTutor = :idTutor";
            $params[':idTutor'] = $idTutor;
        }

        if ($soloActivas === 1) {
            // La tutoría deja de recibir solicitudes al comenzar.
            $sql .= " AND TIMESTAMP(d.fecha, d.horaInicio) > NOW()";
        }

        $sql .= " ORDER BY s.idSolicitud DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['exito' => true, 'datos' => $datos], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'No fue posible completar la operación.']);
    }
} elseif ($metodo === 'POST') {
    verificarRol(['Estudiante']);
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $noCarnet = carnetSesion();
        $idDisp   = (int)($input['idDisponibilidad'] ?? 0);

        if (empty($noCarnet) || $idDisp <= 0) {
            http_response_code(400);
            echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos.']);
            exit;
        }

        // Evitar que se guarden nuevas solicitudes para horarios ya vencidos.
        // La validación se hace también aquí, pues validar_reserva.php se puede omitir.
        $stmt = $pdo->prepare("
            INSERT INTO tblSolicitudes (noCarnetEstudiante, idDisponibilidad, estadoSolicitud)
            SELECT ?, d.idDisponibilidad, 'Pendiente'
            FROM tblDisponibilidad d
            WHERE d.idDisponibilidad = ?
              AND d.estadoDisponibilidad = 'disponible'
              AND TIMESTAMP(d.fecha, d.horaInicio) > NOW()
        ");
        $stmt->execute([$noCarnet, $idDisp]);

        if ($stmt->rowCount() === 0) {
            http_response_code(409);
            echo json_encode([
                'exito' => false,
                'mensaje' => 'La tutoría ya comenzó o no está disponible para nuevas solicitudes.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['exito' => true, 'mensaje' => 'Solicitud enviada correctamente.'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'No fue posible completar la operación.']);
    }
} elseif ($metodo === 'PUT') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $idSol  = (int)($input['idSolicitud'] ?? 0);
        $estado = trim($input['estadoSolicitud'] ?? '');

        if ($idSol <= 0 || !in_array($estado, ['Pendiente', 'Aprobada', 'Rechazada', 'Cancelada'])) {
            http_response_code(400);
            echo json_encode(['exito' => false, 'mensaje' => 'Estado no válido.']);
            exit;
        }

        // Comprobar propiedad y permisos de la solicitud antes de actualizar.
        $propietario = $pdo->prepare("
            SELECT s.noCarnetEstudiante, s.estadoSolicitud, d.idTutor
            FROM tblSolicitudes s
            INNER JOIN tblDisponibilidad d ON d.idDisponibilidad = s.idDisponibilidad
            WHERE s.idSolicitud = ?
        ");
        $propietario->execute([$idSol]);
        $actual = $propietario->fetch(PDO::FETCH_ASSOC);
        if (!$actual) {
            http_response_code(404);
            echo json_encode(['exito' => false, 'mensaje' => 'Solicitud no encontrada.']);
            exit;
        }
        if (rolActual() === 'estudiante') {
            if ($actual['noCarnetEstudiante'] !== carnetSesion() || $estado !== 'Cancelada') {
                errorAcceso(403, 'Solo puedes cancelar tus propias solicitudes.');
            }
            if ($actual['estadoSolicitud'] !== 'Pendiente') {
                http_response_code(409);
                echo json_encode(['exito' => false, 'mensaje' => 'Solo puedes cancelar solicitudes pendientes.']);
                exit;
            }
        } elseif (rolActual() === 'tutor') {
            if ((int)$actual['idTutor'] !== tutorSesion() || !in_array($estado, ['Aprobada', 'Rechazada'], true)) {
                errorAcceso(403, 'Solo puedes atender solicitudes de tus tutorías.');
            }
            if ($actual['estadoSolicitud'] !== 'Pendiente') {
                http_response_code(409);
                echo json_encode(['exito' => false, 'mensaje' => 'La solicitud ya fue atendida.']);
                exit;
            }
        }
        $stmt = $pdo->prepare("UPDATE tblSolicitudes SET estadoSolicitud = ? WHERE idSolicitud = ?");
        $stmt->execute([$estado, $idSol]);

        echo json_encode(['exito' => true, 'mensaje' => 'Estado de la solicitud actualizado a ' . $estado], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'No fue posible completar la operación.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido.']);
}
?>
