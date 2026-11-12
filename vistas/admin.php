<?php
require_once 'php/auth_middleware.php';
verificarRol(['Administrador']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador - Sistema de Tutorías UMG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="encabezado">
        <div class="logotipo">
            <div class="icono-logo"><i class="fa-solid fa-graduation-cap"></i></div>
            <div class="titulo-logo">
                <h1>Sistema de Tutorías Académicas</h1>
                <span>Universidad Mariano Gálvez - Panel de Administración</span>
            </div>
        </div>

        <div class="barra-usuario">
            <span class="nombre-usuario"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <span class="badge-rol badge-admin">Administrador</span>
            <button type="button" onclick="cerrarSesion()" class="btn btn-secundario">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </button>
        </div>
    </header>

    <main class="contenedor-principal">
        <section id="vista-admin">
            
            <!-- DASHBOARD DE MÉTRICAS GLOBALES -->
            <div class="tarjeta mb-lg">
                <h3 class="subtitulo-bloque mb-sm"><i class="fa-solid fa-chart-line icono-oro"></i> Estado General de Tutorías</h3>
                <div class="grid-metricas">
                    <div class="tarjeta-metrica">
                        <div class="icono-metrica oro"><i class="fa-solid fa-folder-open"></i></div>
                        <div>
                            <span id="metric-total" class="numero-metrica">0</span>
                            <span class="etiqueta-metrica">Solicitudes Totales</span>
                        </div>
                    </div>
                    <div class="tarjeta-metrica">
                        <div class="icono-metrica verde"><i class="fa-solid fa-circle-check"></i></div>
                        <div>
                            <span id="metric-aprobadas" class="numero-metrica">0</span>
                            <span class="etiqueta-metrica">Citas Aprobadas</span>
                        </div>
                    </div>
                    <div class="tarjeta-metrica">
                        <div class="icono-metrica naranja"><i class="fa-solid fa-clock"></i></div>
                        <div>
                            <span id="metric-pendientes" class="numero-metrica">0</span>
                            <span class="etiqueta-metrica">Pendientes</span>
                        </div>
                    </div>
                    <div class="tarjeta-metrica">
                        <div class="icono-metrica azul"><i class="fa-solid fa-percent"></i></div>
                        <div>
                            <span id="metric-tasa" class="numero-metrica">0%</span>
                            <span class="etiqueta-metrica">Tasa de Atención</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-dos-columnas mb-lg">
                
                <!-- MÓDULO 1: ASIGNACIÓN DE CURSOS A TUTORES -->
                <div class="tarjeta">
                    <h3 class="subtitulo-bloque"><i class="fa-solid fa-user-check icono-oro"></i> Asignar Cursos a Tutores</h3>
                    <p class="subtitulo-seccion mb-sm">Evalúa el perfil profesional del tutor y autoriza qué materias puede impartir.</p>

                    <form id="form-asignar-curso" onsubmit="guardarAsignacion(event)">
                        <div class="grupo-campo">
                            <label for="select-tutor" class="etiqueta-campo">Seleccionar Tutor Docente</label>
                            <select id="select-tutor" class="campo-entrada" onchange="mostrarPerfilTutor(this.value)" required>
                                <option value="">Cargando tutores...</option>
                            </select>
                        </div>

                        <div id="info-perfil-tutor" class="info-perfil-box mb-sm" style="display:none;">
                            <!-- Detalle del perfil del tutor seleccionado -->
                        </div>

                        <div class="grupo-campo">
                            <label for="select-carrera" class="etiqueta-campo">Seleccionar Facultad / Carrera</label>
                            <select id="select-carrera" class="campo-entrada" onchange="cargarCursosPorCarrera(this.value)" required>
                                <option value="">Selecciona carrera...</option>
                            </select>
                        </div>

                        <div class="grupo-campo">
                            <label for="select-curso" class="etiqueta-campo">Seleccionar Curso a Asignar</label>
                            <select id="select-curso" class="campo-entrada" required disabled>
                                <option value="">Primero selecciona una carrera...</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-oro ancho-total mt-sm">
                            <i class="fa-solid fa-plus-circle"></i> Asignar Curso al Tutor
                        </button>
                    </form>
                </div>

                <!-- MÓDULO 2 (NUEVO): ÁREA EXCLUSIVA DE CAMBIO DE CARRERA DE ESTUDIANTES -->
                <div class="tarjeta">
                    <h3 class="subtitulo-bloque"><i class="fa-solid fa-graduation-cap icono-oro"></i> Cambio de Carrera de Estudiante</h3>
                    <p class="subtitulo-seccion mb-sm">Traslada a un estudiante de carrera (ej: de Sistemas a Biomédica). El sistema actualizará su carnet, catálogo de cursos y eliminará sus tutorías anteriores.</p>

                    <div class="grupo-campo">
                        <label for="select-estudiante-cambio" class="etiqueta-campo">Seleccionar Estudiante</label>
                        <select id="select-estudiante-cambio" class="campo-entrada" onchange="actualizarDetalleEstudianteCambio()" required>
                            <option value="">Cargando lista de estudiantes...</option>
                        </select>
                    </div>

                    <div id="info-estudiante-cambio" class="info-perfil-box mb-sm" style="display:none;">
                        <!-- Muestra carrera actual y carnet -->
                    </div>

                    <div class="grupo-campo">
                        <label for="select-nueva-carrera-cambio" class="etiqueta-campo">Nueva Carrera a Asignar</label>
                        <select id="select-nueva-carrera-cambio" class="campo-entrada" required>
                            <option value="">Cargando carreras...</option>
                        </select>
                    </div>

                    <button type="button" onclick="ejecutarCambioCarrera()" class="btn btn-oro ancho-total mt-sm">
                        <i class="fa-solid fa-arrows-rotate"></i> Realizar Cambio de Carrera
                    </button>
                </div>

            </div>

            <!-- TABLA DE ASIGNACIONES ACTUALES TUTOR-CURSO -->
            <div class="tarjeta">
                <h3 class="subtitulo-bloque"><i class="fa-solid fa-list-check icono-oro"></i> Cursos Asignados Actualmente a Tutores</h3>
                <p class="subtitulo-seccion mb-sm">Listado global de los cursos que los docentes están autorizados a impartir.</p>

                <div class="contenedor-tabla">
                    <table class="tabla-datos">
                        <thead>
                            <tr>
                                <th>Tutor Docente</th>
                                <th>Código y Curso</th>
                                <th>Carrera / Facultad</th>
                                <th class="texto-derecha">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-asignaciones-admin">
                            <!-- Se carga dinámicamente vía js/admin.js -->
                        </tbody>
                    </table>
                </div>
            </div>

        </section>
    </main>

    <footer class="pie-pagina text-center">
        <p>Universidad Mariano Gálvez de Guatemala · Facultad de Ingeniería en Sistemas</p>
        <p class="creditos-equipo">Proyecto Final - Desarrollo Web (Grupo 6)</p>
    </footer>

    <script src="js/admin.js"></script>
</body>
</html>
