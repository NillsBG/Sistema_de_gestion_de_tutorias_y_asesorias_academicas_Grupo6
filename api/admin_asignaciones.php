<?php
// api/admin_asignaciones.php - Endpoint de Administrador para Asignar Cursos a Tutores
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} else {
    require_once 'conexion.php';
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    try {
        $accion = trim($_GET['accion'] ?? 'listar_asignaciones');

        if ($accion === 'tutores') {
            $stmt = $pdo->query("SELECT idTutor, nombreTutor, tituloProfesional, especialidad FROM tblTutores ORDER BY nombreTutor ASC");
            echo json_encode(['exito' => true, 'datos' => $stmt->fetchAll()]);
            exit;
        }

        if ($accion === 'carreras') {
            $stmt = $pdo->query("SELECT idCarrera, codigoCarrera, nombreCarrera FROM tblCarreras ORDER BY codigoCarrera ASC");
            echo json_encode(['exito' => true, 'datos' => $stmt->fetchAll()]);
            exit;
        }

        if ($accion === 'cursos') {
            $idCarrera = (int)($_GET['idCarrera'] ?? 0);
            $sql = "SELECT c.idCurso, c.codigoCurso, c.nombreCurso, car.codigoCarrera, car.nombreCarrera 
                    FROM tblCursos c 
                    INNER JOIN tblCarreras car ON c.idCarrera = car.idCarrera";
            $params = [];
            if ($idCarrera > 0) {
                $sql .= " WHERE c.idCarrera = ?";
                $params[] = $idCarrera;
            }
            $sql .= " ORDER BY c.nombreCurso ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['exito' => true, 'datos' => $stmt->fetchAll()]);
            exit;
        }

        // Listar asignaciones activas
        $stmt = $pdo->query("
            SELECT 
                tc.idTutor,
                tc.idCurso,
                t.nombreTutor,
                c.codigoCurso,
                c.nombreCurso,
                car.codigoCarrera,
                car.nombreCarrera
            FROM tblTutorCursos tc
            INNER JOIN tblTutores t ON tc.idTutor = t.idTutor
            INNER JOIN tblCursos c ON tc.idCurso = c.idCurso
            INNER JOIN tblCarreras car ON c.idCarrera = car.idCarrera
            ORDER BY t.nombreTutor ASC, c.nombreCurso ASC
        ");
        echo json_encode(['exito' => true, 'datos' => $stmt->fetchAll()]);

    } catch (Exception $e) {
        echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
    }
} elseif ($metodo === 'POST') {
    try {
        $input   = json_decode(file_get_contents('php://input'), true);
        $idTutor = (int)($input['idTutor'] ?? 0);
        $idCurso = (int)($input['idCurso'] ?? 0);

        if ($idTutor <= 0 || $idCurso <= 0) {
            echo json_encode(['exito' => false, 'mensaje' => 'Selecciona un tutor y un curso válidos.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT IGNORE INTO tblTutorCursos (idTutor, idCurso) VALUES (?, ?)");
        $stmt->execute([$idTutor, $idCurso]);

        echo json_encode(['exito' => true, 'mensaje' => 'Curso asignado al tutor exitosamente.']);
    } catch (Exception $e) {
        echo json_encode(['exito' => false, 'mensaje' => 'Error al asignar curso: ' . $e->getMessage()]);
    }
} elseif ($metodo === 'DELETE') {
    try {
        $input   = json_decode(file_get_contents('php://input'), true);
        $idTutor = (int)($input['idTutor'] ?? 0);
        $idCurso = (int)($input['idCurso'] ?? 0);

        $stmt = $pdo->prepare("DELETE FROM tblTutorCursos WHERE idTutor = ? AND idCurso = ?");
        $stmt->execute([$idTutor, $idCurso]);

        echo json_encode(['exito' => true, 'mensaje' => 'Asignación eliminada exitosamente.']);
    } catch (Exception $e) {
        echo json_encode(['exito' => false, 'mensaje' => 'Error al eliminar asignación: ' . $e->getMessage()]);
    }
}
?>
