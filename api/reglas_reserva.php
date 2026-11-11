<?php
/** Reglas compartidas por prevalidación y alta definitiva.
 * La inserción debe invocarse en transacción, bloqueando tblEstudiantes y tblDisponibilidad.
 */
function diaReserva(string $fecha): int { return (int)(new DateTimeImmutable($fecha))->format('N'); }
function diasReserva(?string $texto, string $fecha): array {
    $mapa = ['lunes'=>1, 'martes'=>2, 'miércoles'=>3, 'miercoles'=>3,
        'jueves'=>4, 'viernes'=>5, 'sábado'=>6, 'sabado'=>6, 'domingo'=>7];
    $dias = [];
    foreach (explode(',', (string)$texto) as $item) {
        $normalizado = strtolower(strtr(trim($item), ['Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']));
        $n = $mapa[$normalizado] ?? null;
        if ($n !== null) $dias[$n] = true;
    }
    return $dias ? array_keys($dias) : [diaReserva($fecha)];
}
function seTraslapanTutorias(array $a, array $b): bool {
    if ($a['horaInicio'] >= $b['horaFin'] || $a['horaFin'] <= $b['horaInicio']) return false;
    $desde = max($a['fecha'], $b['fecha']);
    $hasta = min($a['fechaFin'] ?: $a['fecha'], $b['fechaFin'] ?: $b['fecha']);
    if ($desde > $hasta) return false;
    $diasA = diasReserva($a['diasSeleccionados'] ?? null, $a['fecha']);
    $diasB = diasReserva($b['diasSeleccionados'] ?? null, $b['fecha']);
    $inicio = new DateTimeImmutable($desde);
    $fin = new DateTimeImmutable($hasta);
    // Cada tutoría publicada tiene un máximo de 90 días; evitar bucles sin fin.
    for ($dia = $inicio, $n = 0; $dia <= $fin && $n <= 366; $dia = $dia->modify('+1 day'), ++$n) {
        $semana = (int)$dia->format('N');
        if (in_array($semana, $diasA, true) && in_array($semana, $diasB, true)) return true;
    }
    return false;
}
function evaluarReserva(PDO $pdo, string $carnet, int $idDisp): array {
    if ($carnet === '' || $idDisp <= 0) return [false, 'Datos incompletos para reservar.'];
    $stmt = $pdo->prepare('SELECT idDisponibilidad, fecha, COALESCE(fechaFin, fecha) AS fechaFin,
        diasSeleccionados, horaInicio, horaFin, cupoMaximo, estadoDisponibilidad,
        TIMESTAMP(fecha, horaInicio) > NOW() AS vigente
        FROM tblDisponibilidad WHERE idDisponibilidad = ?');
    $stmt->execute([$idDisp]);
    $solicitada = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$solicitada) return [false, 'La tutoría no existe.'];
    if ($solicitada['estadoDisponibilidad'] !== 'disponible' || !(int)$solicitada['vigente'])
        return [false, 'La tutoría ya comenzó o no está disponible.'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblSolicitudes
        WHERE idDisponibilidad = ? AND estadoSolicitud IN ('Pendiente', 'Aprobada')");
    $stmt->execute([$idDisp]);
    if ((int)$stmt->fetchColumn() >= min(30, max(1, (int)$solicitada['cupoMaximo'])))
        return [false, 'El cupo de la tutoría está lleno.'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblSolicitudes
        WHERE noCarnetEstudiante = ? AND idDisponibilidad = ?
        AND estadoSolicitud IN ('Pendiente', 'Aprobada')");
    $stmt->execute([$carnet, $idDisp]);
    if ((int)$stmt->fetchColumn() > 0)
        return [false, 'Ya tienes una reserva activa en esta tutoría.'];
    $stmt = $pdo->prepare("SELECT d.fecha, COALESCE(d.fechaFin, d.fecha) AS fechaFin,
        d.diasSeleccionados, d.horaInicio, d.horaFin
        FROM tblSolicitudes s JOIN tblDisponibilidad d ON d.idDisponibilidad = s.idDisponibilidad
        WHERE s.noCarnetEstudiante = ? AND s.estadoSolicitud IN ('Pendiente', 'Aprobada')");
    $stmt->execute([$carnet]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $existente) {
        if (seTraslapanTutorias($solicitada, $existente))
            return [false, 'Tienes un cruce de horario con otra tutoría reservada.'];
    }
    return [true, 'Reserva válida.'];
}
