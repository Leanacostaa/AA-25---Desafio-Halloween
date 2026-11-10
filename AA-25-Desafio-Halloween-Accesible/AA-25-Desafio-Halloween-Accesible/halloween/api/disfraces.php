<?php
/**
 * GET /api/disfraces.php
 * Devuelve la lista de disfraces activos ordenada por votos.
 * 200 OK | 405 Método no permitido | 500 Error interno
 */
require_once __DIR__ . '/config/conexion.php';

exigirMetodo('GET');

try {
    $pdo  = obtenerConexion();
    $stmt = $pdo->prepare(
        'SELECT id, nombre, descripcion, votos, foto
           FROM disfraces
          WHERE eliminado = 0
          ORDER BY votos DESC, nombre ASC'
    );
    $stmt->execute();
    $disfraces = array_map(function (array $d) {
        $d['id']    = (int) $d['id'];
        $d['votos'] = (int) $d['votos'];
        return $d;
    }, $stmt->fetchAll());

    responder(200, $disfraces);
} catch (PDOException $e) {
    error_log('[halloween] disfraces: ' . $e->getMessage());
    responderError(500, 'No se pudieron obtener los disfraces.');
}
