<?php
require_once 'php/auth_middleware.php';
verificarRol(['Estudiante']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Estudiante - Sistema de Tutorías UMG</title>
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
            <span class="nombre-usuario"><i class="fa-solid fa-user-graduate"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <span class="badge-rol badge-estudiante">Estudiante</span>
            <button type="button" onclick="cerrarSesion()" class="btn btn-secundario">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </button>
        </div>
    </header>

    <main class="contenedor-principal">
        <section id="vista-estudiante">
            <!-- BANNER Y FILTRO DE CARRERA ESTRICTO -->
            <div class="tarjeta mb-lg">
                <div class="encabezado-portal">
                    <div>
                        <h2 class="titulo-seccion">Portal del Estudiante</h2>
                        <p class="subtitulo-seccion">Consulta las tutorías asignadas exclusivamente a tu carrera y reserva tu cita.</p>
                    </div>
                    <div class="badge-carrera">
                        <i class="fa-solid fa-book-bookmark"></i>
                        <span id="badge-carrera-texto"><?php echo htmlspecialchars($_SESSION['codigoCarrera'] . ' - ' . $_SESSION['nombreCarrera']); ?></span>
                    </div>
                </div>

                <div class="grid-filtros mt-sm">
                    <div>
                        <label for="buscar-texto" class="etiqueta-campo">Buscar por Curso o Tutor</label>
                        <input type="text" id="buscar-texto" class="campo-entrada" placeholder="Ej. Desarrollo Web, Ing. Berducido...">
                    </div>
                    <div>
                        <label for="filtrar-materia" class="etiqueta-campo">Filtrar por Materia de tu Carrera</label>
                        <select id="filtrar-materia" class="campo-entrada">
                            <option value="">Todas las materias de tu carrera</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SLIDER / CARRUSEL DE CURSOS DISPONIBLES -->
            <div class="seccion-carrusel-encabezado">
                <h3 class="subtitulo-bloque"><i class="fa-solid fa-sliders icono-oro"></i> Cursos Disponibles de tu Carrera</h3>
                <div class="controles-carrusel">
                    <button type="button" class="btn-carrusel" onclick="moverCarrusel(-1)" title="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" class="btn-carrusel" onclick="moverCarrusel(1)" title="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>

            <div class="carrusel-mascara mb-sm">
                <div id="contenedor-disponibilidad" class="carrusel-pista">
                    <!-- Se carga dinámicamente vía js/estudiante.js -->
                </div>
            </div>

            <div id="carrusel-indicadores" class="carrusel-indicadores mb-lg"></div>

            <!-- TABLA DE MIS SOLICITUDES -->
            <div class="tarjeta">
                <h3 class="subtitulo-bloque"><i class="fa-solid fa-calendar-check icono-oro"></i> Mis Solicitudes de Tutoría</h3>
                <p class="subtitulo-seccion">Estado actual de las citas que has reservado en el sistema.</p>

                <div class="contenedor-tabla">
                    <table class="tabla-datos">
                        <thead>
                            <tr>
                                <th>Materia / Curso</th>
                                <th>Tutor</th>
                                <th>Fecha y Hora</th>
                                <th>Estado</th>
                                <th class="texto-derecha">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-solicitudes-estudiante">
                            <!-- Se carga dinámicamente -->
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

    <script src="js/estudiante.js"></script>
</body>
</html>
