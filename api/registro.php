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
$nombre = trim((string)($input['nombreCompleto'] ?? ''));
$correo = trim((string)($input['correo'] ?? ''));
$clave = (string)($input['contrasena'] ?? '');
$confirmacion = (string)($input['confirmarContrasena'] ?? '');
$rol = (string)($input['rolSolicitado'] ?? '');
$carnet = trim((string)($input['noCarnetCompleto'] ?? ''));
$idCarrera = filter_var($input['idCarrera'] ?? null, FILTER_VALIDATE_INT);
$titulo = trim((string)($input['tituloProfesional'] ?? ''));
$especialidad = trim((string)($input['especialidad'] ?? ''));

if (!preg_match('/^[A-Za-z0-9._-]{4,100}$/D', $username)) respuestaRegistro(400,false,'Usuario inválido: usa 4-100 caracteres, letras, números, punto, guion o guion bajo.');
if ($nombre === '' || strlen($nombre) > 100 || preg_match('/[<>]/', $nombre)) respuestaRegistro(400,false,'Ingresa un nombre válido de hasta 100 caracteres.');
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 150) respuestaRegistro(400,false,'Ingresa un correo válido.');
if (strlen($clave) < 12 || strlen($clave) > 72 || !preg_match('/[A-Za-z]/', $clave) || !preg_match('/[0-9]/', $clave)) {
    respuestaRegistro(400,false,'La contraseña requiere 12 a 72 caracteres, incluyendo letras y números.');
}
if ($clave !== $confirmacion) respuestaRegistro(400,false,'Las contraseñas no coinciden.');
if (!in_array($rol, ['Estudiante','Tutor'], true)) respuestaRegistro(400,false,'Selecciona Estudiante o Tutor.');
if ($rol === 'Estudiante') {
    if (!preg_match('/^\d{4}-\d{2}-\d{4,10}$/D', $carnet) || !$idCarrera || $idCarrera < 1) {
        respuestaRegistro(400,false,'Carné o carrera inválidos.');
    }
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
    $existe = $pdo->prepare('SELECT 1 FROM tblUsuarios WHERE username = ? LIMIT 1');
    $existe->execute([$username]);
    if ($existe->fetchColumn()) respuestaRegistro(409,false,'Ese nombre de usuario ya está ocupado.');
    if ($rol === 'Estudiante') {
        $codigoCarrera = $pdo->prepare('SELECT codigoCarrera FROM tblCarreras WHERE idCarrera = ?');
        $codigoCarrera->execute([$idCarrera]);
        $codigo = $codigoCarrera->fetchColumn();
        if (!$codigo || explode('-', $carnet)[0] !== (string)$codigo) respuestaRegistro(400,false,'El código del carné no coincide con la carrera.');
        $carnetExistente = $pdo->prepare('SELECT 1 FROM tblEstudiantes WHERE noCarnetCompleto = ? LIMIT 1');
        $carnetExistente->execute([$carnet]);
        if ($carnetExistente->fetchColumn()) respuestaRegistro(409,false,'El carné ya está registrado.');
    }
    $hash = password_hash($clave, PASSWORD_DEFAULT);
    // Se permite volver a solicitar una cuenta rechazada con el mismo username.
    $sql = 'INSERT INTO tblSolicitudesCuenta(username, contrasenaHash, rolSolicitado, nombreCompleto, correo, noCarnetCompleto, idCarrera, tituloProfesional, especialidad, ipHash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              contrasenaHash = IF(estadoSolicitud = \'Rechazada\', VALUES(contrasenaHash), contrasenaHash),
              rolSolicitado = IF(estadoSolicitud = \'Rechazada\', VALUES(rolSolicitado), rolSolicitado),
              nombreCompleto = IF(estadoSolicitud = \'Rechazada\', VALUES(nombreCompleto), nombreCompleto),
              correo = IF(estadoSolicitud = \'Rechazada\', VALUES(correo), correo),
              noCarnetCompleto = IF(estadoSolicitud = \'Rechazada\', VALUES(noCarnetCompleto), noCarnetCompleto),
              idCarrera = IF(estadoSolicitud = \'Rechazada\', VALUES(idCarrera), idCarrera),
              tituloProfesional = IF(estadoSolicitud = \'Rechazada\', VALUES(tituloProfesional), tituloProfesional),
              especialidad = IF(estadoSolicitud = \'Rechazada\', VALUES(especialidad), especialidad),
              ipHash = IF(estadoSolicitud = \'Rechazada\', VALUES(ipHash), ipHash),
              fechaSolicitud = IF(estadoSolicitud = \'Rechazada\', NOW(), fechaSolicitud),
              fechaRevision = IF(estadoSolicitud = \'Rechazada\', NULL, fechaRevision),
              idAdministradorRevisor = IF(estadoSolicitud = \'Rechazada\', NULL, idAdministradorRevisor),
              observacionRevision = IF(estadoSolicitud = \'Rechazada\', NULL, observacionRevision),
              estadoSolicitud = IF(estadoSolicitud = \'Rechazada\', \'Pendiente\', estadoSolicitud)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username,$hash,$rol,$nombre,$correo,$carnet,$idCarrera,$titulo,$especialidad,$ipHash]);
    respuestaRegistro(200,true,'Solicitud enviada. Un administrador debe aprobarla antes de que puedas iniciar sesión.');
} catch (Throwable $e) {
    error_log('Error creando solicitud: '.$e->getMessage());
    respuestaRegistro(500,false,'No fue posible registrar la solicitud.');
}
