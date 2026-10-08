/*
    Creado por Velveth Saraí Chavez Mejía
*/
CREATE DATABASE IF NOT EXISTS tutoria_umg 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE tutoria_umg;

-- 1. Tabla de Usuarios (Maestra)
CREATE TABLE IF NOT EXISTS tblUsuarios(
    idUsuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    estado VARCHAR(50) DEFAULT 'Activo',
    creadoEn TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabla de Roles (Maestra)
CREATE TABLE IF NOT EXISTS tblRoles(
    idRol INT AUTO_INCREMENT PRIMARY KEY,
    nombreRol VARCHAR(50) NOT NULL,
    descripcionRol VARCHAR(255),
    estadoRol VARCHAR(50) DEFAULT 'Activo'
) ENGINE=InnoDB;

-- 3. Tabla AsignacionRoles (Asociativa)
CREATE TABLE IF NOT EXISTS tblAsignacionRoles(
    idUsuario INT NOT NULL,
    idRol INT NOT NULL,
    PRIMARY KEY (idUsuario, idRol),
    CONSTRAINT fkAsignacionRolesUsuario 
        FOREIGN KEY (idUsuario) REFERENCES tblUsuarios(idUsuario) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fkAsignacionRolesRol 
        FOREIGN KEY (idRol) REFERENCES tblRoles(idRol) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 4. Tabla de Carreras (Maestra)
CREATE TABLE IF NOT EXISTS tblCarreras(
    idCarrera INT AUTO_INCREMENT PRIMARY KEY,
    codigoCarrera VARCHAR(10) NOT NULL UNIQUE, -- Ej: '0901' para Sistemas
    nombreCarrera VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- 5. Tabla de Estudiantes (Maestra - Relacionada directamente a tblCarreras por idCarrera)
CREATE TABLE IF NOT EXISTS tblEstudiantes(
    noCarnetCompleto VARCHAR(14) PRIMARY KEY, -- Ej: '0901-23-11238'
    codigoCarreraEstudiante VARCHAR(4) NOT NULL, -- Ej: '0901'
    fechaInscripcion VARCHAR(2) NOT NULL,        -- Ej: '23'
    correlativoCarnet VARCHAR(5) NOT NULL,     -- Ej: '11238'
    idUsuario INT NOT NULL UNIQUE,
    nombreEstudiante VARCHAR(100) NOT NULL,
    idCarrera INT NOT NULL,                    -- Referencia directa a la carrera
    CONSTRAINT fkEstudianteUsuario 
        FOREIGN KEY (idUsuario) REFERENCES tblUsuarios(idUsuario) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fkEstudianteCarrera 
        FOREIGN KEY (idCarrera) REFERENCES tblCarreras(idCarrera) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 6. Tabla de Tutores (Maestra)
CREATE TABLE IF NOT EXISTS tblTutores(
    idTutor INT AUTO_INCREMENT PRIMARY KEY,
    idUsuario INT NOT NULL UNIQUE,
    nombreTutor VARCHAR(100) NOT NULL,
    CONSTRAINT fkTutorUsuario 
        FOREIGN KEY (idUsuario) REFERENCES tblUsuarios(idUsuario) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 7. Tabla CarreraTutores (Asociativa - El tutor sí puede impartir diferentes carreras/cursos)
CREATE TABLE IF NOT EXISTS tblCarreraTutores(
    idTutor INT NOT NULL,
    idCarrera INT NOT NULL,
    PRIMARY KEY (idTutor, idCarrera),
    CONSTRAINT fkCarreraTutoresTutor 
        FOREIGN KEY (idTutor) REFERENCES tblTutores(idTutor) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fkCarreraTutoresCarrera 
        FOREIGN KEY (idCarrera) REFERENCES tblCarreras(idCarrera) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 8. Tabla de Horarios y Disponibilidad (Publicados por los Tutores)
CREATE TABLE IF NOT EXISTS tblDisponibilidad(
    idDisponibilidad INT AUTO_INCREMENT PRIMARY KEY,
    idTutor INT NOT NULL,
    nombreMateria VARCHAR(100) NOT NULL,
    fecha DATE NOT NULL,
    horaInicio TIME NOT NULL,
    horaFin TIME NOT NULL,
    estadoDisponibilidad ENUM('disponible', 'ocupado') DEFAULT 'disponible',
    creadoEn TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fkDisponibilidadTutor 
        FOREIGN KEY (idTutor) REFERENCES tblTutores(idTutor) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 9. Tabla de Solicitudes de Tutoría (Reservas de los Estudiantes con opción de aprobación/rechazo)
CREATE TABLE IF NOT EXISTS tblSolicitudes(
    idSolicitud INT AUTO_INCREMENT PRIMARY KEY,
    noCarnetEstudiante VARCHAR(14) NOT NULL,
    idDisponibilidad INT NOT NULL,
    estadoSolicitud ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Cancelada') DEFAULT 'Pendiente',
    solicitadoEn TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fkSolicitudesEstudiante 
        FOREIGN KEY (noCarnetEstudiante) REFERENCES tblEstudiantes(noCarnetCompleto) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fkSolicitudesDisponibilidad 
        FOREIGN KEY (idDisponibilidad) REFERENCES tblDisponibilidad(idDisponibilidad) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ========================================================
-- DATOS DE PRUEBA INICIALES
-- ========================================================

INSERT INTO tblRoles (nombreRol, descripcionRol) VALUES
('Administrador', 'Control total del sistema'),
('Tutor', 'Docente encargado de impartir tutorías'),
('Estudiante', 'Alumno solicitante de tutorías');

INSERT INTO tblCarreras (codigoCarrera, nombreCarrera) VALUES
('0901', 'Ingeniería en Sistemas de Información'),
('0902', 'Ingeniería Industrial');

INSERT INTO tblUsuarios (username, contrasena, estado) VALUES
('nberducido', '1234', 'Activo'),
('vchavez', 'Sarai', 'Activo'),
('mcorzo', 'Mario26', 'Activo');
-- 1. Agregar la columna correo a la tabla tblUsuarios (permitiendo valores nulos o con un valor por defecto si es necesario)
ALTER TABLE tblUsuarios 
ADD COLUMN correo VARCHAR(150) UNIQUE AFTER contrasena;

-- 2. (Opcional pero recomendado) Actualizar los registros existentes con sus correos correspondientes
UPDATE tblUsuarios SET correo = 'nberducido@miumg.edu.gt' WHERE username = 'nberducido';
UPDATE tblUsuarios SET correo = 'vchavez@miumg.edu.gt' WHERE username = 'vchavez';
UPDATE tblUsuarios SET correo = 'mcorzo@miumg.edu.gt' WHERE username = 'mcorzo';


INSERT INTO tblAsignacionRoles (idUsuario, idRol) VALUES
(1, 2), -- Nills es Tutor
(2, 2), -- Velveth es Tutora
(3, 3); -- Mario es Estudiante

INSERT INTO tblTutores (idUsuario, nombreTutor) VALUES
(1, 'Ing. Nills Berducido'),
(2, 'Inga. Velveth Chavez');

-- Inserción de estudiante con su carrera asignada directamente por FK
INSERT INTO tblEstudiantes (noCarnetCompleto, codigoCarreraEstudiante,  fechaInscripcion, correlativoCarnet, idUsuario, nombreEstudiante, idCarrera) VALUES
('0901-23-11238', '0901', '23', '11238', 3, 'Mario Alejandro Corzo Peralta', 1);

select * from tblEstudiantes;
INSERT INTO tblCarreraTutores (idTutor, idCarrera) VALUES
(1, 1),
(2, 1);

INSERT INTO tblDisponibilidad (idTutor, nombreMateria, fecha, horaInicio, horaFin, estadoDisponibilidad) VALUES
(1, 'Desarrollo Web', '2026-09-25', '10:00:00', '10:45:00', 'disponible'),
(2, 'Bases de Datos', '2026-09-26', '15:00:00', '16:00:00', 'disponible');

INSERT INTO tblSolicitudes (idSolicitud,noCarnetEstudiante, idDisponibilidad, estadoSolicitud) VALUES
(1,'0901-23-11238', 1, 'Pendiente');

select * from tblasignacionroles;
select * from tblcarreras;
select * from tblcarreratutores;
select * from tbldisponibilidad;
select * from tblestudiantes;
select * from tblroles;
select * from tblsolicitudes;
select * from tbltutores;
select * from tblusuarios;