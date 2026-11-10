# Ficha de Autoevaluación de Calidad — ISO/IEC 25010

**Proyecto:** La Web del Terror Accesible (Desafío Halloween AA-25)
**Stack:** HTML5 + CSS3 + JavaScript Vanilla (ES Modules) · PHP 8 · MySQL/MariaDB vía PDO
**Fecha de auditoría:** 10/11/2026

| # | Característica ISO 25010 | Pregunta de control | Evidencia técnica en el código | Estado |
|---|---|---|---|---|
| 1 | **Adecuación Funcional** | ¿El backend en PHP procesa y persiste datos en MySQL vía PDO? | `api/config/conexion.php` crea una única conexión PDO (`ERRMODE_EXCEPTION`, `EMULATE_PREPARES=false`). Registro, login, sesión, votación y logout persisten en las tablas `usuarios`, `disfraces` y `votos` (`database/halloween.sql`). Se unificaron los dos flujos de login que existían (uno JSON y otro con formulario PHP, incompatibles entre sí). | ✅ Cumple |
| 2 | **Eficiencia de Desempeño** | ¿La API REST responde solicitudes JSON sin latencias desmedidas? | `tests/test_api.php` mide cada petición: promedio **11 ms**, máximo **70 ms** (el máximo corresponde a `password_hash`/`password_verify` con BCRYPT, que es lento a propósito). Lighthouse Performance **100** (LCP 0,8 s, TBT 0 ms, CLS 0,001). | ✅ Cumple |
| 3 | **Compatibilidad** | ¿La arquitectura está desacoplada (Frontend JS Vanilla / Backend PHP)? | Las vistas `.html` no contienen PHP; solo consumen la API REST mediante `fetch()` (`js/api.js`). La API solo devuelve JSON (`Content-Type: application/json`). Cualquier otro cliente (app móvil, Postman) puede usar los mismos endpoints. | ✅ Cumple |
| 4 | **Fiabilidad** | ¿Se capturan excepciones PDO y se devuelven códigos HTTP semánticos? | Todos los endpoints envuelven el acceso a datos en `try/catch (PDOException)`. Códigos usados: **200** OK, **201** creado, **400** datos inválidos, **401** sin sesión/credenciales, **404** disfraz inexistente, **405** método no permitido, **409** conflicto (email repetido / doble voto), **500** error interno. La votación usa transacción + `rollBack()`. El detalle técnico va a `error_log()`, nunca al cliente. 20/20 pruebas automáticas pasan. | ✅ Cumple |
| 5 | **Seguridad (OWASP)** | ¿Las contraseñas usan BCRYPT y los Prepared Statements previenen SQLi? | `password_hash($clave, PASSWORD_BCRYPT)` en el registro y `password_verify()` en el login (**antes se guardaban en texto plano**). 100 % de las consultas con `prepare()`/`execute()`; prueba de inyección `' OR '1'='1` → 401. Además: `session_regenerate_id()` (fijación de sesión), cookie `HttpOnly` + `SameSite=Lax`, mensaje de login genérico, `UNIQUE(usuario_id)` en `votos`, DOM construido con `textContent` (sin XSS por `innerHTML`). | ✅ Cumple |
| 6 | **Usabilidad / Accesibilidad** | ¿Lighthouse marca 100/100 y la interfaz es 100 % operable por teclado? | Lighthouse Accesibilidad **100/100** en `index.html`, `login.html` y `registro.html` (antes 91 / 92 / 92). axe-core: **0 violaciones WCAG 2.1 AA** en 5 estados dinámicos. Recorrido completo (registro → login → votar → cerrar sesión) realizado solo con Tab/Enter (`docs/evidencia/prueba_teclado.txt`). Enlace "Saltar al contenido", `:focus-visible` de alto contraste, `aria-live="polite"` + `.textContent` en todas las respuestas de `fetch()`. | ✅ Cumple |
| 7 | **Mantenibilidad** | ¿El código está estructurado en capas limpias y versionado en Git? | Capas: **datos** (`api/config`), **utilidades HTTP** (`api/lib/respuesta.php`), **endpoints** (`api/*.php`), **acceso a API** (`js/api.js`), **vistas** (`js/index.js`, `js/auth.js`), **estilos** (`css/estilos.css` único con variables). Se eliminaron estilos y scripts inline duplicados. Script SQL versionado. Pruebas automatizadas en `tests/`. Historial en Git. | ✅ Cumple |

## Característica que exigió mayor refactorización

**Seguridad (OWASP).** El código original guardaba las contraseñas en texto plano y el login
aceptaba la comparación directa `$password === $usuario['password']`. Además coexistían dos
sistemas de autenticación incompatibles (`login.html` enviaba JSON con *nombre* y `api/login.php`
esperaba un formulario con *email*), por lo que el login real nunca funcionaba desde la vista
accesible. Hubo que reescribir registro y login como endpoints JSON con BCRYPT, unificar el modelo
de usuario, agregar regeneración de sesión y eliminar la exposición de mensajes de excepción
(`$e->getMessage()`) al cliente.
