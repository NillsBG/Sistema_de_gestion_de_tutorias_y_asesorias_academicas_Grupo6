<?php
/** Obtiene el perfil vigente desde MySQL. Nunca confiar en carrera/carné enviados por el navegador. */
function perfilEstudianteActual(PDO $pdo): array {
    if (empty($_SESSION['idUsuario'])) {
        errorAcceso(401, 'Debes iniciar sesión.');
    }
    $consulta = $pdo->prepare("SELECT e.idCarrera, e.noCarnetCompleto, c.codigoCarrera, c.nombreCarrera
        FROM tblEstudiantes e
        INNER JOIN tblCarreras c ON c.idCarrera = e.idCarrera
        WHERE e.idUsuario = ? LIMIT 1");
    $consulta->execute([(int)$_SESSION['idUsuario']]);
    $perfil = $consulta->fetch(PDO::FETCH_ASSOC);
    if (!$perfil) {
        errorAcceso(403, 'El perfil de estudiante no se encuentra disponible.');
    }
    // Actualizar la sesión después de un cambio de carrera realizado por el administrador.
    $_SESSION['noCarnetCompleto'] = $perfil['noCarnetCompleto'];
    $_SESSION['codigoCarrera'] = $perfil['codigoCarrera'];
    $_SESSION['nombreCarrera'] = $perfil['nombreCarrera'];
    return $perfil;
}
