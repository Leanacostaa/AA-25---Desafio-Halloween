<?php
/**
 * POST /api/registro.php   body: { "nombre": "...", "email": "...", "clave": "..." }
 * 201 Usuario creado | 400 Datos inválidos | 409 Email ya registrado | 405 | 500
 */
require_once __DIR__ . '/config/conexion.php';

exigirMetodo('POST');

$datos  = leerJson();
$nombre = trim((string) ($datos['nombre'] ?? ''));
$email  = trim((string) ($datos['email'] ?? ''));
$clave  = (string) ($datos['clave'] ?? '');

if ($nombre === '' || $email === '' || $clave === '') {
    responderError(400, 'Completá nombre, correo y contraseña.');
}
if (mb_strlen($nombre) > 60) {
    responderError(400, 'El nombre no puede superar los 60 caracteres.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderError(400, 'El correo electrónico no tiene un formato válido.');
}
if (strlen($clave) < 8) {
    responderError(400, 'La contraseña debe tener al menos 8 caracteres.');
}

try {
    $pdo  = obtenerConexion();
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        responderError(409, 'Ese correo ya está registrado.');
    }

    // OWASP A02: la contraseña nunca se guarda en texto plano
    $hash = password_hash($clave, PASSWORD_BCRYPT);

    $pdo->prepare('INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)')
        ->execute([$nombre, $email, $hash]);

    responder(201, ['success' => true, 'mensaje' => '¡Registro exitoso! Ya podés iniciar sesión.']);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        responderError(409, 'Ese correo ya está registrado.');
    }
    error_log('[halloween] registro: ' . $e->getMessage());
    responderError(500, 'Error interno al registrar el usuario.');
}
