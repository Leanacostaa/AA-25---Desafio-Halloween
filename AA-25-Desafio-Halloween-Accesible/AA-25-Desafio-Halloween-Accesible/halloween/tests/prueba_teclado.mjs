// Prueba E2E solo con teclado (Tab / Shift+Tab / Enter / escritura) + axe-core en cada estado.
// Requisitos:  npm i playwright axe-core  &&  npx playwright install chromium
// Uso:         node tests/prueba_teclado.mjs http://localhost/halloween
// (usar una base recién importada: la prueba registra un usuario y emite un voto)
import { chromium } from 'playwright';
import fs from 'fs';
import { createRequire } from 'module';
const require = createRequire(import.meta.url);

const BASE = process.argv[2] || 'http://127.0.0.1:8002';
const OUT = process.argv[3] || './evidencia';
fs.mkdirSync(OUT, { recursive: true });
const axeSrc = fs.readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');

const browser = await chromium.launch();
const page = await (await browser.newContext({ viewport: { width: 1280, height: 800 } })).newPage();
const log = [];
const axeResultados = {};

async function foco() {
  return page.evaluate(() => {
    const el = document.activeElement;
    if (!el || el === document.body) return '(body)';
    const tag = el.tagName.toLowerCase();
    const txt = (el.innerText || el.getAttribute('aria-label') || el.labels?.[0]?.innerText || el.alt || '').trim().replace(/\s+/g, ' ');
    const outline = getComputedStyle(el).outlineStyle !== 'none';
    return `<${tag}> ${txt}${outline ? '  [foco visible ✓]' : '  [SIN FOCO VISIBLE]'}`;
  });
}
async function tab(n = 1, shift = false) {
  for (let i = 0; i < n; i++) { await page.keyboard.press(shift ? 'Shift+Tab' : 'Tab'); }
  return foco();
}
async function axe(nombre) {
  await page.addScriptTag({ content: axeSrc });
  const r = await page.evaluate(async () => {
    const res = await axe.run(document, { runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice'] });
    return res.violations.map(v => `${v.id}: ${v.nodes.length} nodos`);
  });
  axeResultados[nombre] = r;
  log.push(`   axe-core (${nombre}): ${r.length === 0 ? '0 violaciones WCAG 2.1 AA' : r.join(', ')}`);
}
async function textoAviso(sel) {
  await page.waitForFunction(s => document.querySelector(s).textContent.trim().length > 0, sel);
  return page.locator(sel).textContent();
}

// ---------- 1. Orden de tabulación en la portada ----------
await page.goto(`${BASE}/index.html`);
await page.waitForSelector('#contenedor-disfraces button');
log.push('1) ORDEN DE TABULACIÓN EN index.html (sin sesión)');
for (let i = 1; i <= 9; i++) {
  log.push(`   Tab ${i}: ${await tab()}`);
  if (i === 1) await page.screenshot({ path: `${OUT}/01_skip_link.png` });
  if (i === 2) await page.screenshot({ path: `${OUT}/02_foco_marca.png` });
}
await axe('portada sin sesión');

// Saltar al contenido con Enter
await page.goto(`${BASE}/index.html`);
await page.waitForSelector('#contenedor-disfraces button');
await tab(); await page.keyboard.press('Enter');
log.push(`   Enter en "Saltar al contenido" -> foco en: ${await page.evaluate(() => document.activeElement.id)}; siguiente Tab: ${await tab()}`);
await page.screenshot({ path: `${OUT}/03_foco_boton_votar.png` });

// ---------- 2. Votar sin sesión (Enter) ----------
await page.keyboard.press('Enter');
log.push('2) VOTAR SIN SESIÓN (Enter sobre "Votar")');
log.push(`   aria-live anuncia: "${await textoAviso('#aviso')}"`);
log.push(`   El foco permanece en: ${await foco()}`);
await page.screenshot({ path: `${OUT}/04_aviso_sin_sesion.png` });
await axe('portada con aviso de error');

// ---------- 3. Registro solo con teclado ----------
log.push('3) REGISTRO SOLO CON TECLADO');
await page.goto(`${BASE}/index.html`);
await page.waitForSelector('#contenedor-disfraces button');
log.push(`   Tab x4: ${await tab(4)} -> Enter`);
await page.keyboard.press('Enter');
await page.waitForURL('**/registro.html');
// Envío vacío: validación accesible
await tab(5); // skip, marca, nombre, email, clave
log.push(`   Tab hasta: ${await foco()}`);
log.push(`   Tab: ${await tab()} -> Enter con el formulario vacío`);
await page.keyboard.press('Enter');
log.push(`   aria-live anuncia: "${await textoAviso('#mensaje-form')}"; foco movido a: ${await foco()}`);
await page.screenshot({ path: `${OUT}/05_validacion_registro.png` });
await axe('registro con error de validación');
await page.keyboard.type('Grupo Halloween');
await page.keyboard.press('Tab'); await page.keyboard.type('grupo@halloween.test');
await page.keyboard.press('Tab'); await page.keyboard.type('Calabaza2026');
await page.keyboard.press('Enter');
await page.waitForFunction(() => /Registro exitoso/.test(document.querySelector('#mensaje-form').textContent));
log.push(`   aria-live anuncia: "${await page.locator('#mensaje-form').textContent()}"`);
await page.screenshot({ path: `${OUT}/06_registro_ok.png` });
await page.waitForURL('**/login.html');

// ---------- 4. Login con teclado ----------
log.push('4) LOGIN SOLO CON TECLADO');
log.push(`   Tab x3: ${await tab(3)}`);
await page.keyboard.type('grupo@halloween.test');
await page.keyboard.press('Tab'); await page.keyboard.type('incorrecta'); await page.keyboard.press('Enter');
await page.waitForFunction(() => /incorrectos/.test(document.querySelector('#mensaje-form').textContent));
log.push(`   Contraseña errónea + Enter -> aria-live: "${await page.locator('#mensaje-form').textContent()}" (HTTP 401)`);
await page.screenshot({ path: `${OUT}/07_login_error.png` });
await axe('login con error');
await tab(1, true); // volver al campo contraseña
await page.keyboard.press('Control+A'); await page.keyboard.type('Calabaza2026'); await page.keyboard.press('Enter');
await page.waitForURL('**/index.html');
log.push('   Contraseña correcta + Enter -> redirige a index.html');

// ---------- 5. Votar con sesión ----------
log.push('5) VOTAR CON SESIÓN INICIADA');
await page.waitForSelector('#contenedor-disfraces button');
await tab(); await page.keyboard.press('Enter'); // saltar al contenido
const botonTxt = await tab(2);
log.push(`   Saltar contenido + Tab x2: ${botonTxt} -> Enter`);
await page.keyboard.press('Enter');
await page.waitForFunction(() => /Voto registrado/.test(document.querySelector('#aviso').textContent));
log.push(`   aria-live anuncia: "${await page.locator('#aviso').textContent()}"`);
log.push(`   Tras re-renderizar, el foco vuelve a: ${await foco()}`);
await page.screenshot({ path: `${OUT}/08_voto_ok.png` });
await axe('portada con sesión y voto');
await tab(1); await page.keyboard.press('Enter');
await page.waitForFunction(() => /Ya emitiste/.test(document.querySelector('#aviso').textContent));
log.push(`   Segundo voto -> aria-live: "${await page.locator('#aviso').textContent()}" (HTTP 409)`);

// ---------- 6. Cerrar sesión ----------
await page.goto(`${BASE}/index.html`);
await page.waitForSelector('#contenedor-disfraces button');
log.push('6) CERRAR SESIÓN');
log.push(`   Tab x3: ${await tab(3)} -> Enter`);
await page.keyboard.press('Enter');
await page.waitForFunction(() => /Cerraste/.test(document.querySelector('#aviso').textContent));
log.push(`   aria-live: "${await page.locator('#aviso').textContent()}"; foco pasa a: ${await foco()}`);

await page.setViewportSize({ width: 1280, height: 800 });
await page.goto(`${BASE}/index.html`);
await page.waitForSelector('#contenedor-disfraces button');
await page.screenshot({ path: `${OUT}/00_portada_completa.png`, fullPage: true });

fs.writeFileSync(`${OUT}/prueba_teclado.txt`, log.join('\n') + '\n');
fs.writeFileSync(`${OUT}/axe.json`, JSON.stringify(axeResultados, null, 2));
console.log(log.join('\n'));
await browser.close();
