<?php
// conexion.php - Conexión PDO centralizada a la base de datos tutoria_umg

$host    = '127.0.0.1';     // Servidor local (localhost)
$db      = 'tutoria_umg';   // Nombre de la base de datos
$user    = 'root';          // Usuario por defecto en XAMPP / WAMP
$pass    = '1234';              // Contraseña (en XAMPP viene vacía por defecto)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'exito' => false, 
        'mensaje' => 'Error de conexión a la base de datos tutoria_umg: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>