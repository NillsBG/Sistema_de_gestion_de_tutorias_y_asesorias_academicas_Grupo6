<?php
// api/validar_reserva.php - Nills Berducido Gómez (Lógica de Negocio)
header('Content-Type: application/json');
require_once 'conexion.php';

function verificarCrucesYDuplicados($pdo, $noCarnetEstudiante, $idDisponibilidad) {
    // 1. Validar solicitud duplicada en tblSolicitudes
    $stmtDuplicado = $pdo->prepare("
        SELECT idSolicitud 
        FROM tblSolicitudes 
        WHERE noCarnetEstudiante = :noCarnet 
          AND idDisponibilidad = :idDisp 
          AND estadoSolicitud IN ('Pendiente', 'Aprobada')
    ");
    $stmtDuplicado->execute([
        ':noCarnet' => $noCarnetEstudiante,
        ':idDisp'   => $idDisponibilidad
    ]);

    if ($stmtDuplicado->fetch()) {
        return ['valido' => false, 'mensaje' => 'Ya tienes una solicitud activa para esta tutoría.'];
    }

    // 2. Obtener datos del nuevo horario en tblDisponibilidad
    $stmtSlot = $pdo->prepare("SELECT fecha, horaInicio, horaFin FROM tblDisponibilidad WHERE idDisponibilidad = :idDisp");
    $stmtSlot->execute([':idDisp' => $idDisponibilidad]);
    $slotNuevo = $stmtSlot->fetch();

    if (!$slotNuevo) {
        return ['valido' => false, 'mensaje' => 'El horario seleccionado no existe.'];
    }

    // 3. Validar traslape/cruce de horario con otra tutoría activa del estudiante
    $stmtTraslape = $pdo->prepare("
        SELECT s.idSolicitud 
        FROM tblSolicitudes s
        JOIN tblDisponibilidad d ON s.idDisponibilidad = d.idDisponibilidad
        WHERE s.noCarnetEstudiante = :noCarnet
          AND s.estadoSolicitud IN ('Pendiente', 'Aprobada')
          AND d.fecha = :fecha
          AND (
              (d.horaInicio < :horaFin AND d.horaFin > :horaInicio)
          )
    ");
    $stmtTraslape->execute([
        ':noCarnet'   => $noCarnetEstudiante,
        ':fecha'      => $slotNuevo['fecha'],
        ':horaInicio' => $slotNuevo['horaInicio'],
        ':horaFin'    => $slotNuevo['horaFin']
    ]);

    if ($stmtTraslape->fetch()) {
        return ['valido' => false, 'mensaje' => 'Error: Ya tienes otra tutoría programada que se cruza en este horario.'];
    }

    return ['valido' => true];
}
?>