
<?php
// api/login.php
header('Content-Type: application/json; charset=UTF-8');
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$nombre = trim($data['nombre'] ?? '');
$clave  = trim($data['clave'] ?? '');

if (empty($nombre) || empty($clave)) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nombre, clave FROM usuarios WHERE nombre = ?");
    $stmt->execute([$nombre]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($clave, $usuario['clave'])) {
        // Iniciar sesión correctamente
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];

        http_response_code(200);
        echo json_encode(["mensaje" => "Inicio de sesión exitoso", "usuario" => $usuario['nombre']]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Usuario o contraseña incorrectos"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el servidor: " . $e->getMessage()]);
}