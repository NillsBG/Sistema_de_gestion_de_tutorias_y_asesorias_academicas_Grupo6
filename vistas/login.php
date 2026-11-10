<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8'); ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Tutorías UMG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="fundo-login">
    <main class="contenedor-principal flex-centro">
        <section id="vista-login">
            <div class="encabezado-empresa text-center">
                <div class="icono-escudo-umg"><i class="fa-solid fa-graduation-cap"></i></div>
                <h1 class="titulo-empresa">Sistema de Gestión de Tutorías Académicas</h1>
                <p class="subtitulo-empresa">Universidad Mariano Gálvez de Guatemala</p>
            </div>

            <div class="tarjeta tarjeta-login">
                <h2 class="titulo-seccion texto-centro">Acceso Institucional</h2>
                <p class="subtitulo-seccion texto-centro">Ingresa tu usuario institucional y contraseña</p>

                <form id="form-login" onsubmit="procesarLogin(event)">
                    <div class="grupo-campo">
                        <label for="login-usuario" class="etiqueta-campo">Usuario / Nombre de Usuario</label>
                        <div class="input-icono-wrapper">
                            <i class="fa-solid fa-user icono-input"></i>
                            <input type="text" id="login-usuario" class="campo-entrada con-icono" required placeholder="Ej. mcorzo, nberducido, admin">
                        </div>
                    </div>

                    <div class="grupo-campo">
                        <label for="login-clave" class="etiqueta-campo">Contraseña</label>
                        <div class="input-icono-wrapper">
                            <i class="fa-solid fa-lock icono-input"></i>
                            <input type="password" id="login-clave" class="campo-entrada con-icono" required placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-oro ancho-total mt-sm">
                        <i class="fa-solid fa-right-to-bracket"></i> Ingresar al Sistema
                    </button>
                </form>
                <p class="texto-centro" style="margin-top:1rem;">
                    ¿Aún no tienes cuenta?
                    <button type="button" class="btn btn-secundario" onclick="mostrarRegistro(true)" style="margin-left:.4rem;">Solicitar una cuenta</button>
                </p>
            </div>
            <div id="panel-registro" class="tarjeta tarjeta-login" style="display:none;margin-top:1rem;">
                <h2 class="titulo-seccion texto-centro">Solicitud de cuenta</h2>
                <p class="subtitulo-seccion texto-centro">Un administrador revisará tu solicitud antes de activar el acceso.</p>
                <form id="form-registro" onsubmit="enviarSolicitudCuenta(event)">
                    <div class="grupo-campo"><label class="etiqueta-campo" for="reg-rol">Tipo de cuenta</label>
                        <select class="campo-entrada" id="reg-rol" required onchange="cambiarCamposRegistro()">
                            <option value="Estudiante">Estudiante</option><option value="Tutor">Tutor</option>
                        </select></div>
                    <div class="grupo-campo"><label class="etiqueta-campo" for="reg-nombre">Nombre completo</label>
                        <input class="campo-entrada" id="reg-nombre" maxlength="100" autocomplete="name" required></div>
                    <div class="grupo-campo"><label class="etiqueta-campo" for="reg-correo">Correo electrónico</label>
                        <input class="campo-entrada" id="reg-correo" type="email" maxlength="150" autocomplete="email" required></div>
                    <div class="grupo-campo"><label class="etiqueta-campo" for="reg-usuario">Usuario</label>
                        <input class="campo-entrada" id="reg-usuario" minlength="4" maxlength="100" pattern="[A-Za-z0-9._-]+" autocomplete="username" required></div>
                    <div id="campos-estudiante">
                        <div class="grupo-campo"><label class="etiqueta-campo" for="reg-carnet">Carné completo (ej. 0901-23-12345)</label>
                            <input class="campo-entrada" id="reg-carnet" maxlength="20" required></div>
                        <div class="grupo-campo"><label class="etiqueta-campo" for="reg-carrera">Carrera</label>
                            <select class="campo-entrada" id="reg-carrera" required><option value="">Cargando carreras...</option></select></div>
                    </div>
                    <div id="campos-tutor" style="display:none;">
                        <div class="grupo-campo"><label class="etiqueta-campo" for="reg-titulo">Título profesional</label>
                            <input class="campo-entrada" id="reg-titulo" maxlength="100"></div>
                        <div class="grupo-campo"><label class="etiqueta-campo" for="reg-especialidad">Especialidad</label>
                            <input class="campo-entrada" id="reg-especialidad" maxlength="100"></div>
                    </div>
                    <div class="grupo-campo"><label class="etiqueta-campo" for="reg-clave">Contraseña nueva (mínimo 12 caracteres, letras y números)</label>
                        <input class="campo-entrada" id="reg-clave" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></div>
                    <div class="grupo-campo"><label class="etiqueta-campo" for="reg-confirmar">Confirmar contraseña</label>
                        <input class="campo-entrada" id="reg-confirmar" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></div>
                    <button type="submit" class="btn btn-oro ancho-total mt-sm">Enviar solicitud de cuenta</button>
                    <button type="button" class="btn btn-secundario ancho-total mt-sm" onclick="mostrarRegistro(false)">Volver al inicio de sesión</button>
                </form>
            </div>
        </section>
    </main>

    <footer class="pie-pagina text-center">
        <p>Universidad Mariano Gálvez de Guatemala · Facultad de Ingeniería en Sistemas</p>
        <p class="creditos-equipo">Proyecto Final - Desarrollo Web (Grupo 6)</p>
    </footer>

    <script src="js/seguridad_fetch.js"></script>
    <script src="js/login.js"></script>
</body>
</html>