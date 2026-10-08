<?php
// api/perfiles.php - CRUD para Perfiles de Usuario (Estudiantes y Tutores)
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    try {
        $idUsuario = (int)($_GET['idUsuario'] ?? 0);
        if ($idUsuario > 0) {
            $stmt = $pdo->prepare("
                SELECT u.idUsuario, u.username, u.estado, r.nombreRol, 
                       e.noCarnetCompleto, e.nombreEstudiante, t.idTutor, t.nombreTutor
                FROM tblUsuarios u
                LEFT JOIN tblAsignacionRoles ar ON u.idUsuario = ar.idUsuario
                LEFT JOIN tblRoles r ON ar.idRol = r.idRol
                LEFT JOIN tblEstudiantes e ON u.idUsuario = e.idUsuario
                LEFT JOIN tblTutores t ON u.idUsuario = t.idUsuario
                WHERE u.idUsuario = ?
            ");
            $stmt->execute([$idUsuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['exito' => true, 'datos' => $usuario], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['exito' => false, 'mensaje' => 'ID de usuario requerido']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
    }
} elseif ($metodo === 'PUT') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $idUsuario = (int)($input['idUsuario'] ?? 0);
        $nuevoNombre = trim($input['nombre'] ?? '');
        $nuevaClave = trim($input['contrasena'] ?? '');

        if ($idUsuario <= 0) {
            http_response_code(400);
            echo json_encode(['exito' => false, 'mensaje' => 'ID de usuario inválido']);
            exit;
        }

        // Actualizar contraseña si se proporciona
        if (!empty($nuevaClave)) {
            $hashClave = password_hash($nuevaClave, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE tblUsuarios SET contrasena = ? WHERE idUsuario = ?");
            $stmt->execute([$hashClave, $idUsuario]);
        }

        // Actualizar nombre según tabla dependiente
        if (!empty($nuevoNombre)) {
            $stmtTutor = $pdo->prepare("UPDATE tblTutores SET nombreTutor = ? WHERE idUsuario = ?");
            $stmtTutor->execute([$nuevoNombre, $idUsuario]);

            $stmtEst = $pdo->prepare("UPDATE tblEstudiantes SET nombreEstudiante = ? WHERE idUsuario = ?");
            $stmtEst->execute([$nuevoNombre, $idUsuario]);
        }

        echo json_encode(['exito' => true, 'mensaje' => 'Perfil actualizado exitosamente'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al actualizar perfil: ' . $e->getMessage()]);
    }
}
?>