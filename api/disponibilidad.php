<?php
// api/disponibilidad.php - Consulta y publicación de tutorías
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    try {
        $stmt = $pdo->prepare("
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
            WHERE d.estadoDisponibilidad = 'disponible'
            ORDER BY d.fecha ASC, d.horaInicio ASC
        ");
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'exito' => true,
            'datos' => $datos
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al obtener disponibilidades: ' . $e->getMessage()]);
    }
} elseif ($metodo === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $idTutor = (int)($input['idTutor'] ?? $input['tutor_id'] ?? 0);
        $materia = trim($input['nombreMateria'] ?? $input['materia'] ?? '');
        $fecha   = trim($input['fecha'] ?? '');
        $hInicio = trim($input['horaInicio'] ?? $input['hora_inicio'] ?? '');
        $hFin    = trim($input['horaFin'] ?? $input['hora_fin'] ?? '');

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

        echo json_encode([
            'exito' => true,
            'mensaje' => 'Horario de tutoría publicado exitosamente'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al guardar disponibilidad: ' . $e->getMessage()]);
    }
}
?>
