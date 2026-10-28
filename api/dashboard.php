<?php
// api/dashboard.php - Métricas Generales del Sistema
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}

try {
    $total     = (int)$pdo->query("SELECT COUNT(*) FROM tblSolicitudes")->fetchColumn();
    $aprobadas = (int)$pdo->query("SELECT COUNT(*) FROM tblSolicitudes WHERE estadoSolicitud = 'Aprobada'")->fetchColumn();
    $pendientes= (int)$pdo->query("SELECT COUNT(*) FROM tblSolicitudes WHERE estadoSolicitud = 'Pendiente'")->fetchColumn();
    
    $tasa = $total > 0 ? round(($aprobadas / $total) * 100) . '%' : '0%';

    echo json_encode([
        'exito' => true,
        'metricas' => [
            'total_solicitudes' => $total,
            'aprobadas'         => $aprobadas,
            'pendientes'        => $pendientes,
            'tasa_atencion'     => $tasa
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
}
?>
