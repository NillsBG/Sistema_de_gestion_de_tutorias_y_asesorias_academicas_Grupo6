-- ACTUALIZACIÓN INCREMENTAL: no elimina usuarios ni solicitudes actuales.
-- Importar en phpMyAdmin sobre la base existente tutoria_umg.
USE tutoria_umg;

CREATE TABLE IF NOT EXISTS tblSolicitudesCuenta (
    idSolicitudCuenta BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    contrasenaHash VARCHAR(255) NULL,
    rolSolicitado ENUM('Estudiante','Tutor') NOT NULL,
    nombreCompleto VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    noCarnetCompleto VARCHAR(20) NULL,
    idCarrera INT NULL,
    tituloProfesional VARCHAR(100) NULL,
    especialidad VARCHAR(100) NULL,
    estadoSolicitud ENUM('Pendiente','Aprobada','Rechazada') NOT NULL DEFAULT 'Pendiente',
    fechaSolicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaRevision DATETIME NULL,
    idAdministradorRevisor INT NULL,
    observacionRevision VARCHAR(300) NULL,
    ipHash CHAR(64) NOT NULL,
    PRIMARY KEY (idSolicitudCuenta),
    UNIQUE KEY uqSolicitudUsername (username),
    KEY idxSolicitudEstado (estadoSolicitud,fechaSolicitud),
    CONSTRAINT fkSolicitudCuentaCarrera FOREIGN KEY (idCarrera) REFERENCES tblCarreras (idCarrera),
    CONSTRAINT fkSolicitudCuentaRevisor FOREIGN KEY (idAdministradorRevisor) REFERENCES tblUsuarios (idUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limitar las peticiones públicas de registro (5/hora por IP).
CREATE TABLE IF NOT EXISTS tblIntentosRegistro (
    idIntento BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ipHash CHAR(64) NOT NULL,
    realizadoEn TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idxRegistroIPFecha (ipHash,realizadoEn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
