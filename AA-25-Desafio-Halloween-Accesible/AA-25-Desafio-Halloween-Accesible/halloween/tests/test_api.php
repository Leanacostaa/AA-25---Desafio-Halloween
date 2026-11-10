<?php
/**
 * Pruebas automáticas de la API REST (caja negra).
 * Uso (con el sitio levantado en XAMPP o con `php -S localhost:8000`):
 *
 *     php tests/test_api.php http://localhost/halloween
 *
 * No necesita librerías externas: usa file_get_contents con stream context.
 * Cada prueba verifica el código HTTP semántico y el tiempo de respuesta.
 */

if (!function_exists('mb_str_pad')) { // PHP < 8.3 (XAMPP)
    function mb_str_pad(string $s, int $n): string { return $s . str_repeat(' ', max(0, $n - mb_strlen($s))); }
}

$base = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$cookie = '';
$pasadas = 0;
$fallidas = 0;
$tiempos = [];

function pedir(string $metodo, string $ruta, ?array $json = null, ?string $crudo = null): array
{
    global $base, $cookie;
    $cabeceras = ['Accept: application/json'];
    if ($cookie !== '') $cabeceras[] = "Cookie: $cookie";
    $body = $crudo ?? ($json !== null ? json_encode($json) : null);
    if ($body !== null) $cabeceras[] = 'Content-Type: application/json';

    $ctx = stream_context_create(['http' => [
        'method'        => $metodo,
        'header'        => implode("\r\n", $cabeceras),
        'content'       => $body ?? '',
        'ignore_errors' => true,   // permite leer respuestas 4xx/5xx
        'timeout'       => 10,
    ]]);

    $t0 = microtime(true);
    $resp = @file_get_contents($base . $ruta, false, $ctx);
    $ms = (microtime(true) - $t0) * 1000;

    $codigo = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $codigo = (int) $m[1];
        if (stripos($h, 'Set-Cookie:') === 0 && preg_match('/^Set-Cookie:\s*([^;]+)/i', $h, $m)) {
            $cookie = $m[1];
        }
    }
    return ['codigo' => $codigo, 'json' => json_decode((string) $resp, true), 'ms' => $ms];
}

function prueba(string $nombre, array $r, int $esperado, ?callable $extra = null): void
{
    global $pasadas, $fallidas, $tiempos;
    $tiempos[] = $r['ms'];
    $ok = $r['codigo'] === $esperado && ($extra === null || $extra($r['json']));
    $ok ? $pasadas++ : $fallidas++;
    printf("%s %s esperado %d / obtenido %d  (%5.1f ms)\n",
        $ok ? "[OK]  " : "[FAIL]", mb_str_pad($nombre, 50), $esperado, $r['codigo'], $r['ms']);
}

$email = 'test_' . bin2hex(random_bytes(4)) . '@halloween.test';
$clave = 'ClaveSegura123';

echo "Probando API en $base\n" . str_repeat('-', 100) . "\n";

// --- Adecuación funcional / eficiencia
prueba('GET disfraces devuelve lista JSON',              pedir('GET', '/api/disfraces.php'), 200, fn($j) => is_array($j) && count($j) > 0);
prueba('POST a disfraces.php -> método no permitido',   pedir('POST', '/api/disfraces.php', []), 405);

// --- Fiabilidad: validaciones y códigos semánticos
prueba('Registro con JSON inválido',                    pedir('POST', '/api/registro.php', null, '{malformado'), 400);
prueba('Registro con campos vacíos',                    pedir('POST', '/api/registro.php', ['nombre' => '', 'email' => '', 'clave' => '']), 400);
prueba('Registro con email inválido',                   pedir('POST', '/api/registro.php', ['nombre' => 'Ana', 'email' => 'no-es-mail', 'clave' => $clave]), 400);
prueba('Registro con contraseña corta',                 pedir('POST', '/api/registro.php', ['nombre' => 'Ana', 'email' => $email, 'clave' => '123']), 400);
prueba('Registro correcto',                             pedir('POST', '/api/registro.php', ['nombre' => 'Tester', 'email' => $email, 'clave' => $clave]), 201);
prueba('Registro duplicado -> conflicto',               pedir('POST', '/api/registro.php', ['nombre' => 'Tester', 'email' => $email, 'clave' => $clave]), 409);

// --- Seguridad
prueba('Votar sin sesión -> no autorizado',             pedir('POST', '/api/votar.php', ['disfraz_id' => 1]), 401);
prueba('Login con contraseña incorrecta',               pedir('POST', '/api/login.php', ['email' => $email, 'clave' => 'incorrecta']), 401);
prueba('Intento de inyección SQL en login',             pedir('POST', '/api/login.php', ['email' => "' OR '1'='1' -- ", 'clave' => "' OR '1'='1"]), 401);
prueba('Login correcto',                                pedir('POST', '/api/login.php', ['email' => $email, 'clave' => $clave]), 200, fn($j) => ($j['usuario'] ?? '') === 'Tester');
prueba('Sesión activa',                                 pedir('GET', '/api/sesion.php'), 200, fn($j) => $j['autenticado'] === true);

// --- Reglas de negocio de votación
prueba('Votar con id inválido',                         pedir('POST', '/api/votar.php', ['disfraz_id' => 'abc']), 400);
prueba('Votar disfraz inexistente',                     pedir('POST', '/api/votar.php', ['disfraz_id' => 99999]), 404);
prueba('Voto válido',                                   pedir('POST', '/api/votar.php', ['disfraz_id' => 1]), 200);
prueba('Segundo voto del mismo usuario -> conflicto',   pedir('POST', '/api/votar.php', ['disfraz_id' => 2]), 409);
prueba('Sesión informa el voto emitido',                pedir('GET', '/api/sesion.php'), 200, fn($j) => $j['voto_por'] === 1);
prueba('Cerrar sesión',                                 pedir('POST', '/api/logout.php'), 200);
prueba('Votar después de cerrar sesión',                pedir('POST', '/api/votar.php', ['disfraz_id' => 1]), 401);

echo str_repeat('-', 100) . "\n";
printf("Resultado: %d pasadas, %d fallidas. Tiempo promedio %.1f ms, máximo %.1f ms\n",
    $pasadas, $fallidas, array_sum($tiempos) / count($tiempos), max($tiempos));
exit($fallidas === 0 ? 0 : 1);
