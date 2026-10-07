<?php
// api/validar_reserva.php - Validación de Cruces de Horario y Duplicados
header('Content-Type: application/json; charset=utf-8');

if (file_exists('conexion.php')) {
    require_once 'conexion.php';
} elseif (file_exists('../api/conexion.php')) {
    require_once '../api/conexion.php';
} else {
    require_once '../conexion.php';
}

function verificarCrucesYDuplicados($pdo, $noCarnetEstudiante, $idDisponibilidad) {
    if (empty($noCarnetEstudiante) || empty($idDisponibilidad)) {
        return ['valido' => false, 'mensaje' => 'Faltan datos obligatorios para validar.'];
    }

    // 1. Validar si ya tiene una solicitud activa en tblSolicitudes
    $stmtDuplicado = $pdo->prepare("
        SELECT idSolicitud 
        FROM tblSolicitudes 
        WHERE noCarnetEstudiante = :noCarnet 
          AND idDisponibilidad = :idDisp 
          AND estadoSolicitud IN ('Pendiente', 'Aprobada')
        LIMIT 1
    ");
    $stmtDuplicado->execute([
        ':noCarnet' => $noCarnetEstudiante,
        ':idDisp'   => $idDisponibilidad
    ]);

    if ($stmtDuplicado->fetch(PDO::FETCH_ASSOC)) {
        return ['valido' => false, 'mensaje' => 'Ya tienes una solicitud activa para esta tutoría.'];
    }

    // 2. Obtener datos del horario solicitado en tblDisponibilidad
    $stmtSlot = $pdo->prepare("SELECT fecha, horaInicio, horaFin FROM tblDisponibilidad WHERE idDisponibilidad = :idDisp LIMIT 1");
    $stmtSlot->execute([':idDisp' => $idDisponibilidad]);
    $slotNuevo = $stmtSlot->fetch(PDO::FETCH_ASSOC);

    if (!$slotNuevo) {
        return ['valido' => false, 'mensaje' => 'El horario seleccionado no existe.'];
    }

    // 3. Validar traslape/cruce de horario con otra tutoría
    $stmtTraslape = $pdo->prepare("
        SELECT s.idSolicitud, d.nombreMateria, d.horaInicio, d.horaFin
        FROM tblSolicitudes s
        INNER JOIN tblDisponibilidad d ON s.idDisponibilidad = d.idDisponibilidad
        WHERE s.noCarnetEstudiante = :noCarnet
          AND s.estadoSolicitud IN ('Pendiente', 'Aprobada')
          AND d.fecha = :fecha
          AND d.horaInicio < :horaFinNueva 
          AND d.horaFin > :horaInicioNueva
        LIMIT 1
    ");
    $stmtTraslape->execute([
        ':noCarnet'        => $noCarnetEstudiante,
        ':fecha'           => $slotNuevo['fecha'],
        ':horaFinNueva'    => $slotNuevo['horaFin'],
        ':horaInicioNueva' => $slotNuevo['horaInicio']
    ]);

    $cruce = $stmtTraslape->fetch(PDO::FETCH_ASSOC);

    if ($cruce) {
        return [
            'valido' => false, 
            'mensaje' => 'Error de traslape: Ya tienes programada "' . $cruce['nombreMateria'] . '" (' . $cruce['horaInicio'] . ' - ' . $cruce['horaFin'] . ') en este horario.'
        ];
    }

    return ['valido' => true, 'mensaje' => 'Horario disponible sin conflictos.'];
}

// Bloque ejecutable para peticiones HTTP POST
$input = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;
$noCarnet = trim($input['noCarnetEstudiante'] ?? $input['noCarnet'] ?? '');
$idDisp   = (int)($input['idDisponibilidad'] ?? $input['idDisp'] ?? 0);

if (!empty($noCarnet) && $idDisp > 0) {
    $resultado = verificarCrucesYDuplicados($pdo, $noCarnet, $idDisp);
    if (!$resultado['valido']) http_response_code(400);
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
}
?>
