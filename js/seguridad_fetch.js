// Token CSRF sincronizado con la sesión PHP. No modifica el diseño.
function fetchSeguro(url, opciones = {}) {
    const metodo = String(opciones.method || 'GET').toUpperCase();
    const cabeceras = new Headers(opciones.headers || {});
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(metodo)) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) return Promise.reject(new Error('Falta el token CSRF; recarga la página.'));
        cabeceras.set('X-CSRF-Token', token);
    }
    return fetch(url, { ...opciones, method: metodo, headers: cabeceras, credentials: 'same-origin' });
}
