<?php
/**
 * GET /api/sesion.php
 * Informa si hay un usuario autenticado y si ya votó.
 * 200 { "autenticado": bool, "usuario": string|null, "voto_por": int|null }
 */
require_once __DIR__ . '/config/conexion.php';

exigirMetodo('GET');
iniciarSesion();

if (!isset($_SESSION['usuario_id'])) {
    responder(200, ['autenticado' => false, 'usuario' => null, 'voto_por' => null]);
}

try {
    $stmt = obtenerConexion()->prepare('SELECT disfraz_id FROM votos WHERE usuario_id = ?');
    $stmt->execute([$_SESSION['usuario_id']]);
    $voto = $stmt->fetch();

    responder(200, [
        'autenticado' => true,
        'usuario'     => $_SESSION['usuario_nombre'],
        'voto_por'    => $voto ? (int) $voto['disfraz_id'] : null,
    ]);
} catch (PDOException $e) {
    error_log('[halloween] sesion: ' . $e->getMessage());
    responderError(500, 'No se pudo consultar la sesión.');
}
