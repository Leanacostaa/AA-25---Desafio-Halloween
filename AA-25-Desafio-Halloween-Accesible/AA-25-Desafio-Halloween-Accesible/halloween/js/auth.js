/**
 * Lógica compartida de login.html y registro.html.
 * El endpoint y la página de destino se leen de los atributos data-* del formulario.
 */
import { pedirApi, anunciar } from './api.js';

const form = document.getElementById('form-auth');
const mensaje = document.getElementById('mensaje-form');
const boton = form.querySelector('button[type="submit"]');

function validar() {
    // Validación en el cliente con mensajes accesibles (la validación real está en PHP)
    for (const input of form.querySelectorAll('input')) {
        input.removeAttribute('aria-invalid');
    }
    for (const input of form.querySelectorAll('input')) {
        if (!input.checkValidity()) {
            input.setAttribute('aria-invalid', 'true');
            const etiqueta = form.querySelector(`label[for="${input.id}"]`).textContent;
            let detalle = 'es obligatorio';
            if (input.validity.typeMismatch) detalle = 'no tiene un formato válido';
            if (input.validity.tooShort) detalle = `debe tener al menos ${input.minLength} caracteres`;
            anunciar(mensaje, `El campo "${etiqueta}" ${detalle}.`, 'error');
            input.focus();
            return null;
        }
    }
    return Object.fromEntries(new FormData(form));
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const datos = validar();
    if (!datos) return;

    boton.disabled = true;
    anunciar(mensaje, 'Enviando…', 'ok');

    const r = await pedirApi(form.dataset.endpoint, { metodo: 'POST', datos });
    boton.disabled = false;

    if (r.ok) {
        const texto = r.datos.mensaje || `¡Bienvenido, ${r.datos.usuario}! Redirigiendo…`;
        anunciar(mensaje, texto, 'ok');
        setTimeout(() => { window.location.href = form.dataset.destino; }, 1500);
    } else {
        anunciar(mensaje, r.datos.error || 'No se pudo completar la operación.', 'error');
        boton.focus();
    }
});
