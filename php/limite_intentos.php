<?php
/**
 * Límite de intentos de inicio de sesión.
 * Por usuario: cada 5 intentos fallidos -> bloqueo FIJO de 5 minutos.
 * Por IP: 30 intentos fallidos dentro de 15 minutos -> bloqueo de 5 minutos.
 * Compatible con tblIntentosLogin tanto con como sin columna nivelBloqueo.
 */

function clavesDeLogin(string $usuario): array
{
    $usuario = mb_strtolower(trim($usuario), 'UTF-8');
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'desconocida');
    return [
        ['clave' => hash('sha256', 'usuario:' . $usuario), 'tipo' => 'usuario', 'maximo' => 5],
        ['clave' => hash('sha256', 'ip:' . $ip), 'tipo' => 'ip', 'maximo' => 30],
    ];
}

function segundosBloqueoLogin(PDO $pdo, string $usuario): int
{
    $sql = 'SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), bloqueadoHasta)) '
         . 'FROM tblIntentosLogin WHERE claveLimite = ? AND bloqueadoHasta > UTC_TIMESTAMP()';
    $stmt = $pdo->prepare($sql);
    $segundos = 0;
    foreach (clavesDeLogin($usuario) as $limite) {
        $stmt->execute([$limite['clave']]);
        $valor = $stmt->fetchColumn();
        if ($valor !== false) $segundos = max($segundos, (int)$valor);
    }
    return $segundos;
}

function loginBloqueado(PDO $pdo, string $usuario): bool
{
    return segundosBloqueoLogin($pdo, $usuario) > 0;
}

function registrarFalloLogin(PDO $pdo, string $usuario): int
{
    $limites = clavesDeLogin($usuario);
    usort($limites, static fn($a, $b) => strcmp($a['clave'], $b['clave']));

    $pdo->beginTransaction();
    try {
        $insertar = $pdo->prepare(
            'INSERT IGNORE INTO tblIntentosLogin (claveLimite, intentos, ventanaInicio) '
          . 'VALUES (?, 0, UTC_TIMESTAMP())'
        );
        $seleccionar = $pdo->prepare(
            'SELECT intentos, ventanaInicio, bloqueadoHasta '
          . 'FROM tblIntentosLogin WHERE claveLimite = ? FOR UPDATE'
        );
        $actualizar = $pdo->prepare(
            'UPDATE tblIntentosLogin SET intentos = ?, ventanaInicio = ?, bloqueadoHasta = ? '
          . 'WHERE claveLimite = ?'
        );
        $ahora = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $ahoraSQL = $ahora->format('Y-m-d H:i:s');
        $bloqueoMaximo = 0;

        foreach ($limites as $limite) {
            $insertar->execute([$limite['clave']]);
            $seleccionar->execute([$limite['clave']]);
            $fila = $seleccionar->fetch(PDO::FETCH_ASSOC);

            if ($fila['bloqueadoHasta'] !== null && $fila['bloqueadoHasta'] > $ahoraSQL) {
                $hasta = new DateTimeImmutable($fila['bloqueadoHasta'], new DateTimeZone('UTC'));
                $bloqueoMaximo = max($bloqueoMaximo, $hasta->getTimestamp() - $ahora->getTimestamp());
                continue;
            }

            $intentos = (int)$fila['intentos'];
            $inicio = new DateTimeImmutable($fila['ventanaInicio'], new DateTimeZone('UTC'));
            if ($limite['tipo'] === 'ip' && $inicio <= $ahora->modify('-15 minutes')) {
                $intentos = 0;
                $inicio = $ahora;
            }

            $intentos++;
            $bloqueadoHasta = null;
            if ($intentos >= $limite['maximo']) {
                $bloqueadoHasta = $ahora->modify('+5 minutes');
                $bloqueoMaximo = max($bloqueoMaximo, 300);
                $intentos = 0;
                $inicio = $ahora;
            }

            $actualizar->execute([
                $intentos,
                $inicio->format('Y-m-d H:i:s'),
                $bloqueadoHasta ? $bloqueadoHasta->format('Y-m-d H:i:s') : null,
                $limite['clave'],
            ]);
        }
        $pdo->commit();
        return $bloqueoMaximo;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function limpiarIntentosUsuario(PDO $pdo, string $usuario): void
{
    $claveUsuario = clavesDeLogin($usuario)[0]['clave'];
    $stmt = $pdo->prepare('DELETE FROM tblIntentosLogin WHERE claveLimite = ?');
    $stmt->execute([$claveUsuario]);
}
