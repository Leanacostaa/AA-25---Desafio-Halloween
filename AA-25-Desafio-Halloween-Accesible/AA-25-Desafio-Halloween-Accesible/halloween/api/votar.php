<?php
/**
 * POST /api/votar.php   body: { "disfraz_id": 3 }
 * 200 Voto registrado | 400 Datos inválidos | 401 Sin sesión
 * 404 Disfraz inexistente | 409 Ya votó | 405 | 500
 */
require_once __DIR__ . '/config/conexion.php';

exigirMetodo('POST');
iniciarSesion();

if (!isset($_SESSION['usuario_id'])) {
    responderError(401, 'Tenés que iniciar sesión para votar.');
}

$datos      = leerJson();
$disfrazId  = filter_var($datos['disfraz_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$usuarioId  = (int) $_SESSION['usuario_id'];

if ($disfrazId === false) {
    responderError(400, 'El identificador del disfraz no es válido.');
}

$pdo = obtenerConexion();

try {
    $pdo->beginTransaction();

    // 1. El disfraz debe existir y no estar eliminado
    $stmt = $pdo->prepare('SELECT nombre FROM disfraces WHERE id = ? AND eliminado = 0 FOR UPDATE');
    $stmt->execute([$disfrazId]);
    $disfraz = $stmt->fetch();
    if (!$disfraz) {
        $pdo->rollBack();
        responderError(404, 'El disfraz solicitado no existe.');
    }

    // 2. Un voto por usuario (además lo garantiza el índice UNIQUE de la tabla)
    $stmt = $pdo->prepare('SELECT id FROM votos WHERE usuario_id = ?');
    $stmt->execute([$usuarioId]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        responderError(409, 'Ya emitiste tu voto. Solo se permite un voto por usuario.');
    }

    // 3. Registrar voto e incrementar contador
    $pdo->prepare('INSERT INTO votos (usuario_id, disfraz_id) VALUES (?, ?)')
        ->execute([$usuarioId, $disfrazId]);
    $pdo->prepare('UPDATE disfraces SET votos = votos + 1 WHERE id = ?')
        ->execute([$disfrazId]);

    $pdo->commit();
    responder(200, [
        'success' => true,
        'mensaje' => 'Voto registrado para ' . $disfraz['nombre'] . '. ¡Gracias por participar!',
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // 23000 = violación de restricción (doble clic / peticiones concurrentes)
    if ($e->getCode() === '23000') {
        responderError(409, 'Ya emitiste tu voto. Solo se permite un voto por usuario.');
    }
    error_log('[halloween] votar: ' . $e->getMessage()); // el detalle técnico NO se envía al cliente
    responderError(500, 'Error interno al procesar el voto.');
}
