<?php
// api/conexion.php - Conexión Segura PDO a MySQL
$host = '127.0.0.1';
$db   = 'tutoria_umg';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$port = '3306';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$db;charset=$charset",
    $user,
    $pass,
    $options);
} catch (PDOException $e) {
    error_log('Error de conexión PDO: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'No fue posible conectar con la base de datos.']);
    exit;
}
?>
