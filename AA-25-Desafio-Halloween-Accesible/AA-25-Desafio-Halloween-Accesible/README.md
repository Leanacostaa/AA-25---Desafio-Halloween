# AA-25 — Desafío Halloween: La Web del Terror Accesible 🎃

Auditoría de Calidad (ISO/IEC 25010), Accesibilidad (WCAG 2.1 AA) y Testing de Software.

| Página | Lighthouse Accesibilidad (antes → después) | Performance | Buenas prácticas | SEO |
|---|---|---|---|---|
| `index.html`    | 91 → **100** | 100 | 100 | 100 |
| `login.html`    | 92 → **100** | 100 | 100 | 100 |
| `registro.html` | 92 → **100** | 100 | 100 | 100 |

Reportes completos en `docs/lighthouse/` · Ficha ISO en [`docs/FICHA_ISO25010.md`](docs/FICHA_ISO25010.md).

---

## 🚀 Cómo ejecutarlo (XAMPP)

1. Copiar la carpeta `halloween/` dentro de `C:\xampp\htdocs\`.
2. Iniciar **Apache** y **MySQL** en el panel de XAMPP.
3. En phpMyAdmin → *Importar* → `halloween/database/halloween.sql`.
4. Abrir <http://localhost/halloween/index.html>.

> Credenciales de BD por defecto: `root` sin contraseña. Se pueden cambiar con las variables de entorno
> `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` sin modificar el código.

Alternativa sin XAMPP: `cd halloween && php -S localhost:8000` (con MySQL corriendo).

## 🧪 Pruebas

```bash
# 20 pruebas de la API REST (códigos HTTP, BCRYPT, SQLi, reglas de votación, tiempos)
php halloween/tests/test_api.php http://localhost/halloween

# Recorrido completo solo con teclado + axe-core (requiere Node)
npm i playwright axe-core && npx playwright install chromium
node halloween/tests/prueba_teclado.mjs http://localhost/halloween
```

Ambas pruebas registran un usuario y emiten un voto: conviene reimportar el `.sql` antes de la demo.

---

## 🗂️ Estructura (capas)

```
halloween/
├── index.html · login.html · registro.html   ← vistas (sin PHP, sin estilos inline)
├── css/estilos.css                           ← estilos únicos con variables de color WCAG AA
├── js/
│   ├── api.js        ← capa de acceso a la API (fetch + región aria-live)
│   ├── index.js      ← podio, listado y votación
│   └── auth.js       ← login y registro (formulario compartido)
├── img/*.svg         ← ilustraciones de los disfraces (con alt descriptivo)
├── api/
│   ├── config/conexion.php   ← PDO (capa de datos)
│   ├── lib/respuesta.php     ← JSON, códigos HTTP, sesión segura
│   ├── disfraces.php  GET    ← 200 / 405 / 500
│   ├── registro.php   POST   ← 201 / 400 / 409 / 500
│   ├── login.php      POST   ← 200 / 400 / 401 / 500
│   ├── sesion.php     GET    ← 200
│   ├── votar.php      POST   ← 200 / 400 / 401 / 404 / 409 / 500
│   └── logout.php     POST   ← 200
├── database/halloween.sql
└── tests/  test_api.php · prueba_teclado.mjs
docs/  FICHA_ISO25010.md · lighthouse/ · evidencia/
```

## ♿ Mejoras de accesibilidad aplicadas

* **Contraste:** los botones tenían texto blanco sobre naranja (2,9:1 ✗). Ahora usan texto oscuro `#121212` sobre `#ff7518` (7,0:1 ✓). Todos los pares de color superan 4,5:1.
* **Landmarks y títulos:** `<header>`, `<nav aria-label>`, `<main>` y un único `<h1>` en cada página (login y registro no tenían `<main>`).
* **Formularios:** `<label for>` en todos los campos, `autocomplete`, `aria-required`, `aria-invalid` y `aria-describedby` para la ayuda de contraseña.
* **Imágenes:** cada disfraz muestra su ilustración con `alt="Ilustración del disfraz …"`; emojis decorativos con `aria-hidden="true"`.
* **Teclado:** enlace "Saltar al contenido principal", solo elementos nativos `<a>`/`<button>`, sin `onclick` inline, `:focus-visible` con doble anillo blanco/naranja. El foco no se pierde al re-renderizar (vuelve al botón votado) ni al cerrar sesión.
* **Botones con nombre accesible único:** "Votar" + texto oculto "por Vampiro Clásico".
* **Comunicación asíncrona:** todas las respuestas de `fetch()` se inyectan con `.textContent` en regiones `role="status" aria-live="polite"`. Se eliminaron los `alert()`.
* **Movimiento reducido:** se respeta `prefers-reduced-motion`.

## 🔒 Mejoras de seguridad y fiabilidad

* Contraseñas con **BCRYPT** (`password_hash` / `password_verify`) — antes se guardaban en texto plano.
* Un solo flujo de autenticación JSON (antes había dos incompatibles).
* `session_regenerate_id()`, cookie `HttpOnly` + `SameSite=Lax`.
* 100 % *Prepared Statements*; validación de entrada con `filter_var`.
* Transacción en la votación + índice `UNIQUE` para impedir doble voto (incluso con doble clic).
* Los errores técnicos van a `error_log()`; el cliente solo recibe mensajes genéricos.

## 📋 Matriz ISO/IEC 25010 (resumen)

| Característica | Evidencia | Estado |
| :--- | :--- | :--- |
| 1. Adecuación Funcional | PHP + PDO + MySQL; flujos de registro, login, voto y logout funcionando | ✅ Cumple |
| 2. Eficiencia de Desempeño | API: 11 ms promedio / 70 ms máx. · Lighthouse Performance 100 | ✅ Cumple |
| 3. Compatibilidad | Vistas HTML/JS Vanilla desacopladas de una API REST JSON | ✅ Cumple |
| 4. Fiabilidad | `try/catch PDOException`, transacciones, códigos 200/201/400/401/404/405/409/500 | ✅ Cumple |
| 5. Seguridad (OWASP) | BCRYPT, Prepared Statements, sesión segura, sin XSS | ✅ Cumple |
| 6. Usabilidad / A11y | Lighthouse 100/100 en 3 páginas, axe 0 violaciones, 100 % teclado | ✅ Cumple |
| 7. Mantenibilidad | Capas separadas, CSS/JS únicos, tests automatizados, Git | ✅ Cumple |
