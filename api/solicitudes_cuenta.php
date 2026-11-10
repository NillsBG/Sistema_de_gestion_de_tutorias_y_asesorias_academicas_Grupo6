<?php
// Exclusivo del administrador: aprobar/rechazar cuentas pendientes.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../php/auth_middleware.php';
verificarRol(['Administrador']);
require_once __DIR__ . '/../php/csrf.php';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($metodo, ['GET','POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    echo json_encode(['exito'=>false,'mensaje'=>'Método no permitido.']);
    exit;
}
if ($metodo === 'POST') verificarCsrf();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../php/auditoria.php';

function responderCuentas(int $codigo, bool $exito, string $mensaje, array $datos = []): void {
    http_response_code($codigo);
    echo json_encode(array_merge(['exito'=>$exito,'mensaje'=>$mensaje],$datos), JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($metodo === 'GET') {
        $stmt = $pdo->query("SELECT s.idSolicitudCuenta, s.username, s.rolSolicitado, s.nombreCompleto,
                s.correo, s.noCarnetCompleto, c.nombreCarrera, s.tituloProfesional,
                s.especialidad, s.fechaSolicitud
                FROM tblSolicitudesCuenta s
                LEFT JOIN tblCarreras c ON c.idCarrera = s.idCarrera
                WHERE s.estadoSolicitud = 'Pendiente'
                ORDER BY s.fechaSolicitud ASC, s.idSolicitudCuenta ASC");
        responderCuentas(200,true,'Solicitudes pendientes.', ['datos'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos)) responderCuentas(400,false,'Datos inválidos.');
    $id = filter_var($datos['idSolicitudCuenta'] ?? null, FILTER_VALIDATE_INT);
    $accion = (string)($datos['accion'] ?? '');
    if (!$id || $id < 1 || !in_array($accion, ['aprobar','rechazar'], true)) {
        responderCuentas(400,false,'Acción o solicitud inválida.');
    }
    $observacion = trim((string)($datos['observacion'] ?? ''));
    if (strlen($observacion) > 300) responderCuentas(400,false,'La observación es demasiado larga.');
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT * FROM tblSolicitudesCuenta WHERE idSolicitudCuenta = ? FOR UPDATE');
    $stmt->execute([$id]);
    $sol = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sol || $sol['estadoSolicitud'] !== 'Pendiente') {
        $pdo->rollBack();
        responderCuentas(409,false,'La solicitud ya fue revisada o no existe.');
    }
    if ($accion === 'rechazar') {
        $update = $pdo->prepare("UPDATE tblSolicitudesCuenta SET estadoSolicitud='Rechazada', contrasenaHash=NULL,
            fechaRevision=NOW(), idAdministradorRevisor=?, observacionRevision=? WHERE idSolicitudCuenta=?");
        $update->execute([(int)$_SESSION['idUsuario'], $observacion ?: null, $id]);
        $pdo->commit();
        registrarAuditoria($pdo,'CUENTA_RECHAZADA','solicitud_cuenta',(string)$id);
        responderCuentas(200,true,'Solicitud rechazada.');
    }
    if (empty($sol['contrasenaHash']) || password_get_info($sol['contrasenaHash'])['algo'] === null) {
        $pdo->rollBack();
        responderCuentas(409,false,'La solicitud no tiene una contraseña segura.');
    }
    $existe = $pdo->prepare('SELECT idUsuario FROM tblUsuarios WHERE username = ? LIMIT 1');
    $existe->execute([$sol['username']]);
    if ($existe->fetchColumn()) {
        $pdo->rollBack();
        responderCuentas(409,false,'El usuario ya existe. Rechaza esta solicitud o revisa el registro.');
    }
    if ($sol['rolSolicitado'] === 'Estudiante') {
        $car = $pdo->prepare('SELECT 1 FROM tblEstudiantes WHERE noCarnetCompleto = ? LIMIT 1');
        $car->execute([$sol['noCarnetCompleto']]);
        if ($car->fetchColumn()) {
            $pdo->rollBack();
            responderCuentas(409,false,'El carné ya se encuentra registrado.');
        }
    }
    $rol = $pdo->prepare("SELECT idRol FROM tblRoles WHERE nombreRol = ? AND estadoRol = 'Activo' LIMIT 1");
    $rol->execute([$sol['rolSolicitado']]);
    $idRol = $rol->fetchColumn();
    if (!$idRol) {
        $pdo->rollBack();
        responderCuentas(409,false,'No existe el rol solicitado en la base de datos.');
    }
    $usuario = $pdo->prepare("INSERT INTO tblUsuarios (username,contrasena,estado) VALUES (?,?,'Activo')");
    $usuario->execute([$sol['username'], $sol['contrasenaHash']]);
    $idUsuario = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO tblAsignacionRoles(idUsuario,idRol) VALUES (?,?)')->execute([$idUsuario,$idRol]);
    if ($sol['rolSolicitado'] === 'Estudiante') {
        $partes = explode('-', $sol['noCarnetCompleto']);
        $stmt = $pdo->prepare('SELECT codigoCarrera FROM tblCarreras WHERE idCarrera = ?');
        $stmt->execute([$sol['idCarrera']]);
        $codigoCarrera = $stmt->fetchColumn();
        if (!$codigoCarrera || $codigoCarrera !== $partes[0]) {
            throw new RuntimeException('Carrera y carné incompatibles.');
        }
        $stmt = $pdo->prepare('INSERT INTO tblEstudiantes
            (noCarnetCompleto,codigoCarreraEstudiante,fechaInscripcion,correlativoCarnet,idUsuario,nombreEstudiante,idCarrera)
            VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$sol['noCarnetCompleto'],$partes[0],$partes[1],$partes[2],$idUsuario,$sol['nombreCompleto'],$sol['idCarrera']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO tblTutores (idUsuario,nombreTutor,tituloProfesional,especialidad) VALUES (?,?,?,?)');
        $stmt->execute([$idUsuario,$sol['nombreCompleto'],$sol['tituloProfesional'],$sol['especialidad']]);
    }
    $stmt = $pdo->prepare("UPDATE tblSolicitudesCuenta SET estadoSolicitud='Aprobada', contrasenaHash=NULL,
        fechaRevision=NOW(), idAdministradorRevisor=?, observacionRevision=? WHERE idSolicitudCuenta=?");
    $stmt->execute([(int)$_SESSION['idUsuario'],$observacion ?: null,$id]);
    $pdo->commit();
    registrarAuditoria($pdo,'CUENTA_APROBADA','usuario',(string)$idUsuario);
    responderCuentas(200,true,'Cuenta aprobada y habilitada.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Error revisando solicitud: '.$e->getMessage());
    responderCuentas(500,false,'No se pudo procesar la solicitud. Verifica los datos y vuelve a intentar.');
}
