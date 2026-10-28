-- ==============================================================================
-- SISTEMA DE GESTIÓN DE TUTORÍAS Y ASESORÍAS ACADÉMICAS - UMG
-- BASE DE DATOS COMPLETA CON PENSUM DE INGENIERÍA EN SISTEMAS (0901)
-- ==============================================================================

DROP DATABASE IF EXISTS tutoria_umg;
CREATE DATABASE tutoria_umg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
    estado VARCHAR(20) DEFAULT 'Activo',
    creadoEn TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO tblUsuarios (idUsuario, username, contrasena) VALUES
(1, 'nberducido', '1234'),
(2, 'vchavez', 'Sarai'),
(3, 'mcorzo', 'Mario26'),
(4, 'admin', 'admin123');

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
(3, '0905', 'Ingeniería Electrónica');

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