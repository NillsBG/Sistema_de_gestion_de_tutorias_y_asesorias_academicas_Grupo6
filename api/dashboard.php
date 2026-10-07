<?php
// api/dashboard.php - Nills Berducido Gómez
header('Content-Type: application/json');
require_once '../conexion.php';

try {
    $totalSolicitudes = $pdo->query("SELECT COUNT(*) FROM tblSolicitudes")->fetchColumn();
    $aprobadas        = $pdo->query("SELECT COUNT(*) FROM tblSolicitudes WHERE estadoSolicitud = 'Aprobada'")->fetchColumn();
    $pendientes       = $pdo->query("SELECT COUNT(*) FROM tblSolicitudes WHERE estadoSolicitud = 'Pendiente'")->fetchColumn();
    $rechazadas       = $pdo->query("SELECT COUNT(*) FROM tblSolicitudes WHERE estadoSolicitud = 'Rechazada'")->fetchColumn();

    $tasaAtencion = $totalSolicitudes > 0 
        ? round((($aprobadas + $rechazadas) / $totalSolicitudes) * 100, 1) 
        : 0;

    echo json_encode([
        'exito' => true,
        'metricas' => [
            'total_solicitudes' => (int)$totalSolicitudes,
            'aprobadas'         => (int)$aprobadas,
            'pendientes'        => (int)$pendientes,
            'rechazadas'        => (int)$rechazadas,
            'tasa_atencion'     => $tasaAtencion . '%'
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error al calcular indicadores']);
}
?>


