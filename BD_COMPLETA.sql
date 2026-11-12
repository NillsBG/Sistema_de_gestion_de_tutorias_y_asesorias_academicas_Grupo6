-- INSTALACIÓN NUEVA: ejecutar en base vacía; no contiene DROP DATABASE.
-- Si ya tienes datos, usa sql/ACTUALIZAR_BD_EXISTENTE.sql en su lugar.
-- Cuentas demostrativas originales: hashes bcrypt, cambiar contraseñas antes de publicar.
-- ==============================================================================
-- SISTEMA DE GESTIÓN DE TUTORÍAS Y ASESORÍAS ACADÉMICAS - UMG
-- BASE DE DATOS COMPLETA CON PENSUM DE INGENIERÍA EN SISTEMAS (0901)
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS tutoria_umg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tutoria_umg;

-- ------------------------------------------------------------------------------
-- 1. TABLA DE ROLES
-- ------------------------------------------------------------------------------
CREATE TABLE tblRoles (
    idRol INT AUTO_INCREMENT PRIMARY KEY,
    nombreRol VARCHAR(50) NOT NULL UNIQUE,
    descripcionRol VARCHAR(255),
    estadoRol VARCHAR(20) DEFAULT 'Activo'
) ENGINE=InnoDB;

INSERT INTO tblRoles (idRol, nombreRol, descripcionRol) VALUES
(1, 'Administrador', 'Acceso total y asignación de tutores'),
(2, 'Tutor', 'Publicación de tutorías y atención de citas'),
(3, 'Estudiante', 'Solicitud de tutorías académicas');

