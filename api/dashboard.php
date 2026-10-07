<?php
// api/dashboard.php - Indicadores y Métricas del Sistema
header('Content-Type: application/json; charset=utf-8');

try {
    if (file_exists('conexion.php')) {
        require_once 'conexion.php';
    } elseif (file_exists('../api/conexion.php')) {
        require_once '../api/conexion.php';
    } else {
        require_once '../conexion.php';
    }

    $sql = "
        SELECT 
            COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN estadoSolicitud = 'Aprobada' THEN 1 ELSE 0 END), 0) AS aprobadas,
            COALESCE(SUM(CASE WHEN estadoSolicitud = 'Pendiente' THEN 1 ELSE 0 END), 0) AS pendientes,
            COALESCE(SUM(CASE WHEN estadoSolicitud = 'Rechazada' THEN 1 ELSE 0 END), 0) AS rechazadas,
            COALESCE(SUM(CASE WHEN estadoSolicitud = 'Cancelada' THEN 1 ELSE 0 END), 0) AS canceladas
        FROM tblSolicitudes
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    $total      = (int)($datos['total'] ?? 0);
    $aprobadas  = (int)($datos['aprobadas'] ?? 0);
    $pendientes = (int)($datos['pendientes'] ?? 0);
    $rechazadas = (int)($datos['rechazadas'] ?? 0);

    $atendidas    = $aprobadas + $rechazadas;
    $tasaAtencion = ($total > 0) ? round(($atendidas / $total) * 100, 1) : 0;

    echo json_encode([
        'exito' => true,
        'metricas' => [
            'total_solicitudes' => $total,
            'aprobadas'         => $aprobadas,
            'pendientes'        => $pendientes,
            'rechazadas'        => $rechazadas,
            'tasa_atencion'     => $tasaAtencion . '%'
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
