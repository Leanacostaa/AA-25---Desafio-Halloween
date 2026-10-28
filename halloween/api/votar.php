<?php
// api/votar.php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Debes iniciar sesión para votar"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$disfraz_id = $data['disfraz_id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];

if (!$disfraz_id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de disfraz no proporcionado"]);
    exit;
}

try {
    // 1. Verificar si el usuario YA votó (CUALQUIER disfraz)
    $stmtCheck = $pdo->prepare("SELECT id FROM votos WHERE usuario_id = ?");
    $stmtCheck->execute([$usuario_id]);
    
    if ($stmtCheck->fetch()) {
        http_response_code(400);
        echo json_encode(["error" => "Ya has emitido tu voto. Solo se permite un voto por usuario."]);
        exit;
    }

    // 2. Registrar el voto en la tabla 'votos'
    $pdo->beginTransaction();
    
    $stmtInsert = $pdo->prepare("INSERT INTO votos (usuario_id, disfraz_id) VALUES (?, ?)");
    $stmtInsert->execute([$usuario_id, $disfraz_id]);

    // 3. Incrementar el contador en la tabla 'disfraces'
    $stmtUpdate = $pdo->prepare("UPDATE disfraces SET votos = votos + 1 WHERE id = ?");
    $stmtUpdate->execute([$disfraz_id]);

    $pdo->commit();

    echo json_encode(["success" => true, "mensaje" => "¡Voto registrado con éxito!"]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["error" => "Error al procesar el voto: " . $e->getMessage()]);
}