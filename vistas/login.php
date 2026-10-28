<!DOCTYPE html>
<html lang="es">
<head>
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
            </div>
        </section>
    </main>

    <footer class="pie-pagina text-center">
        <p>Universidad Mariano Gálvez de Guatemala · Facultad de Ingeniería en Sistemas</p>
        <p class="creditos-equipo">Proyecto Final - Desarrollo Web (Grupo 6)</p>
    </footer>

    <script src="js/login.js"></script>
</body>
</html>