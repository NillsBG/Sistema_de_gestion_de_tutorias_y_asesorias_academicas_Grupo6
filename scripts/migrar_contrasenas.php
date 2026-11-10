<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo CLI.'); }
require_once __DIR__ . '/../api/conexion.php';
try {
    $pdo->beginTransaction();
    $filas = $pdo->query('SELECT idUsuario, contrasena FROM tblUsuarios FOR UPDATE')->fetchAll(PDO::FETCH_ASSOC);
    $migradas = 0;
    foreach ($filas as $fila) {
        $actual = (string)$fila['contrasena'];
        // No volver a hashear cuentas que ya tienen un hash de password_hash().
        if (password_get_info($actual)['algoName'] !== 'unknown') { continue; }
        $hash = password_hash($actual, PASSWORD_DEFAULT);
        $sentencia = $pdo->prepare('UPDATE tblUsuarios SET contrasena = ? WHERE idUsuario = ?');
        $sentencia->execute([$hash, (int)$fila['idUsuario']]);
        $migradas++;
    }
    $pdo->commit();
    echo "Migración completada. Usuarios convertidos: {$migradas}.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Migración de contraseñas: ' . $e->getMessage());
    fwrite(STDERR, "No se pudo completar la migración. No se guardaron cambios parciales.\n");
    exit(1);
}
