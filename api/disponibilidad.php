<?php
// api/disponibilidad.php - CRUD de Horarios con Expiración Automática y Filtro por Carrera
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    try {
        $codigoCarrera = trim($_GET['codigoCarrera'] ?? '');
        $idTutor       = (int)($_GET['idTutor'] ?? 0);

        $sql = "
            SELECT DISTINCT
                d.idDisponibilidad,
                d.idTutor,
                t.nombreTutor,
                d.idCurso,
                d.nombreMateria,
                d.fecha,
                d.horaInicio,
                d.horaFin,
                d.duracionMinutos,
                d.cupoMaximo,
                d.estadoDisponibilidad,
                c.codigoCarrera,
                c.nombreCarrera,
                (SELECT COUNT(*) FROM tblSolicitudes s WHERE s.idDisponibilidad = d.idDisponibilidad AND s.estadoSolicitud IN ('Pendiente', 'Aprobada')) AS cuposOcupados
            FROM tblDisponibilidad d
            INNER JOIN tblTutores t ON d.idTutor = t.idTutor
            INNER JOIN tblCursos cur ON d.idCurso = cur.idCurso
            INNER JOIN tblCarreras c ON cur.idCarrera = c.idCarrera
            WHERE d.estadoDisponibilidad = 'disponible'
              AND CONCAT(d.fecha, ' ', d.horaFin) >= NOW() -- EXPIRACIÓN AUTOMÁTICA DE FECHAS PASADAS
        ";

        $params = [];

        if (!empty($codigoCarrera)) {
            $sql .= " AND c.codigoCarrera = :codCarrera";
            $params[':codCarrera'] = $codigoCarrera;
        }

        if ($idTutor > 0) {
            $sql .= " AND d.idTutor = :idTutor";
            $params[':idTutor'] = $idTutor;
        }

        $sql .= " ORDER BY d.fecha ASC, d.horaInicio ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'exito' => true,
            'datos' => $datos
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al obtener disponibilidades: ' . $e->getMessage()]);
    }
} elseif ($metodo === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $idTutor    = (int)($input['idTutor'] ?? 0);
        $idCurso    = (int)($input['idCurso'] ?? 0);
        $fecha      = trim($input['fecha'] ?? '');
        $horaInicio = trim($input['horaInicio'] ?? '');
        $duracion   = (int)($input['duracionMinutos'] ?? 45);

        if ($idTutor <= 0 || $idCurso <= 0 || empty($fecha) || empty($horaInicio)) {
            http_response_code(400);
            echo json_encode(['exito' => false, 'mensaje' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // Obtener nombre del curso
        $stmtC = $pdo->prepare("SELECT nombreCurso FROM tblCursos WHERE idCurso = ?");
        $stmtC->execute([$idCurso]);
        $cursoObj = $stmtC->fetch();
        if (!$cursoObj) {
            echo json_encode(['exito' => false, 'mensaje' => 'El curso seleccionado no existe.']);
            exit;
        }
        $nombreMateria = $cursoObj['nombreCurso'];

        // Calcular hora fin
        $horaFin = date('H:i:s', strtotime("+$duracion minutes", strtotime($horaInicio)));

        $stmt = $pdo->prepare("
            INSERT INTO tblDisponibilidad (idTutor, idCurso, nombreMateria, fecha, horaInicio, horaFin, duracionMinutos, cupoMaximo, estadoDisponibilidad)
            VALUES (:idTutor, :idCurso, :materia, :fecha, :hInicio, :hFin, :duracion, 30, 'disponible')
        ");
        $stmt->execute([
            ':idTutor'  => $idTutor,
            ':idCurso'  => $idCurso,
            ':materia'  => $nombreMateria,
            ':fecha'    => $fecha,
            ':hInicio'  => $horaInicio,
            ':hFin'     => $horaFin,
            ':duracion' => $duracion
        ]);

        echo json_encode(['exito' => true, 'mensaje' => 'Horario de tutoría publicado exitosamente.'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al guardar disponibilidad: ' . $e->getMessage()]);
    }
}
?>
