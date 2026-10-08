<?php
// api/disponibilidad.php - CRUD completo para la disponibilidad de tutorías
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}

$metodo = $_SERVER['REQUEST_METHOD'];

// --- [READ] Obtener disponibilidades ---
if ($metodo === 'GET') {
    try {
        $idTutor = (int)($_GET['idTutor'] ?? 0);
        
        $sql = "
            SELECT 
                d.idDisponibilidad,
                d.idTutor,
                t.nombreTutor,
                d.nombreMateria,
                d.fecha,
                d.horaInicio,
                d.horaFin,
                d.estadoDisponibilidad
            FROM tblDisponibilidad d
            INNER JOIN tblTutores t ON d.idTutor = t.idTutor
            WHERE 1=1
        ";
        
        $params = [];
        if ($idTutor > 0) {
            $sql .= " AND d.idTutor = :idTutor";
            $params[':idTutor'] = $idTutor;
        } else {
            $sql .= " AND d.estadoDisponibilidad = 'disponible'";
        }
        
        $sql .= " ORDER BY d.fecha ASC, d.horaInicio ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['exito' => true, 'datos' => $datos], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al obtener disponibilidades: ' . $e->getMessage()]);
    }
} 
// --- [CREATE] Registrar nueva disponibilidad ---
elseif ($metodo === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $idTutor = (int)($input['idTutor'] ?? 0);
        $materia = trim($input['nombreMateria'] ?? '');
        $fecha   = trim($input['fecha'] ?? '');
        $hInicio = trim($input['horaInicio'] ?? '');
        $hFin    = trim($input['horaFin'] ?? '');

        if ($idTutor <= 0 || empty($materia) || empty($fecha) || empty($hInicio) || empty($hFin)) {
            http_response_code(400);
            echo json_encode(['exito' => false, 'mensaje' => 'Todos los campos son obligatorios']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO tblDisponibilidad (idTutor, nombreMateria, fecha, horaInicio, horaFin, estadoDisponibilidad)
            VALUES (:idTutor, :materia, :fecha, :hInicio, :hFin, 'disponible')
        ");
        $stmt->execute([
            ':idTutor' => $idTutor,
            ':materia' => $materia,
            ':fecha'   => $fecha,
            ':hInicio' => $hInicio,
            ':hFin'    => $hFin
        ]);

        echo json_encode(['exito' => true, 'mensaje' => 'Horario de tutoría publicado exitosamente'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al guardar disponibilidad: ' . $e->getMessage()]);
    }
} 
// --- [UPDATE] Modificar disponibilidad existente ---
elseif ($metodo === 'PUT') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $idDisp  = (int)($input['idDisponibilidad'] ?? 0);
        $materia = trim($input['nombreMateria'] ?? '');
        $fecha   = trim($input['fecha'] ?? '');
        $hInicio = trim($input['horaInicio'] ?? '');
        $hFin    = trim($input['horaFin'] ?? '');
        $estado  = trim($input['estadoDisponibilidad'] ?? 'disponible');

        if ($idDisp <= 0 || empty($materia) || empty($fecha) || empty($hInicio) || empty($hFin)) {
            http_response_code(400);
            echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos para actualizar']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE tblDisponibilidad 
            SET nombreMateria = :materia, fecha = :fecha, horaInicio = :hInicio, horaFin = :hFin, estadoDisponibilidad = :estado
            WHERE idDisponibilidad = :idDisp
        ");
        $stmt->execute([
            ':materia' => $materia,
            ':fecha'   => $fecha,
            ':hInicio' => $hInicio,
            ':hFin'    => $hFin,
            ':estado'  => $estado,
            ':idDisp'  => $idDisp
        ]);

        echo json_encode(['exito' => true, 'mensaje' => 'Horario actualizado correctamente'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al actualizar disponibilidad: ' . $e->getMessage()]);
    }
} 
// --- [DELETE] Eliminar disponibilidad ---
elseif ($metodo === 'DELETE') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $idDisp = (int)($input['idDisponibilidad'] ?? $_GET['idDisponibilidad'] ?? 0);

        if ($idDisp <= 0) {
            http_response_code(400);
            echo json_encode(['exito' => false, 'mensaje' => 'ID de disponibilidad no válido']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM tblDisponibilidad WHERE idDisponibilidad = :idDisp");
        $stmt->execute([':idDisp' => $idDisp]);

        echo json_encode(['exito' => true, 'mensaje' => 'Horario eliminado correctamente'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al eliminar disponibilidad: ' . $e->getMessage()]);
    }
}
?>