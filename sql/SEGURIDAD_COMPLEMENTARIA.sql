-- Ejecutar UNA VEZ en la base de datos existente tutoria_umg.
-- No elimina datos ni modifica otras tablas.
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

CREATE TABLE IF NOT EXISTS tblAuditoriaSeguridad (
    idAuditoria BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    idUsuario INT NULL,
    evento VARCHAR(60) NOT NULL,
    entidad VARCHAR(60) NOT NULL,
    idRegistro VARCHAR(100) NULL,
    ipHash CHAR(64) NOT NULL,
    ocurridoEn TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idxAuditoriaFecha (ocurridoEn),
    INDEX idxAuditoriaUsuario (idUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
