<?php
/**
 * POST /api/login.php   body: { "email": "...", "clave": "..." }
 * 200 Sesión iniciada | 400 Campos vacíos | 401 Credenciales incorrectas | 405 | 500
 */
require_once __DIR__ . '/config/conexion.php';

exigirMetodo('POST');
iniciarSesion();

$datos = leerJson();
$email = trim((string) ($datos['email'] ?? ''));
$clave = (string) ($datos['clave'] ?? '');

if ($email === '' || $clave === '') {
    responderError(400, 'Completá correo y contraseña.');
}

try {
    $pdo  = obtenerConexion();
    $stmt = $pdo->prepare('SELECT id, nombre, password FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Solo se acepta el hash BCRYPT (se eliminó la comparación en texto plano)
    if (!$usuario || !password_verify($clave, $usuario['password'])) {
        responderError(401, 'Correo o contraseña incorrectos.'); // mensaje genérico: no revela qué falló
    }

    session_regenerate_id(true); // previene fijación de sesión
    $_SESSION['usuario_id']     = (int) $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];

    responder(200, ['success' => true, 'usuario' => $usuario['nombre']]);
} catch (PDOException $e) {
    error_log('[halloween] login: ' . $e->getMessage());
    responderError(500, 'Error interno al iniciar sesión.');
}
