<?php
/** Bitácora sin contraseñas ni tokens; los errores no deben impedir la operación principal. */
function registrarAuditoria(PDO $pdo, string $evento, string $entidad, ?string $idRegistro = null, ?int $idUsuario = null): void {
    try {
        $idUsuario ??= isset($_SESSION['idUsuario']) ? (int)$_SESSION['idUsuario'] : null;
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'CLI');
        $stmt = $pdo->prepare('INSERT INTO tblAuditoriaSeguridad (idUsuario, evento, entidad, idRegistro, ipHash) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$idUsuario, $evento, $entidad, $idRegistro, hash('sha256', $ip)]);
    } catch (Throwable $e) {
        error_log('No se pudo registrar auditoría: ' . $e->getMessage());
    }
}
