
<?php
session_start();
require_once 'conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && ($password === $usuario['password'] || password_verify($password, $usuario['password']))) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'] ?? 'Usuario';
            header("Location: ../index.html");
            exit;
        } else {
            $error = "Credenciales incorrectas.";
        }
    } else {
        $error = "Por favor completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Halloween</title>
    <style>
        body { background-color: #121212; color: #ffffff; font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #1a1a1a; border: 2px solid #ff6600; padding: 2rem; border-radius: 8px; width: 300px; text-align: center; }
        h2 { color: #ff6600; }
        input { width: 90%; padding: 0.8rem; margin: 0.5rem 0; background: #2a2a2a; border: 1px solid #444; color: white; border-radius: 4px; }
        button { background: #ff6600; border: none; padding: 0.8rem; width: 100%; color: white; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 1rem; }
        button:hover { background: #e55b00; }
        .error { color: #ff4444; font-size: 0.9rem; margin-top: 1rem; }
        a { color: #ff9933; text-decoration: none; display: block; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🎃 Iniciar Sesión 🦇</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
        <a href="registro.php">¿No tienes cuenta? Regístrate</a>
        <a href="../index.html">Volver al inicio</a>
    </div>
</body>
</html>