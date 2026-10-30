<?php
// api/solicitudes.php - Gestión de Solicitudes y Reservas de Tutoría con Días y Expiración
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    try {
        $noCarnet = trim($_GET['noCarnetEstudiante'] ?? '');
        $idTutor  = (int)($_GET['idTutor'] ?? 0);
        $soloActivas = (int)($_GET['soloActivas'] ?? 0);

        $sql = "
            SELECT 
                s.idSolicitud,
                s.noCarnetEstudiante,
                s.estadoSolicitud,
                s.solicitadoEn,
                d.idDisponibilidad,
                d.nombreMateria,
                d.fecha,
                d.horaInicio,
                d.horaFin,
                d.duracionMinutos,
                d.diasSemana,
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
            $sql .= " AND CONCAT(d.fecha, ' ', d.horaFin) >= NOW()";
        }

        $sql .= " ORDER BY s.idSolicitud DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['exito' => true, 'datos' => $datos], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al obtener solicitudes: ' . $e->getMessage()]);
    }
} elseif ($metodo === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $noCarnet = trim($input['noCarnetEstudiante'] ?? '');
        $idDisp   = (int)($input['idDisponibilidad'] ?? 0);

        if (empty($noCarnet) || $idDisp <= 0) {
            http_response_code(400);
            echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO tblSolicitudes (noCarnetEstudiante, idDisponibilidad, estadoSolicitud) VALUES (?, ?, 'Pendiente')");
        $stmt->execute([$noCarnet, $idDisp]);

        echo json_encode(['exito' => true, 'mensaje' => 'Solicitud enviada correctamente.'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al guardar solicitud: ' . $e->getMessage()]);
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

        $stmt = $pdo->prepare("UPDATE tblSolicitudes SET estadoSolicitud = ? WHERE idSolicitud = ?");
        $stmt->execute([$estado, $idSol]);

        echo json_encode(['exito' => true, 'mensaje' => 'Estado de la solicitud actualizado a ' . $estado], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al actualizar solicitud: ' . $e->getMessage()]);
    }
}
?>
