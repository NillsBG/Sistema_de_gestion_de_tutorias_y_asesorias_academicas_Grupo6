<?php
// api/conexion.php - Conexión PDO Inteligente (Prueba varias claves automáticamente)
$host    = '127.0.0.1';
$db      = 'tutoria_umg';
$user    = 'root';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = null;
$clavesAProbar = ['1234', '', 'root', 'admin'];
$ultimoError = '';

foreach ($clavesAProbar as $pass) {
    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, $options);
        break; // Conexión exitosa
    } catch (\PDOException $e) {
        $ultimoError = $e->getMessage();
    }
}

if (!$pdo) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'exito' => false, 
        'mensaje' => 'Error al conectar a la base de datos tutoria_umg: ' . $ultimoError
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
