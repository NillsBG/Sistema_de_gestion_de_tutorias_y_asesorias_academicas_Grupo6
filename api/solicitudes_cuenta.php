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
    // Impedir aprobar una solicitud cuyo correo ya pertenece a otra cuenta.
    $correoEnUso = $pdo->prepare('SELECT idUsuario FROM tblUsuarios WHERE correo = ? LIMIT 1');
    $correoEnUso->execute([$sol['correo']]);
    if ($correoEnUso->fetchColumn()) {
        $pdo->rollBack();
        responderCuentas(409,false,'El correo ya está asociado a otra cuenta.');
    }
    $existe = $pdo->prepare('SELECT idUsuario FROM tblUsuarios WHERE username = ? LIMIT 1');
    $existe->execute([$sol['username']]);
    if ($existe->fetchColumn()) {
        $pdo->rollBack();
        responderCuentas(409,false,'El usuario ya existe. Rechaza esta solicitud o revisa el registro.');
    }
    $rol = $pdo->prepare("SELECT idRol FROM tblRoles WHERE nombreRol = ? AND estadoRol = 'Activo' LIMIT 1");
    $rol->execute([$sol['rolSolicitado']]);
    $idRol = $rol->fetchColumn();
    if (!$idRol) {
        $pdo->rollBack();
        responderCuentas(409,false,'No existe el rol solicitado en la base de datos.');
    }
    $usuario = $pdo->prepare("INSERT INTO tblUsuarios (username,contrasena,correo,estado) VALUES (?,?,?,'Activo')");
    $usuario->execute([$sol['username'], $sol['contrasenaHash'], $sol['correo']]);
    $idUsuario = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO tblAsignacionRoles(idUsuario,idRol) VALUES (?,?)')->execute([$idUsuario,$idRol]);
    if ($sol['rolSolicitado'] === 'Estudiante') {
        // Se asigna un carné INTERNO al aprobar, no antes (solicitudes rechazadas no consumen números).
        $stmt = $pdo->prepare('SELECT codigoCarrera FROM tblCarreras WHERE idCarrera = ?');
        $stmt->execute([(int)$sol['idCarrera']]);
        $codigoCarrera = (string)$stmt->fetchColumn();
        if (!preg_match('/^\d{4}$/D', $codigoCarrera)) {
            throw new RuntimeException('La carrera requiere un código de cuatro dígitos.');
        }
        // Año de la solicitud, no de la aprobación, para evitar cambios en enero.
        $anio = (new DateTimeImmutable((string)$sol['fechaSolicitud']))->format('y');
        $idCarrera = (int)$sol['idCarrera'];
        $pdo->prepare('INSERT IGNORE INTO tblCorrelativosCarnet (idCarrera, anioIngreso, ultimoCorrelativo) VALUES (?, ?, 0)')
            ->execute([$idCarrera, $anio]);
        $bloqueo = $pdo->prepare('SELECT ultimoCorrelativo FROM tblCorrelativosCarnet WHERE idCarrera = ? AND anioIngreso = ? FOR UPDATE');
        $bloqueo->execute([$idCarrera, $anio]);
        $ultimo = (int)$bloqueo->fetchColumn();
        // Se respeta la numeración preexistente del mismo año y carrera.
        $existentes = $pdo->prepare("SELECT COALESCE(MAX(CAST(correlativoCarnet AS UNSIGNED)), 0)
            FROM tblEstudiantes WHERE codigoCarreraEstudiante = ? AND fechaInscripcion = ?
            AND correlativoCarnet REGEXP '^[0-9]+$'");
        $existentes->execute([$codigoCarrera, $anio]);
        $ultimo = max($ultimo, (int)$existentes->fetchColumn());
        $siguiente = $ultimo + 1;
        if ($siguiente > 9999999999) throw new RuntimeException('Se agotó el rango de carnés.');
        $correlativo = str_pad((string)$siguiente, 4, '0', STR_PAD_LEFT);
        $carnet = $codigoCarrera . '-' . $anio . '-' . $correlativo;
        $pdo->prepare('UPDATE tblCorrelativosCarnet SET ultimoCorrelativo = ? WHERE idCarrera = ? AND anioIngreso = ?')
            ->execute([$siguiente, $idCarrera, $anio]);
        $stmt = $pdo->prepare('INSERT INTO tblEstudiantes
            (noCarnetCompleto,codigoCarreraEstudiante,fechaInscripcion,correlativoCarnet,idUsuario,nombreEstudiante,idCarrera)
            VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$carnet,$codigoCarrera,$anio,(string)$siguiente,$idUsuario,$sol['nombreCompleto'],$idCarrera]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO tblTutores (idUsuario,nombreTutor,tituloProfesional,especialidad) VALUES (?,?,?,?)');
        $stmt->execute([$idUsuario,$sol['nombreCompleto'],$sol['tituloProfesional'],$sol['especialidad']]);
    }
    $stmt = $pdo->prepare("UPDATE tblSolicitudesCuenta SET estadoSolicitud='Aprobada', contrasenaHash=NULL,
        fechaRevision=NOW(), idAdministradorRevisor=?, observacionRevision=? WHERE idSolicitudCuenta=?");
    $stmt->execute([(int)$_SESSION['idUsuario'],$observacion ?: null,$id]);
    $pdo->commit();
    registrarAuditoria($pdo,'CUENTA_APROBADA','usuario',(string)$idUsuario);
    responderCuentas(200,true,'Cuenta aprobada y habilitada.',
        ['carnetGenerado' => $sol['rolSolicitado'] === 'Estudiante' ? $carnet : null]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Error revisando solicitud: '.$e->getMessage());
    responderCuentas(500,false,'No se pudo procesar la solicitud. Verifica los datos y vuelve a intentar.');
}
