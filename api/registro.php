<?php
// Registro público: únicamente solicita cuentas. No habilita acceso ni roles.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../php/csrf.php';

function respuestaRegistro(int $codigo, bool $exito, string $mensaje, array $extra = []): void {
    http_response_code($codigo);
    echo json_encode(array_merge(['exito' => $exito, 'mensaje' => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($metodo, ['GET','POST'], true)) {
    header('Allow: GET, POST');
    respuestaRegistro(405, false, 'Método no permitido.');
}
if ($metodo === 'POST') verificarCsrf();
require_once __DIR__ . '/conexion.php';

if ($metodo === 'GET') {
    try {
        $consulta = $pdo->query('SELECT idCarrera, codigoCarrera, nombreCarrera FROM tblCarreras ORDER BY nombreCarrera');
        respuestaRegistro(200, true, 'Carreras disponibles.', ['datos' => $consulta->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Throwable $e) {
        error_log('Error carreras registro: ' . $e->getMessage());
        respuestaRegistro(500, false, 'No fue posible cargar las carreras.');
    }
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) respuestaRegistro(400, false, 'Envía un formulario válido.');
$username = trim((string)($input['username'] ?? ''));
// La cuenta nueva usa nombre de usuario en mayúsculas (incluye Ñ).
$username = function_exists('mb_strtoupper')
    ? mb_strtoupper($username, 'UTF-8')
    : strtr(strtoupper($username), ['ñ' => 'Ñ', 'á' => 'Á', 'é' => 'É', 'í' => 'Í', 'ó' => 'Ó', 'ú' => 'Ú']);
$nombre = trim((string)($input['nombreCompleto'] ?? ''));
$correo = trim((string)($input['correo'] ?? ''));
$clave = (string)($input['contrasena'] ?? '');
$confirmacion = (string)($input['confirmarContrasena'] ?? '');
$rol = (string)($input['rolSolicitado'] ?? '');
// El carné no se acepta del navegador: se asigna al aprobar.
$carnet = null;
$idCarrera = filter_var($input['idCarrera'] ?? null, FILTER_VALIDATE_INT);
$titulo = trim((string)($input['tituloProfesional'] ?? ''));
$especialidad = trim((string)($input['especialidad'] ?? ''));

if (!preg_match('/\A[\p{L}\p{N}._-]{4,100}\z/u', $username)) respuestaRegistro(400,false,'Usuario inválido: usa 4-100 caracteres, letras (incluida Ñ), números, punto, guion o guion bajo.');
if ($nombre === '' || strlen($nombre) > 100 || preg_match('/[<>]/', $nombre)) respuestaRegistro(400,false,'Ingresa un nombre válido de hasta 100 caracteres.');
// HTML type=email y FILTER_VALIDATE_EMAIL rechazan ciertos correos con Ñ en la parte local.
// Permitimos letras Unicode en el nombre, y dominios DNS ASCII.
$correoValido = strlen($correo) <= 150
    && preg_match('/\A[\p{L}\p{N}._%+\-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+\z/u', $correo)
    && !str_contains($correo, '..');
if (!$correoValido) respuestaRegistro(400,false,'Ingresa un correo válido.');
if (!preg_match('//u', $clave) || preg_match_all('/./us', $clave) < 8 || strlen($clave) > 72) {
    respuestaRegistro(400,false,'La contraseña debe tener al menos 8 caracteres y un máximo de 72 bytes. Recomendamos 12 o más caracteres.');
}
if ($clave !== $confirmacion) respuestaRegistro(400,false,'Las contraseñas no coinciden.');
if (!in_array($rol, ['Estudiante','Tutor'], true)) respuestaRegistro(400,false,'Selecciona Estudiante o Tutor.');
if ($rol === 'Estudiante') {
    if (!$idCarrera || $idCarrera < 1) respuestaRegistro(400,false,'Selecciona una carrera válida.');
    $titulo = null;
    $especialidad = null;
} else {
    if ($titulo === '' || $especialidad === '' || strlen($titulo)>100 || strlen($especialidad)>100) respuestaRegistro(400,false,'Ingresa título profesional y especialidad (máximo 100 caracteres).');
    $carnet = null;
    $idCarrera = null;
}

$ipHash = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'desconocida'));
try {
    // Garantizar el límite incluso si llegan dos solicitudes a la vez desde la misma IP.
    $pdo->beginTransaction();
    $bloqueoObtenido = (int)$pdo->query('SELECT GET_LOCK(' . $pdo->quote('registro_' . $ipHash) . ', 3)')->fetchColumn();
    if ($bloqueoObtenido !== 1) {
        $pdo->rollBack();
        respuestaRegistro(503,false,'El servicio está ocupado. Vuelve a intentarlo.');
    }
    $contador = $pdo->prepare('SELECT COUNT(*) FROM tblIntentosRegistro WHERE ipHash = ? AND realizadoEn >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    $contador->execute([$ipHash]);
    if ((int)$contador->fetchColumn() >= 5) {
        $pdo->rollBack();
        respuestaRegistro(429,false,'Se alcanzó el límite de solicitudes. Intenta de nuevo más tarde.');
    }
    $pdo->prepare('INSERT INTO tblIntentosRegistro(ipHash) VALUES (?)')->execute([$ipHash]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Límite registro: '.$e->getMessage());
    respuestaRegistro(500,false,'No fue posible procesar el registro.');
} finally {
    try { $pdo->query('SELECT RELEASE_LOCK(' . $pdo->quote('registro_' . $ipHash) . ')'); } catch (Throwable $e) {}
}

try {
    // Regla de correo único: comprobar cuentas aprobadas y solicitudes anteriores.
    // Los índices UNIQUE de ambas tablas protegen contra solicitudes concurrentes.
    $correoCuenta = $pdo->prepare('SELECT 1 FROM tblUsuarios WHERE correo = ? LIMIT 1');
    $correoCuenta->execute([$correo]);
    if ($correoCuenta->fetchColumn()) respuestaRegistro(409,false,'Este correo ya está registrado.');

    $correoSolicitud = $pdo->prepare('SELECT username FROM tblSolicitudesCuenta WHERE correo = ? LIMIT 1');
    $correoSolicitud->execute([$correo]);
    $titularCorreo = $correoSolicitud->fetchColumn();
    if ($titularCorreo !== false && strcasecmp((string)$titularCorreo, $username) !== 0) {
        respuestaRegistro(409,false,'Este correo ya se utilizó en otra solicitud.');
    }

    if ($rol === 'Estudiante') {
        $codigoCarrera = $pdo->prepare('SELECT codigoCarrera FROM tblCarreras WHERE idCarrera = ?');
        $codigoCarrera->execute([$idCarrera]);
        $codigo = $codigoCarrera->fetchColumn();
        if (!$codigo || !preg_match('/^\d{4}$/D', (string)$codigo)) {
            respuestaRegistro(400,false,'La carrera no tiene un código de cuatro dígitos válido.');
        }
    }

    $existe = $pdo->prepare('SELECT 1 FROM tblUsuarios WHERE username = ? LIMIT 1');
    $existe->execute([$username]);
    if ($existe->fetchColumn()) respuestaRegistro(409,false,'Ese nombre de usuario ya está ocupado.');

    $hash = password_hash($clave, PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    $pendiente = $pdo->prepare('SELECT idSolicitudCuenta,estadoSolicitud FROM tblSolicitudesCuenta WHERE username = ? FOR UPDATE');
    $pendiente->execute([$username]);
    $anterior = $pendiente->fetch(PDO::FETCH_ASSOC);

    if ($anterior) {
        if ($anterior['estadoSolicitud'] !== 'Rechazada') {
            $pdo->rollBack();
            respuestaRegistro(409,false,'Ya existe una solicitud para este usuario.');
        }
        $stmt = $pdo->prepare("UPDATE tblSolicitudesCuenta SET
            contrasenaHash=?, rolSolicitado=?, nombreCompleto=?, correo=?,
            noCarnetCompleto=?, idCarrera=?, tituloProfesional=?, especialidad=?,
            ipHash=?, fechaSolicitud=NOW(), fechaRevision=NULL,
            idAdministradorRevisor=NULL, observacionRevision=NULL,
            estadoSolicitud='Pendiente'
            WHERE idSolicitudCuenta=? AND estadoSolicitud='Rechazada'");
        $stmt->execute([$hash,$rol,$nombre,$correo,$carnet,$idCarrera,$titulo,$especialidad,$ipHash,$anterior['idSolicitudCuenta']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO tblSolicitudesCuenta
            (username, contrasenaHash, rolSolicitado, nombreCompleto, correo,
             noCarnetCompleto, idCarrera, tituloProfesional, especialidad, ipHash)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$username,$hash,$rol,$nombre,$correo,$carnet,$idCarrera,$titulo,$especialidad,$ipHash]);
    }
    $pdo->commit();
    respuestaRegistro(200,true,'Solicitud enviada. Un administrador debe aprobarla antes de que puedas iniciar sesión.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e->getCode() === '23000') {
        respuestaRegistro(409,false,'El usuario o correo ya está utilizado en otra cuenta o solicitud.');
    }
    error_log('Error creando solicitud: '.$e->getMessage());
    respuestaRegistro(500,false,'No fue posible registrar la solicitud.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Error creando solicitud: '.$e->getMessage());
    respuestaRegistro(500,false,'No fue posible registrar la solicitud.');
}