-- ------------------------------------------------------------------------------
-- 2. TABLA DE USUARIOS
-- ------------------------------------------------------------------------------
CREATE TABLE tblUsuarios (
    idUsuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    correo VARCHAR(150) NULL UNIQUE,
    estado VARCHAR(20) DEFAULT 'Activo',
    creadoEn TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO tblUsuarios (idUsuario, username, contrasena) VALUES
(1, 'nberducido', '$2y$12$beSuNAnUvcyGtduwLC4NgOd0u.mmSAQhVc0i35zVxCUPJJpl52k3a'),
(2, 'vchavez', '$2y$12$c8ExYGlIzYxRjyEo7s6e8eP.mfKmhQ9isrhW4OTt6txgqAsH0hqA6'),
(3, 'mcorzo', '$2y$12$HhUJnlt7olDllKyRmipVZODUeKXcL.B9H.MIu9YSKBihCaOl2SzpK'),
(4, 'admin', '$2y$12$yrmDvXmaf2.DkKC/Qpqff..SMw0V5VmAlTFHuvPCeuTDbnDDSB8CC');

-- ------------------------------------------------------------------------------
-- 3. TABLA ASIGNACIÓN DE ROLES
-- ------------------------------------------------------------------------------
CREATE TABLE tblAsignacionRoles (
    idUsuario INT NOT NULL,
    idRol INT NOT NULL,
    PRIMARY KEY (idUsuario, idRol),
    CONSTRAINT fkAsignacionUsuario FOREIGN KEY (idUsuario) REFERENCES tblUsuarios(idUsuario) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fkAsignacionRol FOREIGN KEY (idRol) REFERENCES tblRoles(idRol) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO tblAsignacionRoles (idUsuario, idRol) VALUES
(1, 2), -- Nills -> Tutor
(2, 2), -- Velveth -> Tutora
(3, 3), -- Mario -> Estudiante
(4, 1); -- Admin -> Administrador

-- ------------------------------------------------------------------------------
-- 4. TABLA DE CARRERAS
-- ------------------------------------------------------------------------------
CREATE TABLE tblCarreras (
    idCarrera INT AUTO_INCREMENT PRIMARY KEY,
    codigoCarrera VARCHAR(10) NOT NULL UNIQUE,
    nombreCarrera VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO tblCarreras (idCarrera, codigoCarrera, nombreCarrera) VALUES
(1, '0901', 'Ingeniería en Sistemas de Información y Ciencias de la Computación'),
(2, '1012', 'Ingeniería Industrial'),
(3, '0905', 'Ingeniería Electrónica'),
(4, '0906', 'Ingeniería Biomédica');

-- ------------------------------------------------------------------------------
-- 5. TABLA DE CURSOS (PENSUM COMPLETO INGENIERÍA EN SISTEMAS 0901)
-- ------------------------------------------------------------------------------
CREATE TABLE tblCursos (
    idCurso INT AUTO_INCREMENT PRIMARY KEY,
    idCarrera INT NOT NULL,
    codigoCurso VARCHAR(10) NOT NULL,
    nombreCurso VARCHAR(100) NOT NULL,
    ciclo INT NOT NULL,
    CONSTRAINT fkCursosCarrera FOREIGN KEY (idCarrera) REFERENCES tblCarreras(idCarrera) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO tblCursos (idCarrera, codigoCurso, nombreCurso, ciclo) VALUES
-- PRIMER CICLO
(1, '001', 'Desarrollo Humano y Profesional', 1),
(1, '002', 'Metodología de la Investigación', 1),
(1, '003', 'Contabilidad I', 1),
(1, '004', 'Introducción a los Sistemas de Cómputo', 1),
(1, '005', 'Lógica de Sistemas', 1),
-- SEGUNDO CICLO
(1, '006', 'Precálculo', 2),
(1, '007', 'Álgebra Lineal', 2),
(1, '008', 'Algoritmos', 2),
(1, '009', 'Contabilidad II', 2),
(1, '010', 'Matemática Discreta', 2),
-- TERCER CICLO
(1, '011', 'Física I', 3),
(1, '012', 'Programación I', 3),
(1, '013', 'Cálculo I', 3),
(1, '014', 'Proceso Administrativo', 3),
(1, '015', 'Derecho Informático', 3),
-- CUARTO CICLO
(1, '016', 'Microeconomía', 4),
(1, '017', 'Programación II', 4),
(1, '018', 'Cálculo II', 4),
(1, '019', 'Estadística I', 4),
(1, '020', 'Física II', 4),
-- QUINTO CICLO
(1, '021', 'Métodos Numéricos', 5),
(1, '022', 'Programación III', 5),
(1, '023', 'Emprendedores de Negocios', 5),
(1, '024', 'Electrónica Analógica', 5),
(1, '025', 'Estadística II', 5),
-- SEXTO CICLO
(1, '026', 'Investigación de Operaciones', 6),
(1, '027', 'Bases de Datos I', 6),
(1, '028', 'Autómatas y Lenguajes Formales', 6),
(1, '029', 'Sistemas Operativos I', 6),
(1, '030', 'Electrónica Digital', 6),
-- SÉPTIMO CICLO
(1, '031', 'Bases de Datos II', 7),
(1, '032', 'Análisis de Sistemas I', 7),
(1, '033', 'Sistemas Operativos II', 7),
(1, '034', 'Arquitectura de Computadoras I', 7),
(1, '035', 'Compiladores', 7),
-- OCTAVO CICLO
(1, '036', 'Desarrollo Web', 8),
(1, '037', 'Análisis de Sistemas II', 8),
(1, '038', 'Redes de Computadoras I', 8),
(1, '039', 'Ética Profesional', 8),
(1, '040', 'Arquitectura de Computadoras II', 8),
-- NOVENO CICLO
(1, '041', 'Administración de Tecnologías de Información', 9),
(1, '042', 'Ingeniería de Software', 9),
(1, '043', 'Proyecto de Graduación I', 9),
(1, '044', 'Redes de Computadoras II', 9),
(1, '045', 'Inteligencia Artificial', 9),
-- DÉCIMO CICLO
(1, '046', 'Telecomunicaciones', 10),
(1, '047', 'Seminario de Tecnologías de Información', 10),
(1, '048', 'Aseguramiento de la Calidad de Software', 10),
(1, '049', 'Proyecto de Graduación II', 10),
(1, '050', 'Seguridad y Auditoría de Sistemas', 10);

-- CURSOS DE INGENIERÍA BIOMÉDICA (60 REGISTROS)
INSERT INTO tblCursos (idCarrera, codigoCurso, nombreCurso, ciclo) VALUES
(4, '802', 'Álgebra Superior', 1),
(4, '803', 'Geometría Plana y del Espacio', 1),
(4, '804', 'Desarrollo Humano y Profesional', 1),
(4, '001', 'Introducción a la Ingeniería Biomédica', 1),
(4, '002', 'Química Inorgánica', 1),
(4, '003', 'Biología Celular y Molecular Humana I', 1),
(4, '809', 'Cálculo I', 2),
(4, '816', 'Álgebra Lineal I', 2),
(4, '813', 'Programación para la Ciencia y la Ingeniería', 2),
(4, '817', 'Dibujo Asistido por Computadora', 2),
(4, '004', 'Biología Celular y Molecular Humana II', 2),
(4, '005', 'Química Orgánica', 2),
(4, '812', 'Física I', 3),
(4, '815', 'Cálculo II', 3),
(4, '006', 'Histología', 3),
(4, '007', 'Anatomía', 3),
(4, '008', 'Bioquímica I', 3),
(4, '009', 'Bioestadística', 3),
(4, '814', 'Física II', 4),
(4, '823', 'Cálculo Vectorial y Multivariable', 4),
(4, '822', 'Ecuaciones Diferenciales Ordinarias', 4),
(4, '013', 'Programación Aplicada a la Ingeniería Biomédica', 4),
(4, '010', 'Bioquímica II', 4),
(4, '011', 'Fisiología', 4),
(4, '821', 'Física III', 5),
(4, '827', 'Ecuaciones Diferenciales Parciales', 5),
(4, '705', 'Análisis de Variable Compleja', 5),
(4, '945', 'Mecánica de Cuerpos Rígidos', 5),
(4, '870', 'Estadística Inferencial', 5),
(4, '979', 'Ciencia y Tecnología de los Materiales', 5),
(4, '939', 'Metodología de la Investigación en Ingeniería', 6),
(4, '709', 'Circuitos Eléctricos', 6),
(4, '710', 'Señales y Sistemas', 6),
(4, '014', 'Teoría Electromagnética', 6),
(4, '015', 'Tecnología Médica', 6),
(4, '012', 'Física Médica Aplicada', 6),
(4, '715', 'Electrónica Analógica', 7),
(4, '716', 'Electrónica Digital', 7),
(4, '719', 'Sistemas de Control', 7),
(4, '740', 'Mecánica de Fluidos', 7),
(4, '017', 'Instrumentación Biomédica', 7),
(4, '018', 'Biotecnología', 7),
(4, '722', 'Microprocesadores y Microcontroladores', 8),
(4, '019', 'Técnicas de Imagen en Biomédica', 8),
(4, '020', 'Sistemas Biomédicos y Electromecánicos', 8),
(4, '021', 'Micro y Nanobiotecnología', 8),
(4, '022', 'Sensores y Actuadores para la Biomédica', 8),
(4, '023', 'Modelación y Simulación Aplicada a la Biomédica', 8),
(4, '024', 'Instrumentación Biomédica Aplicada', 9),
(4, '025', 'Sistemas de Biotransporte', 9),
(4, '026', 'Dispositivos de Obtención de Imágenes', 9),
(4, '027', 'Tratamiento Digital de Señales', 9),
(4, '028', 'Bioética Médica y Calidad', 9),
(4, '029', 'Legislación Aplicada a la Biomédica', 9),
(4, '030', 'Diseño de Sistemas Biomecatrónicos', 10),
(4, '031', 'Sistemas Inteligentes', 10),
(4, '032', 'Diseño de Prototipos', 10),
(4, '033', 'Biomecánica y Biorrobótica', 10),
(4, '035', 'Administración Hospitalaria', 10),
(4, '034', 'Emprendimiento, Innovación y Proyectos', 10);

-- ------------------------------------------------------------------------------
-- 6. TABLA DE ESTUDIANTES
-- ------------------------------------------------------------------------------
CREATE TABLE tblEstudiantes (
    noCarnetCompleto VARCHAR(20) PRIMARY KEY,
    codigoCarreraEstudiante VARCHAR(10) NOT NULL,
    fechaInscripcion VARCHAR(10) NOT NULL,
    correlativoCarnet VARCHAR(10) NOT NULL,
    idUsuario INT NOT NULL UNIQUE,
    nombreEstudiante VARCHAR(100) NOT NULL,
    idCarrera INT NOT NULL,
    CONSTRAINT fkEstudianteUsuario FOREIGN KEY (idUsuario) REFERENCES tblUsuarios(idUsuario) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fkEstudianteCarrera FOREIGN KEY (idCarrera) REFERENCES tblCarreras(idCarrera) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO tblEstudiantes (noCarnetCompleto, codigoCarreraEstudiante, fechaInscripcion, correlativoCarnet, idUsuario, nombreEstudiante, idCarrera) VALUES
('0901-23-11238', '0901', '23', '11238', 3, 'Mario Alejandro Corzo Peralta', 1);

-- ------------------------------------------------------------------------------
-- 7. TABLA DE TUTORES (Con tituloProfesional y especialidad)
-- ------------------------------------------------------------------------------
CREATE TABLE tblTutores (
    idTutor INT AUTO_INCREMENT PRIMARY KEY,
    idUsuario INT NOT NULL UNIQUE,
    nombreTutor VARCHAR(100) NOT NULL,
    tituloProfesional VARCHAR(100) DEFAULT 'Ingeniero en Sistemas',
    especialidad VARCHAR(100) DEFAULT 'Desarrollo Web y Software',
    CONSTRAINT fkTutorUsuario FOREIGN KEY (idUsuario) REFERENCES tblUsuarios(idUsuario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO tblTutores (idUsuario, nombreTutor, tituloProfesional, especialidad) VALUES
(1, 'Ing. Nills Berducido', 'Ingeniero en Sistemas', 'Desarrollo Web y Redes'),
(2, 'Inga. Velveth Chavez', 'Ingeniera Electrónica', 'Electrónica y Hardware');

-- ------------------------------------------------------------------------------
-- 8. TABLA TUTOR-CURSOS (Asignaciones por Administrador)
-- ------------------------------------------------------------------------------
CREATE TABLE tblTutorCursos (
    idTutor INT NOT NULL,
    idCurso INT NOT NULL,
    asignadoEn TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idTutor, idCurso),
    CONSTRAINT fkTutorCursosTutor FOREIGN KEY (idTutor) REFERENCES tblTutores(idTutor) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fkTutorCursosCurso FOREIGN KEY (idCurso) REFERENCES tblCursos(idCurso) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO tblTutorCursos (idTutor, idCurso) VALUES
(1, 36), -- Nills -> Desarrollo Web
(1, 27), -- Nills -> Bases de Datos I
(2, 24), -- Velveth -> Electrónica Analógica
(2, 30); -- Velveth -> Electrónica Digital

-- ------------------------------------------------------------------------------
-- 9. TABLA DE DISPONIBILIDAD (Tutorías con Límite de 30 Cupos)
-- ------------------------------------------------------------------------------
CREATE TABLE tblDisponibilidad (
    idDisponibilidad INT AUTO_INCREMENT PRIMARY KEY,
    idTutor INT NOT NULL,
    idCurso INT NOT NULL,
    nombreMateria VARCHAR(100) NOT NULL,
    fecha DATE NOT NULL,
    horaInicio TIME NOT NULL,
    horaFin TIME NOT NULL,
    duracionMinutos INT DEFAULT 60,
    cupoMaximo INT DEFAULT 30,
    estadoDisponibilidad ENUM('disponible', 'ocupado', 'finalizado') DEFAULT 'disponible',
    creadoEn TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fkDisponibilidadTutor FOREIGN KEY (idTutor) REFERENCES tblTutores(idTutor) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fkDisponibilidadCurso FOREIGN KEY (idCurso) REFERENCES tblCursos(idCurso) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO tblDisponibilidad (idTutor, idCurso, nombreMateria, fecha, horaInicio, horaFin, duracionMinutos, cupoMaximo) VALUES
(1, 36, 'Desarrollo Web', '2026-11-15', '10:00:00', '11:00:00', 60, 30),
(2, 24, 'Electrónica Analógica', '2026-11-16', '14:00:00', '15:00:00', 60, 30);

-- ------------------------------------------------------------------------------
-- 10. TABLA DE SOLICITUDES DE TUTORÍA
-- ------------------------------------------------------------------------------
CREATE TABLE tblSolicitudes (
    idSolicitud INT AUTO_INCREMENT PRIMARY KEY,
    noCarnetEstudiante VARCHAR(20) NOT NULL,
    idDisponibilidad INT NOT NULL,
    estadoSolicitud ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Cancelada') DEFAULT 'Pendiente',
    solicitadoEn TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fkSolicitudesEstudiante FOREIGN KEY (noCarnetEstudiante) REFERENCES tblEstudiantes(noCarnetCompleto) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fkSolicitudesDisponibilidad FOREIGN KEY (idDisponibilidad) REFERENCES tblDisponibilidad(idDisponibilidad) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO tblSolicitudes (noCarnetEstudiante, idDisponibilidad, estadoSolicitud) VALUES
('0901-23-11238', 1, 'Pendiente');

ALTER TABLE tblDisponibilidad 
ADD COLUMN fechaFin DATE NULL AFTER fecha,
ADD COLUMN diasSeleccionados VARCHAR(100) NULL AFTER fechaFin;

-- TABLAS DE SEGURIDAD Y REGISTRO INTEGRADAS
-- Ejecutar UNA VEZ en la base de datos existente tutoria_umg.
-- No elimina datos ni modifica otras tablas.

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

-- ACTUALIZACIÓN INCREMENTAL: no elimina usuarios ni solicitudes actuales.
-- Importar en phpMyAdmin sobre la base existente tutoria_umg.


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
    UNIQUE KEY uqSolicitudCorreo (correo),
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

-- Ejecutar en la base EXISTENTE. No borra cuentas ni solicitudes.


CREATE TABLE IF NOT EXISTS tblCorrelativosCarnet (
    idCarrera INT NOT NULL,
    anioIngreso CHAR(2) NOT NULL,
    ultimoCorrelativo BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (idCarrera, anioIngreso),
    CONSTRAINT fkCorrelativoCarrera FOREIGN KEY (idCarrera)
        REFERENCES tblCarreras(idCarrera)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No es necesario eliminar la columna noCarnetCompleto de tblSolicitudesCuenta;
-- ahora permanece NULL hasta que el administrador aprueba. El carné final
-- se almacena en tblEstudiantes.noCarnetCompleto.

-- Historial de cambios de carrera
CREATE TABLE IF NOT EXISTS tblHistorialCambioCarrera (
    idCambio BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    idUsuarioEstudiante INT NOT NULL,
    idCarreraAnterior INT NOT NULL,
    idCarreraNueva INT NOT NULL,
    carnetAnterior VARCHAR(20) NOT NULL,
    carnetNuevo VARCHAR(20) NOT NULL,
    idAdministrador INT NOT NULL,
    solicitudesCanceladas INT NOT NULL DEFAULT 0,
    fechaCambio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idxCambioEstudiante (idUsuarioEstudiante, fechaCambio),
    CONSTRAINT fkCambioEstudiante FOREIGN KEY (idUsuarioEstudiante) REFERENCES tblUsuarios(idUsuario),
    CONSTRAINT fkCambioCarreraAnterior FOREIGN KEY (idCarreraAnterior) REFERENCES tblCarreras(idCarrera),
    CONSTRAINT fkCambioCarreraNueva FOREIGN KEY (idCarreraNueva) REFERENCES tblCarreras(idCarrera),
    CONSTRAINT fkCambioAdministrador FOREIGN KEY (idAdministrador) REFERENCES tblUsuarios(idUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
