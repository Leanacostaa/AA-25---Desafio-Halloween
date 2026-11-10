/**
 * Vista principal: podio, listado de disfraces y votación.
 * El DOM se construye con createElement + textContent (sin innerHTML).
 */
import { pedirApi, anunciar } from './api.js';

const contenedorTop = document.getElementById('contenedor-top');
const contenedorDisfraces = document.getElementById('contenedor-disfraces');
const navCuenta = document.getElementById('nav-cuenta');
const aviso = document.getElementById('aviso');

let sesion = { autenticado: false, usuario: null, voto_por: null };
let ocultarAviso;

function crear(etiqueta, { clase, texto, attrs = {} } = {}) {
    const el = document.createElement(etiqueta);
    if (clase) el.className = clase;
    if (texto !== undefined) el.textContent = texto;
    for (const [k, v] of Object.entries(attrs)) el.setAttribute(k, v);
    return el;
}

function mostrarAviso(mensaje, tipo) {
    clearTimeout(ocultarAviso);
    anunciar(aviso, mensaje, tipo);
    ocultarAviso = setTimeout(() => { aviso.textContent = ''; }, 8000);
}

function crearImagen(d) {
    if (!d.foto) return null;
    return crear('img', {
        attrs: { src: d.foto, alt: `Ilustración del disfraz ${d.nombre}`, width: '96', height: '96', loading: 'lazy' },
    });
}

/* ---------- Navegación de cuenta ---------- */
function renderNav() {
    navCuenta.replaceChildren();
    if (sesion.autenticado) {
        const saludo = crear('li', { clase: 'saludo', texto: `Hola, ${sesion.usuario}` });
        const li = crear('li');
        const salir = crear('button', { clase: 'btn btn-secundario', texto: 'Cerrar sesión', attrs: { type: 'button' } });
        salir.addEventListener('click', cerrarSesion);
        li.append(salir);
        navCuenta.append(saludo, li);
    } else {
        for (const [href, texto] of [['login.html', 'Iniciar sesión'], ['registro.html', 'Registrarse']]) {
            const li = crear('li');
            li.append(crear('a', { clase: 'btn btn-secundario', texto, attrs: { href } }));
            navCuenta.append(li);
        }
    }
}

async function cerrarSesion() {
    const r = await pedirApi('api/logout.php', { metodo: 'POST' });
    if (r.ok) {
        sesion = { autenticado: false, usuario: null, voto_por: null };
        renderNav();
        await cargarDisfraces();
        navCuenta.querySelector('a')?.focus(); // el foco no se pierde al desaparecer el botón
        mostrarAviso('Cerraste sesión correctamente.', 'ok');
    } else {
        mostrarAviso(r.datos.error || 'No se pudo cerrar la sesión.', 'error');
    }
}

/* ---------- Podio y listado ---------- */
function renderPodio(disfraces) {
    const medallas = ['🥇', '🥈', '🥉'];
    const puestos = ['Primer puesto', 'Segundo puesto', 'Tercer puesto'];
    contenedorTop.replaceChildren();

    [...disfraces].sort((a, b) => b.votos - a.votos).slice(0, 3).forEach((d, i) => {
        const li = crear('li', { clase: 'podio-card' });
        const puesto = crear('span', { clase: 'puesto' });
        puesto.append(crear('span', { texto: medallas[i], attrs: { 'aria-hidden': 'true' } }),
                      crear('span', { clase: 'sr-only', texto: puestos[i] }));
        const img = crearImagen(d);
        li.append(puesto);
        if (img) li.append(img);
        li.append(crear('h3', { texto: d.nombre }), crear('p', { texto: `${d.votos} ${d.votos === 1 ? 'voto' : 'votos'}` }));
        contenedorTop.append(li);
    });
}

function renderListado(disfraces) {
    contenedorDisfraces.replaceChildren();

    disfraces.forEach(d => {
        const yaVotoEste = sesion.voto_por === d.id;
        const li = crear('li', { clase: yaVotoEste ? 'card votado' : 'card' });
        const img = crearImagen(d);
        if (img) li.append(img);
        li.append(
            crear('h3', { texto: d.nombre }),
            crear('p', { texto: d.descripcion }),
            crear('p', { clase: 'votos', texto: `Votos: ${d.votos}` })
        );
        if (yaVotoEste) li.append(crear('p', { clase: 'etiqueta-votado', texto: '✔ Tu voto' }));

        // Botón nativo: enfocable con Tab y activable con Enter/Espacio
        const boton = crear('button', { attrs: { type: 'button', 'data-id': String(d.id) } });
        boton.append(document.createTextNode('Votar'), crear('span', { clase: 'sr-only', texto: ` por ${d.nombre}` }));
        boton.addEventListener('click', () => votar(d.id, d.nombre));
        li.append(boton);

        contenedorDisfraces.append(li);
    });
}

async function cargarDisfraces() {
    contenedorTop.setAttribute('aria-busy', 'true');
    contenedorDisfraces.setAttribute('aria-busy', 'true');

    const r = await pedirApi('api/disfraces.php');

    contenedorTop.setAttribute('aria-busy', 'false');
    contenedorDisfraces.setAttribute('aria-busy', 'false');

    if (!r.ok) {
        const msg = r.datos.error || 'Error al cargar los disfraces.';
        contenedorDisfraces.replaceChildren(crear('li', { clase: 'cargando', texto: msg }));
        contenedorTop.replaceChildren();
        mostrarAviso(msg, 'error');
        return;
    }
    renderPodio(r.datos);
    renderListado(r.datos);
}

/* ---------- Votación ---------- */
async function votar(disfrazId, nombre) {
    const r = await pedirApi('api/votar.php', { metodo: 'POST', datos: { disfraz_id: disfrazId } });

    if (r.ok) {
        sesion.voto_por = disfrazId;
        await cargarDisfraces();
        // Se devuelve el foco al botón del mismo disfraz (el DOM se regeneró)
        const boton = contenedorDisfraces.querySelector(`button[data-id="${disfrazId}"]`);
        boton?.focus({ preventScroll: true });
        boton?.scrollIntoView({ block: 'center', behavior: 'instant' }); // el aviso flotante nunca tapa el foco
        mostrarAviso(r.datos.mensaje || `Voto registrado para ${nombre}.`, 'ok');
        return;
    }
    const msg = r.estado === 401
        ? 'Tenés que iniciar sesión para votar. Usá el enlace "Iniciar sesión" del encabezado.'
        : (r.datos.error || 'Ocurrió un error al registrar el voto.');
    mostrarAviso(msg, 'error');
}

/* ---------- Inicio ---------- */
async function iniciar() {
    const r = await pedirApi('api/sesion.php');
    if (r.ok) sesion = r.datos;
    renderNav();
    await cargarDisfraces();
}

iniciar();
