<?php
// api/solicitudes.php - Registro y actualización de solicitudes
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    try {
        $idTutor  = (int)($_GET['idTutor'] ?? $_GET['tutor_id'] ?? 0);
        $noCarnet = trim($_GET['noCarnetEstudiante'] ?? $_GET['estudiante_id'] ?? '');

        $sql = "
            SELECT 
                s.idSolicitud,
                s.noCarnetEstudiante,
                e.nombreEstudiante,
                d.nombreMateria,
                d.fecha,
                d.horaInicio,
                d.horaFin,
                s.estadoSolicitud,
                t.nombreTutor
            FROM tblSolicitudes s
            INNER JOIN tblDisponibilidad d ON s.idDisponibilidad = d.idDisponibilidad
            INNER JOIN tblTutores t ON d.idTutor = t.idTutor
            INNER JOIN tblEstudiantes e ON s.noCarnetEstudiante = e.noCarnetCompleto
            WHERE 1=1
        ";

        $params = [];
        if ($idTutor > 0) {
            $sql .= " AND d.idTutor = :idTutor";
            $params[':idTutor'] = $idTutor;
        } elseif (!empty($noCarnet)) {
            $sql .= " AND s.noCarnetEstudiante = :noCarnet";
            $params[':noCarnet'] = $noCarnet;
        }

        $sql .= " ORDER BY s.solicitadoEn DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['exito' => true, 'datos' => $datos], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al consultar solicitudes: ' . $e->getMessage()]);
    }
} elseif ($metodo === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $noCarnet = trim($input['noCarnetEstudiante'] ?? $input['estudiante_id'] ?? '');
        $idDisp   = (int)($input['idDisponibilidad'] ?? $input['disponibilidad_id'] ?? 0);

        if (empty($noCarnet) || $idDisp <= 0) {
            http_response_code(400);
            echo json_encode(['exito' => false, 'mensaje' => 'Faltan datos para crear la solicitud']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO tblSolicitudes (noCarnetEstudiante, idDisponibilidad, estadoSolicitud)
            VALUES (:noCarnet, :idDisp, 'Pendiente')
        ");
        $stmt->execute([
            ':noCarnet' => $noCarnet,
            ':idDisp'   => $idDisp
        ]);

        echo json_encode(['exito' => true, 'mensaje' => 'Solicitud de tutoría creada exitosamente'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al registrar la solicitud: ' . $e->getMessage()]);
    }
} elseif ($metodo === 'PUT') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $idSolicitud = (int)($input['idSolicitud'] ?? $input['solicitud_id'] ?? 0);
        $nuevoEstado = trim($input['estadoSolicitud'] ?? $input['estado'] ?? '');

        if ($idSolicitud <= 0 || empty($nuevoEstado)) {
            http_response_code(400);
            echo json_encode(['exito' => false, 'mensaje' => 'ID de solicitud o estado no válido']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE tblSolicitudes SET estadoSolicitud = :estado WHERE idSolicitud = :id");
        $stmt->execute([':estado' => $nuevoEstado, ':id' => $idSolicitud]);

        echo json_encode(['exito' => true, 'mensaje' => 'Estado actualizado a ' . $nuevoEstado], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al actualizar estado: ' . $e->getMessage()]);
    }
}
?>
