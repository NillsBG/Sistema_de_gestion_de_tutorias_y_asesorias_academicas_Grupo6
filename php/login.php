<?php
// login.php - Nills Berducido Gómez (Líder de Integración & QA)
session_start();
header('Content-Type: application/json');
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$correo = trim($input['correo'] ?? '');
$clave  = trim($input['clave'] ?? '');

if (empty($correo) || empty($clave)) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'mensaje' => 'Por favor, ingresa tu correo y contraseña']);
    exit;
}

try {
    // Consulta preparada para evitar inyecciones SQL
    $stmt = $pdo->prepare("SELECT id, nombre, correo, clave, rol FROM usuarios WHERE correo = :correo LIMIT 1");
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($clave, $usuario['clave'])) {
        // Variables de sesión seguras
        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_correo'] = $usuario['correo'];
        $_SESSION['usuario_rol']    = $usuario['rol'];

        echo json_encode([
            'exito' => true,
            'mensaje' => 'Inicio de sesión exitoso',
            'usuario' => [
                'id'     => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'correo' => $usuario['correo'],
                'rol'    => $usuario['rol']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['exito' => false, 'mensaje' => 'Credenciales incorrectas']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error en el servidor']);
}
?>
