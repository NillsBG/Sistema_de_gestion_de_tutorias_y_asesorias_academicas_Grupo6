<?php
// php/login.php - Login con consultas preparadas y límite de intentos fallidos.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/seguridad_sesion.php';
iniciarSesionSegura();

function responderLogin(bool $exito, string $mensaje, int $codigo = 200, array $extra = []): void {
    http_response_code($codigo);
    echo json_encode(array_merge(['exito' => $exito, 'mensaje' => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responderLogin(false, 'Método no permitido.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;
$usuarioIngresado = trim((string)($input['usuario'] ?? $input['username'] ?? ''));
// No aplicar trim() a contraseñas: los espacios pueden ser parte de ellas.
$clave = (string)($input['clave'] ?? $input['contrasena'] ?? '');
if ($usuarioIngresado === '' || $clave === '' || mb_strlen($usuarioIngresado, 'UTF-8') > 100 || strlen($clave) > 1024) {
    responderLogin(false, 'Ingresa un usuario y contraseña válidos.', 400);
}

require_once __DIR__ . '/../api/conexion.php';
require_once __DIR__ . '/limite_intentos.php';

try {
    // Consulta preparada: los datos de entrada NO forman parte del texto SQL.
    $stmt = $pdo->prepare("
        SELECT u.idUsuario, u.username, u.contrasena, u.estado,
               r.nombreRol, e.noCarnetCompleto, e.codigoCarreraEstudiante,
               e.nombreEstudiante, c.nombreCarrera, c.codigoCarrera,
               t.idTutor, t.nombreTutor, t.tituloProfesional, t.especialidad
        FROM tblUsuarios u
        INNER JOIN tblAsignacionRoles ar ON u.idUsuario = ar.idUsuario
        INNER JOIN tblRoles r ON ar.idRol = r.idRol
        LEFT JOIN tblEstudiantes e ON u.idUsuario = e.idUsuario
        LEFT JOIN tblCarreras c ON e.idCarrera = c.idCarrera
        LEFT JOIN tblTutores t ON u.idUsuario = t.idUsuario
        WHERE (u.username = ? OR e.noCarnetCompleto = ?)
          AND u.estado = 'Activo'
        LIMIT 1
    ");
    $stmt->execute([$usuarioIngresado, $usuarioIngresado]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Compartir el mismo contador cuando el estudiante ingresa por carnet o por username.
    $usuarioLimite = $usuario ? (string)$usuario['username'] : $usuarioIngresado;
    $segundosRestantes = segundosBloqueoLogin($pdo, $usuarioLimite);
    if ($segundosRestantes > 0) {
        header('Retry-After: ' . $segundosRestantes);
        $minutos = (int)ceil($segundosRestantes / 60);
        responderLogin(false, 'Demasiados intentos. Intenta nuevamente en ' . $minutos . ' minuto(s).', 429, [
            'segundosRestantes' => $segundosRestantes
        ]);
    }

    // Compatibilidad temporal con las contraseñas de prueba anteriores.
    // PENDIENTE para la etapa de hashing: ELIMINAR la comparación de texto plano.
    $esValida = $usuario && (
        password_verify($clave, $usuario['contrasena']) ||
        hash_equals((string)$usuario['contrasena'], $clave)
    );

    if (!$esValida) {
        $segundosNuevos = registrarFalloLogin($pdo, $usuarioLimite);
        if ($segundosNuevos > 0) {
            header('Retry-After: ' . $segundosNuevos);
            responderLogin(false, 'Se alcanzó el límite de intentos. Acceso bloqueado durante ' . (int)ceil($segundosNuevos / 60) . ' minuto(s).', 429, [
                'segundosRestantes' => $segundosNuevos
            ]);
        }
        responderLogin(false, 'Usuario o contraseña incorrectos.', 401);
    }

    limpiarIntentosUsuario($pdo, $usuarioLimite);
    session_regenerate_id(true);
    $_SESSION = [];
    $rol = strtolower($usuario['nombreRol']);
    $_SESSION['idUsuario'] = (int)$usuario['idUsuario'];
    registrarActividadReal();
    $_SESSION['username'] = $usuario['username'];
    $_SESSION['rol'] = $usuario['nombreRol'];
    $nombre = $usuario['nombreEstudiante'] ?? $usuario['nombreTutor'] ?? $usuario['username'];
    $_SESSION['nombre'] = $nombre;
    $codigoCarrera = $usuario['codigoCarreraEstudiante'] ?? $usuario['codigoCarrera'] ?? '0901';
    $nombreCarrera = $usuario['nombreCarrera'] ?? 'Ingeniería en Sistemas';
    if ($rol === 'estudiante') {
        $_SESSION['noCarnetCompleto'] = $usuario['noCarnetCompleto'];
        $_SESSION['codigoCarrera'] = $codigoCarrera;
        $_SESSION['nombreCarrera'] = $nombreCarrera;
    } elseif ($rol === 'tutor') {
        $_SESSION['idTutor'] = $usuario['idTutor'];
        $_SESSION['tituloProfesional'] = $usuario['tituloProfesional'];
        $_SESSION['especialidad'] = $usuario['especialidad'];
    }
    responderLogin(true, '¡Inicio de sesión exitoso!', 200, [
        'usuario' => [
            'idUsuario' => (int)$usuario['idUsuario'],
            'username' => $usuario['username'],
            'rol' => $usuario['nombreRol'],
            'nombre' => $nombre,
            'noCarnetCompleto' => $usuario['noCarnetCompleto'] ?? null,
            'codigoCarrera' => $codigoCarrera,
            'nombreCarrera' => $nombreCarrera,
            'idTutor' => $usuario['idTutor'] ?? null
        ]
    ]);
} catch (Throwable $e) {
    error_log('Error en autenticación: ' . $e->getMessage());
    responderLogin(false, 'No fue posible procesar el inicio de sesión.', 500);
}
