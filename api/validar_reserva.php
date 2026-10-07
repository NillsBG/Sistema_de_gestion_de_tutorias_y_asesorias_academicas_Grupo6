<?php
// Lógica de Negocio
header('Content-Type: application/json');
require_once '../conexion.php';

function verificarCrucesYDuplicados($pdo, $estudiante_id, $disponibilidad_id) {
    // 1. Validar solicitud duplicada
    $stmtDuplicado = $pdo->prepare("
        SELECT id FROM solicitudes 
        WHERE estudiante_id = :estudiante_id 
          AND disponibilidad_id = :disponibilidad_id 
          AND estado IN ('Pendiente', 'Aprobada')
    ");
    $stmtDuplicado->execute([
        ':estudiante_id' => $estudiante_id,
        ':disponibilidad_id' => $disponibilidad_id
    ]);

    if ($stmtDuplicado->fetch()) {
        return ['valido' => false, 'mensaje' => 'Ya tienes una solicitud activa para esta tutoría.'];
    }

    // 2. Obtener datos del nuevo horario
    $stmtSlot = $pdo->prepare("SELECT fecha, hora_inicio, hora_fin FROM disponibilidad WHERE id = :id");
    $slotNuevo = $stmtSlot->fetch();

    if (!$slotNuevo) {
        return ['valido' => false, 'mensaje' => 'El horario seleccionado no existe.'];
    }

    // 3. Validar traslape/cruce de horario con otra tutoría
    $stmtTraslape = $pdo->prepare("
        SELECT s.id 
        FROM solicitudes s
        JOIN disponibilidad d ON s.disponibilidad_id = d.id
        WHERE s.estudiante_id = :estudiante_id
          AND s.estado IN ('Pendiente', 'Aprobada')
          AND d.fecha = :fecha
          AND (
              (d.hora_inicio < :hora_fin AND d.hora_fin > :hora_inicio)
          )
    ");
    $stmtTraslape->execute([
        ':estudiante_id' => $estudiante_id,
        ':fecha'         => $slotNuevo['fecha'],
        ':hora_inicio'   => $slotNuevo['hora_inicio'],
        ':hora_fin'      => $slotNuevo['hora_fin']
    ]);

    if ($stmtTraslape->fetch()) {
        return ['valido' => false, 'mensaje' => 'Error: Ya tienes otra tutoría programada que se cruza con este horario.'];
    }

    return ['valido' => true];
}
?>
