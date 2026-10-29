<?php
require_once 'php/auth_middleware.php';
verificarRol(['Tutor', 'Administrador']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Tutor - Sistema de Tutorías UMG</title>
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
                <span>Universidad Mariano Gálvez</span>
            </div>
        </div>

        <div class="barra-usuario">
            <span class="nombre-usuario"><i class="fa-solid fa-chalkboard-user"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <span class="badge-rol badge-tutor">Tutor Docente</span>
            <button type="button" onclick="cerrarSesion()" class="btn btn-secundario">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </button>
        </div>
    </header>

    <main class="contenedor-principal">
        <section id="vista-tutor">
            <!-- SECCIÓN 1: FORMULARIO DE APERTURA DE DISPONIBILIDAD -->
            <div class="grid-dos-columnas mb-lg">
                <div class="tarjeta">
                    <h3 class="subtitulo-bloque"><i class="fa-solid fa-plus-circle icono-oro"></i> Abrir Nuevo Espacio de Tutoría</h3>
                    <p class="subtitulo-seccion">Selecciona un curso asignado a tu perfil por el Administrador y define el horario de atención.</p>

                    <form id="form-disponibilidad" onsubmit="guardarDisponibilidad(event)">
                        <div class="grupo-campo">
                            <label for="disp-curso" class="etiqueta-campo">Curso Asignado a tu Perfil</label>
                            <select id="disp-curso" class="campo-entrada" required>
                                <option value="">Cargando cursos asignados...</option>
                            </select>
                        </div>

                        <div class="grupo-campo">
                            <label for="disp-fecha" class="etiqueta-campo">Fecha de Inicio</label>
                            <input type="date" id="disp-fecha" class="campo-entrada" required>
                        </div>

                        <div class="grupo-campo">
                            <label for="disp-dias-semana" class="etiqueta-campo">¿Cuántos días a la semana la impartirás?</label>
                            <select id="disp-dias-semana" class="campo-entrada" onchange="limitarDiasSeleccionados()">
                                <option value="1">1 día</option>
                                <option value="2">2 días</option>
                                <option value="3">3 días</option>
                                <option value="4">4 días</option>
                                <option value="5">5 días</option>
                            </select>
                        </div>

                        <div class="grupo-campo">
                            <label class="etiqueta-campo">Días de la semana</label>
                            <div style="display:flex; flex-wrap:wrap; gap:0.75rem;">
                                <label><input type="checkbox" class="disp-dia" value="1" onchange="validarDiasSeleccionados(this)"> Lunes</label>
                                <label><input type="checkbox" class="disp-dia" value="2" onchange="validarDiasSeleccionados(this)"> Martes</label>
                                <label><input type="checkbox" class="disp-dia" value="3" onchange="validarDiasSeleccionados(this)"> Miércoles</label>
                                <label><input type="checkbox" class="disp-dia" value="4" onchange="validarDiasSeleccionados(this)"> Jueves</label>
                                <label><input type="checkbox" class="disp-dia" value="5" onchange="validarDiasSeleccionados(this)"> Viernes</label>
                                <label><input type="checkbox" class="disp-dia" value="6" onchange="validarDiasSeleccionados(this)"> Sábado</label>
                                <label><input type="checkbox" class="disp-dia" value="7" onchange="validarDiasSeleccionados(this)"> Domingo</label>
                            </div>
                        </div>

                        <div class="grupo-campo">
                            <label for="disp-semanas" class="etiqueta-campo">¿Durante cuántas semanas?</label>
                            <input type="number" id="disp-semanas" class="campo-entrada" min="1" max="16" value="1">
                        </div>

                        <div class="grid-dos-campos grupo-campo">
                            <div>
                                <label for="disp-inicio" class="etiqueta-campo">Hora Inicio</label>
                                <input type="time" id="disp-inicio" class="campo-entrada" required>
                            </div>
                            <div>
                                <label for="disp-duracion" class="etiqueta-campo">Duración (Minutos)</label>
                                <select id="disp-duracion" class="campo-entrada">
                                    <option value="30">30 minutos</option>
                                    <option value="45" selected>45 minutos</option>
                                    <option value="60">60 minutos (1 hora)</option>
                                    <option value="90">90 minutos (1.5 horas)</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-oro ancho-total mt-sm">
                            <i class="fa-solid fa-calendar-plus"></i> Publicar Horario de Tutoría
                        </button>
                    </form>
                </div>

                <!-- SLIDER DE MIS TUTORÍAS PUBLICADAS -->
                <div class="tarjeta">
                    <div class="seccion-carrusel-encabezado">
                        <h3 class="subtitulo-bloque"><i class="fa-solid fa-sliders icono-oro"></i> Mis Tutorías Publicadas</h3>
                        <div class="controles-carrusel">
                            <button type="button" class="btn-carrusel" onclick="moverSliderTutor(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                            <button type="button" class="btn-carrusel" onclick="moverSliderTutor(1)"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>

                    <div class="carrusel-mascara">
                        <div id="slider-tutor-pista" class="carrusel-pista">
                            <!-- Carga dinámicamente vía js/tutor.js -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: SOLICITUDES RECIBIDAS -->
            <div class="tarjeta">
                <h3 class="subtitulo-bloque"><i class="fa-solid fa-inbox icono-oro"></i> Solicitudes Recibidas de Estudiantes</h3>
                <p class="subtitulo-seccion">Gestiona las reservas solicitadas por los alumnos para tus materias.</p>

                <div class="contenedor-tabla">
                    <table class="tabla-datos">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Materia / Curso</th>
                                <th>Fecha y Hora</th>
                                <th class="texto-centro">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-solicitudes-tutor">
                            <!-- Carga dinámicamente -->
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

    <script src="js/tutor.js"></script>
</body>
</html>