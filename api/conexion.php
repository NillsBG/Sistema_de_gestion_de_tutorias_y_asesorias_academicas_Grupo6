<?php
// api/conexion.php - Conexión Segura PDO a MySQL
$host = '127.0.0.1';
$db   = 'tutoria_umg';
$user = 'root';
$pass = '1234';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    exit;
}
?>
