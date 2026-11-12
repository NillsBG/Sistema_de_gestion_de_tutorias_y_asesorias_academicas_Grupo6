<?php
/** Cambio de carrera: únicamente administradores. Mantiene historial y evita pérdida de solicitudes. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../php/auth_middleware.php';
verificarRol(['Administrador']);
require_once __DIR__ . '/../php/csrf.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../php/auditoria.php';

function respuestaCarrera(int $codigo, bool $exito, string $mensaje, array $extra = []): void {
    http_response_code($codigo);
    echo json_encode(array_merge(['exito' => $exito, 'mensaje' => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($metodo, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    respuestaCarrera(405, false, 'Método no permitido.');
}
if ($metodo === 'POST') verificarCsrf();

try {
    if ($metodo === 'GET') {
        $estudiantes = $pdo->query("SELECT e.idUsuario, e.nombreEstudiante, e.noCarnetCompleto,
                    e.idCarrera, c.nombreCarrera, c.codigoCarrera, u.username
                 FROM tblEstudiantes e JOIN tblCarreras c ON c.idCarrera = e.idCarrera
                 JOIN tblUsuarios u ON u.idUsuario = e.idUsuario
                 ORDER BY e.nombreEstudiante, e.idUsuario")->fetchAll(PDO::FETCH_ASSOC);
        $carreras = $pdo->query("SELECT idCarrera, codigoCarrera, nombreCarrera FROM tblCarreras ORDER BY nombreCarrera")
            ->fetchAll(PDO::FETCH_ASSOC);
        respuestaCarrera(200, true, 'Datos obtenidos.', ['estudiantes' => $estudiantes, 'carreras' => $carreras]);
    }

    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos)) respuestaCarrera(400, false, 'Envía datos JSON válidos.');
    $idUsuario = filter_var($datos['idUsuario'] ?? null, FILTER_VALIDATE_INT);
    $idCarreraNueva = filter_var($datos['idCarreraNueva'] ?? null, FILTER_VALIDATE_INT);
    if (!$idUsuario || !$idCarreraNueva || $idUsuario < 1 || $idCarreraNueva < 1) {
        respuestaCarrera(400, false, 'Selecciona un estudiante y una carrera válidos.');
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT e.idUsuario, e.idCarrera, e.noCarnetCompleto, e.fechaInscripcion,
                                 e.correlativoCarnet, e.nombreEstudiante
                          FROM tblEstudiantes e WHERE e.idUsuario = ? FOR UPDATE");
    $stmt->execute([$idUsuario]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$estudiante) {
        $pdo->rollBack();
        respuestaCarrera(404, false, 'El estudiante no existe.');
    }
    $idAnterior = (int)$estudiante['idCarrera'];
    if ($idAnterior === (int)$idCarreraNueva) {
        $pdo->rollBack();
        respuestaCarrera(409, false, 'El estudiante ya pertenece a esa carrera.');
    }
    $carrera = $pdo->prepare('SELECT codigoCarrera, nombreCarrera FROM tblCarreras WHERE idCarrera = ?');
    $carrera->execute([$idCarreraNueva]);
    $nueva = $carrera->fetch(PDO::FETCH_ASSOC);
    if (!$nueva) {
        $pdo->rollBack();
        respuestaCarrera(404, false, 'La carrera seleccionada no existe.');
    }
    $cursos = $pdo->prepare('SELECT COUNT(*) FROM tblCursos WHERE idCarrera = ?');
    $cursos->execute([$idCarreraNueva]);
    if ((int)$cursos->fetchColumn() === 0) {
        $pdo->rollBack();
        respuestaCarrera(422, false, 'La carrera de destino todavía no tiene cursos registrados.');
    }
    $codigo = (string)$nueva['codigoCarrera'];
    if (!preg_match('/^[0-9]{4}$/D', $codigo)) {
        $pdo->rollBack();
        respuestaCarrera(422, false, 'El código de la nueva carrera debe tener cuatro dígitos.');
    }
    // Conservar el año del carné anterior, no asignar el año del traslado.
    $anio = (string)$estudiante['fechaInscripcion'];
    if (!preg_match('/^[0-9]{2}$/D', $anio)) {
        $pdo->rollBack();
        respuestaCarrera(422, false, 'El año de ingreso del estudiante no tiene un formato válido.');
    }
    // Serializar la asignación de correlativos para una carrera y año.
    $pdo->prepare('INSERT IGNORE INTO tblCorrelativosCarnet (idCarrera, anioIngreso, ultimoCorrelativo) VALUES (?, ?, 0)')
        ->execute([$idCarreraNueva, $anio]);
    $bloqueo = $pdo->prepare('SELECT ultimoCorrelativo FROM tblCorrelativosCarnet WHERE idCarrera = ? AND anioIngreso = ? FOR UPDATE');
    $bloqueo->execute([$idCarreraNueva, $anio]);
    $ultimo = (int)$bloqueo->fetchColumn();
    $existentes = $pdo->prepare("SELECT COALESCE(MAX(CAST(correlativoCarnet AS UNSIGNED)), 0)
                              FROM tblEstudiantes WHERE codigoCarreraEstudiante = ? AND fechaInscripcion = ?
                                AND correlativoCarnet REGEXP '^[0-9]+$'");
    $existentes->execute([$codigo, $anio]);
    $siguiente = max($ultimo, (int)$existentes->fetchColumn()) + 1;
    if ($siguiente > 9999999999) throw new RuntimeException('Correlativo de carné agotado.');
    $correlativo = str_pad((string)$siguiente, 4, '0', STR_PAD_LEFT);
    $carnetNuevo = $codigo . '-' . $anio . '-' . $correlativo;
    $carnetAnterior = (string)$estudiante['noCarnetCompleto'];

    // Las solicitudes anteriores no se eliminan: las activas se cancelan y el historial se conserva.
    $cancelar = $pdo->prepare("UPDATE tblSolicitudes SET estadoSolicitud = 'Cancelada'
                             WHERE noCarnetEstudiante = ? AND estadoSolicitud IN ('Pendiente', 'Aprobada')");
    $cancelar->execute([$carnetAnterior]);
    $canceladas = $cancelar->rowCount();

    // FK tblSolicitudes.noCarnetEstudiante tiene ON UPDATE CASCADE.
    $pdo->prepare('UPDATE tblEstudiantes
                   SET noCarnetCompleto = ?, codigoCarreraEstudiante = ?, correlativoCarnet = ?, idCarrera = ?
                   WHERE idUsuario = ?')
        ->execute([$carnetNuevo, $codigo, (string)$siguiente, $idCarreraNueva, $idUsuario]);
    $pdo->prepare('UPDATE tblCorrelativosCarnet SET ultimoCorrelativo = ? WHERE idCarrera = ? AND anioIngreso = ?')
        ->execute([$siguiente, $idCarreraNueva, $anio]);
    $pdo->prepare('INSERT INTO tblHistorialCambioCarrera
        (idUsuarioEstudiante, idCarreraAnterior, idCarreraNueva, carnetAnterior, carnetNuevo, idAdministrador, solicitudesCanceladas)
        VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$idUsuario, $idAnterior, $idCarreraNueva, $carnetAnterior, $carnetNuevo,
            (int)$_SESSION['idUsuario'], $canceladas]);
    $pdo->commit();
    registrarAuditoria($pdo, 'CAMBIO_CARRERA', 'estudiante', (string)$idUsuario);
    respuestaCarrera(200, true, 'Carrera actualizada correctamente.', [
        'carnetAnterior' => $carnetAnterior, 'carnetNuevo' => $carnetNuevo,
        'carreraNueva' => $nueva['nombreCarrera'], 'solicitudesCanceladas' => $canceladas
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Error al cambiar carrera: ' . $e->getMessage());
    respuestaCarrera(500, false, 'No fue posible realizar el cambio de carrera. Comprueba la actualización SQL.');
}
