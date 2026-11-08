// Mantiene viva la sesión SOLO cuando existe actividad del usuario.
// Los fetch automáticos de tutor.js / estudiante.js NO renuevan la sesión.
(() => {
    const URL = 'php/session_check.php';
    const LIMITE = 15 * 60 * 1000;
    const INTERVALO_ENVIO = 60 * 1000;
    let ultimaInteraccion = Date.now();
    let ultimoEnvio = Date.now();
    let expirando = false;

    function salirPorInactividad() {
        if (expirando) return;
        expirando = true;
        alert('Tu sesión terminó después de 15 minutos sin actividad. Inicia sesión nuevamente.');
        window.location.href = 'index.php';
    }

    async function enviarActividad() {
        if (expirando) return;
        if (Date.now() - ultimaInteraccion >= LIMITE) return salirPorInactividad();
        try {
            const r = await fetch(URL, {method: 'POST', credentials: 'same-origin', cache: 'no-store'});
            const datos = await r.json();
            if (r.status === 401 || !datos.autenticado) salirPorInactividad();
        } catch (error) {
            console.warn('No se pudo verificar la sesión:', error);
        }
    }

    function marcarActividad() {
        if (expirando) return;
        const ahora = Date.now();
        if (ahora - ultimaInteraccion >= LIMITE) return salirPorInactividad();
        ultimaInteraccion = ahora;
        if (ahora - ultimoEnvio >= INTERVALO_ENVIO) {
            ultimoEnvio = ahora;
            enviarActividad();
        }
    }
    ['pointerdown', 'keydown', 'touchstart', 'wheel'].forEach(evento => {
        document.addEventListener(evento, marcarActividad, {passive: true});
    });
    // El intervalo SOLO verifica expiración; nunca envía actividad por sí mismo.
    setInterval(() => {
        if (Date.now() - ultimaInteraccion >= LIMITE) salirPorInactividad();
    }, 10000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && Date.now() - ultimaInteraccion >= LIMITE) salirPorInactividad();
    });
})();
