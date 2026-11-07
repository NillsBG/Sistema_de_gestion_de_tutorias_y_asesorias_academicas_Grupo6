-- Migración no destructiva: NO elimina bases ni tablas existentes.
USE tutoria_umg;

CREATE TABLE IF NOT EXISTS tblIntentosLogin (
    claveLimite CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    intentos INT UNSIGNED NOT NULL DEFAULT 0,
    ventanaInicio DATETIME NOT NULL,
    bloqueadoHasta DATETIME NULL,
    actualizadoEn TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (claveLimite),
    INDEX idxIntentosActualizado (actualizadoEn)
) ENGINE=InnoDB;

-- Mantenimiento opcional (ejecutar periódicamente, no al instalar):
-- DELETE FROM tblIntentosLogin WHERE actualizadoEn < NOW() - INTERVAL 30 DAY;
