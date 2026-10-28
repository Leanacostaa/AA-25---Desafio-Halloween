<?php
// api/disfraces.php
header('Content-Type: application/json; charset=UTF-8');
session_start();
require_once 'conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Listar disfraces no eliminados
    $stmt = $pdo->query("SELECT id, nombre, descripcion, votos, foto FROM disfraces WHERE eliminado = 0");
    $disfraces = $stmt->fetchAll();
    echo json_encode($disfraces);
    exit;
}

if ($method === 'POST') {
    // Validar autenticación obligatoria (Buenas prácticas OWASP A01)
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Debe iniciar sesión para emitir un voto."]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $id_disfraz = $data['id_disfraz'] ?? null;
    $id_usuario = $_SESSION['usuario_id'];

    if (!$id_disfraz) {
        http_response_code(400);
        echo json_encode(["error" => "ID de disfraz inválido."]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Verificar si el usuario ya votó por este disfraz (Prevenir votos duplicados)
        $stmtCheck = $pdo->prepare("SELECT id FROM votos WHERE id_usuario = ? AND id_disfraz = ?");
        $stmtCheck->execute([$id_usuario, $id_disfraz]);
        if ($stmtCheck->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(["error" => "Ya has votado por este disfraz."]);
            $pdo->rollBack();
            exit;
        }

        // 2. Registrar el voto
        $stmtVoto = $pdo->prepare("INSERT INTO votos (id_usuario, id_disfraz) VALUES (?, ?)");
        $stmtVoto->execute([$id_usuario, $id_disfraz]);

        // 3. Incrementar el contador en la tabla disfraces
        $stmtUpdate = $pdo->prepare("UPDATE disfraces SET votos = votos + 1 WHERE id = ?");
        $stmtUpdate->execute([$id_disfraz]);

        $pdo->commit();
        http_response_code(200);
        echo json_encode(["mensaje" => "¡Voto registrado con éxito!"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Error interno al procesar el voto."]);
    }
    exit;
}
?>