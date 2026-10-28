<?php
require_once 'php/auth_middleware.php';
verificarRol(['Administrador', 'Admin']);
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
                <span>Universidad Mariano Gálvez</span>
            </div>
        </div>

        <div class="barra-usuario">
            <span class="nombre-usuario"><i class="fa-solid fa-user-gear"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <span class="badge-rol badge-admin">Administrador</span>
            <button type="button" onclick="cerrarSesion()" class="btn btn-secundario">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </button>
        </div>
    </header>

    <main class="contenedor-principal">
        <section id="vista-admin">
            <!-- DASHBOARD DE MÉTRICAS GENERALES -->
            <div class="grid-metricas mb-lg">
                <div class="tarjeta-metrica">
                    <div class="icono-metrica oro"><i class="fa-solid fa-paper-plane"></i></div>
                    <div class="info-metrica">
                        <span id="metric-total" class="numero-metrica">0</span>
                        <span class="etiqueta-metrica">Total Solicitudes</span>
                    </div>
                </div>
                <div class="tarjeta-metrica">
                    <div class="icono-metrica verde"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="info-metrica">
                        <span id="metric-aprobadas" class="numero-metrica">0</span>
                        <span class="etiqueta-metrica">Tutorías Aprobadas</span>
                    </div>
                </div>
                <div class="tarjeta-metrica">
                    <div class="icono-metrica naranja"><i class="fa-solid fa-clock"></i></div>
                    <div class="info-metrica">
                        <span id="metric-pendientes" class="numero-metrica">0</span>
                        <span class="etiqueta-metrica">Tutorías Pendientes</span>
                    </div>
                </div>
            </div>

            <!-- GESTIÓN DE ASIGNACIONES DE CURSOS A TUTORES -->
            <div class="grid-dos-columnas mb-lg">
                <div class="tarjeta">
                    <h3 class="subtitulo-bloque"><i class="fa-solid fa-user-plus icono-oro"></i> Asignar Cursos a Tutor según Perfil</h3>
                    <p class="subtitulo-seccion">Evalúa el perfil del docente y asígnale los cursos que está facultado para impartir.</p>

                    <form id="form-asignar-curso" onsubmit="guardarAsignacionCurso(event)">
                        <div class="grupo-campo">
                            <label for="admin-select-tutor" class="etiqueta-campo">Seleccionar Tutor Docente</label>
                            <select id="admin-select-tutor" class="campo-entrada" required onchange="cargarPerfilTutorSeleccionado()">
                                <option value="">Cargando tutores...</option>
                            </select>
                            <div id="info-perfil-tutor" class="info-perfil-box mt-xs"></div>
                        </div>

                        <div class="grupo-campo">
                            <label for="admin-select-carrera" class="etiqueta-campo">Filtrar Cursos por Carrera</label>
                            <select id="admin-select-carrera" class="campo-entrada" onchange="cargarCursosPorCarreraAdmin()">
                                <option value="">Todas las Carreras</option>
                            </select>
                        </div>

                        <div class="grupo-campo">
                            <label for="admin-select-curso" class="etiqueta-campo">Curso a Asignar</label>
                            <select id="admin-select-curso" class="campo-entrada" required>
                                <option value="">Selecciona un curso...</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-oro ancho-total mt-sm">
                            <i class="fa-solid fa-link"></i> Asignar Curso a Tutor
                        </button>
                    </form>
                </div>

                <!-- TABLA DE ASIGNACIONES ACTIVAS -->
                <div class="tarjeta">
                    <h3 class="subtitulo-bloque"><i class="fa-solid fa-list-check icono-oro"></i> Asignaciones Activas</h3>
                    <p class="subtitulo-seccion">Lista de cursos habilitados por tutor en el sistema.</p>

                    <div class="contenedor-tabla">
                        <table class="tabla-datos">
                            <thead>
                                <tr>
                                    <th>Tutor</th>
                                    <th>Curso Habilitado</th>
                                    <th>Carrera</th>
                                    <th class="texto-derecha">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-asignaciones-admin">
                                <!-- Carga dinámicamente -->
                            </tbody>
                        </table>
                    </div>
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
