<?php
session_start();
require_once 'conexion.php';

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($nombre) && !empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $mensaje = "El correo ya está registrado.";
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
            if ($stmtInsert->execute([$nombre, $email, $password])) {
                $mensaje = "¡Registro exitoso! Ya puedes iniciar sesión.";
            } else {
                $mensaje = "Error al registrar el usuario.";
            }
        }
    } else {
        $mensaje = "Por favor completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Halloween</title>
    <style>
        body { background-color: #121212; color: #ffffff; font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #1a1a1a; border: 2px solid #ff6600; padding: 2rem; border-radius: 8px; width: 300px; text-align: center; }
        h2 { color: #ff6600; }
        input { width: 90%; padding: 0.8rem; margin: 0.5rem 0; background: #2a2a2a; border: 1px solid #444; color: white; border-radius: 4px; }
        button { background: #ff6600; border: none; padding: 0.8rem; width: 100%; color: white; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 1rem; }
        button:hover { background: #e55b00; }
        .msg { color: #ff9933; font-size: 0.9rem; margin-top: 1rem; }
        a { color: #ff9933; text-decoration: none; display: block; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🦇 Registro de Usuario 🎃</h2>
        <?php if (!empty($mensaje)): ?>
            <p class="msg"><?= $mensaje ?></p>
        <?php endif; ?>
        <form action="registro.php" method="POST">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Registrarse</button>
        </form>
        <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
        <a href="../index.html">Volver al inicio</a>
    </div>
</body>
</html>