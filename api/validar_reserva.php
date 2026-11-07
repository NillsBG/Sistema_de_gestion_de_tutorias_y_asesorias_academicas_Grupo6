<?php
require_once __DIR__ . '/../php/auth_middleware.php';
verificarRol(['Estudiante']);
// api/validar_reserva.php - Validación Backend de Cupos (30 Máx) y Traslapes
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}

// Comparar fechas usando la hora de Guatemala en MySQL.
$pdo->exec("SET time_zone = '-06:00'");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valido' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$noCarnet = carnetSesion(); // Nunca confiar en el carné enviado por el navegador.
$idDisp   = (int)($input['idDisponibilidad'] ?? 0);

if (empty($noCarnet) || $idDisp <= 0) {
    echo json_encode(['valido' => false, 'mensaje' => 'Datos incompletos para validar.']);
    exit;
}

try {
    // 1. Obtener datos del horario solicitado
    $stmtH = $pdo->prepare("
        SELECT fecha, horaInicio, horaFin, cupoMaximo,
               estadoDisponibilidad,
               (TIMESTAMP(fecha, horaInicio) > NOW()) AS vigente
        FROM tblDisponibilidad
        WHERE idDisponibilidad = ?
    ");
    $stmtH->execute([$idDisp]);
    $horario = $stmtH->fetch();

    if (!$horario) {
        echo json_encode(['valido' => false, 'mensaje' => 'El horario de tutoría ya no existe.']);
        exit;
    }

    if ($horario['estadoDisponibilidad'] !== 'disponible' || !(int)$horario['vigente']) {
        echo json_encode([
            'valido' => false,
            'mensaje' => 'Esta tutoría ya comenzó o no está disponible para inscribirse.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fecha   = $horario['fecha'];
    $hInicio = $horario['horaInicio'];
    $hFin    = $horario['horaFin'];
    $cupoMax = $horario['cupoMaximo'] ?? 30;

    // 2. VALIDAR CUPO MÁXIMO DE 30 ESTUDIANTES
    $stmtCupo = $pdo->prepare("
        SELECT COUNT(*) AS totalInscriptos 
        FROM tblSolicitudes 
        WHERE idDisponibilidad = ? AND estadoSolicitud IN ('Pendiente', 'Aprobada')
    ");
    $stmtCupo->execute([$idDisp]);
    $inscriptos = (int)($stmtCupo->fetch()['totalInscriptos'] ?? 0);

    if ($inscriptos >= $cupoMax) {
        echo json_encode(['valido' => false, 'mensaje' => "❌ El cupo para esta tutoría está lleno (Máximo $cupoMax estudiantes alcanzado)."]);
        exit;
    }

    // 3. VALIDAR SOLICITUD DUPLICADA
    $stmtDup = $pdo->prepare("
        SELECT COUNT(*) AS dup 
        FROM tblSolicitudes 
        WHERE noCarnetEstudiante = ? AND idDisponibilidad = ? AND estadoSolicitud IN ('Pendiente', 'Aprobada')
    ");
    $stmtDup->execute([$noCarnet, $idDisp]);
    if ((int)$stmtDup->fetch()['dup'] > 0) {
        echo json_encode(['valido' => false, 'mensaje' => 'Ya tienes una reserva activa para este mismo horario.']);
        exit;
    }

    // 4. VALIDAR TRASLAPE/CRUCE DE HORARIOS
    $stmtCru = $pdo->prepare("
        SELECT COUNT(*) AS cruces
        FROM tblSolicitudes s
        INNER JOIN tblDisponibilidad d ON s.idDisponibilidad = d.idDisponibilidad
        WHERE s.noCarnetEstudiante = ?
          AND s.estadoSolicitud IN ('Pendiente', 'Aprobada')
          AND d.fecha = ?
          AND (
                (d.horaInicio < ? AND d.horaFin > ?) OR
                (d.horaInicio < ? AND d.horaFin > ?) OR
                (? <= d.horaInicio AND ? >= d.horaFin)
              )
    ");
    $stmtCru->execute([$noCarnet, $fecha, $hFin, $hInicio, $hInicio, $hFin, $hInicio, $hFin]);

    if ((int)$stmtCru->fetch()['cruces'] > 0) {
        echo json_encode(['valido' => false, 'mensaje' => '❌ Tienes un cruce de horario con otra tutoría reservada en la misma fecha y hora.']);
        exit;
    }

    echo json_encode(['valido' => true, 'mensaje' => 'Reserva válida.']);

} catch (Exception $e) {
    echo json_encode(['valido' => false, 'mensaje' => 'No fue posible completar la operación.']);
}
?>
