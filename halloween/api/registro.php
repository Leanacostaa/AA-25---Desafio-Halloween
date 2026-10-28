<?php
// api/registro.php
header('Content-Type: application/json; charset=UTF-8');
require_once 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);
$nombre = trim($data['nombre'] ?? '');
$clave = $data['clave'] ?? '';

if (empty($nombre) || empty($clave)) {
    http_response_code(400);
    echo json_encode(["error" => "Complete todos los campos."]);
    exit;
}

// Buenas prácticas: BCRYPT para el hash de contraseñas (OWASP A02)
$claveHash = password_hash($clave, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, clave) VALUES (?, ?)");
    $stmt->execute([$nombre, $claveHash]);
    http_response_code(201);
    echo json_encode(["mensaje" => "Usuario registrado exitosamente."]);
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(["error" => "El nombre de usuario ya existe."]);
}
?>