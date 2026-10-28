// js/login.js - Lógica Exclusiva para el Inicio de Sesión
async function procesarLogin(event) {
    if (event) event.preventDefault();

    const usuarioInput = document.getElementById('login-usuario');
    const claveInput   = document.getElementById('login-clave');

    if (!usuarioInput || !claveInput) return;

    const usuarioVal = usuarioInput.value.trim();
    const claveVal   = claveInput.value.trim();

    if (!usuarioVal || !claveVal) {
        alert('Por favor ingresa tu usuario y contraseña.');
        return;
    }

    try {
        const respuesta = await fetch('php/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario: usuarioVal, clave: claveVal })
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            window.location.reload(); // Recarga y index.php redirecciona al rol correcto
        } else {
            alert('❌ ' + (resultado.mensaje || 'Credenciales incorrectas'));
        }
    } catch (error) {
        console.error('Error en procesarLogin:', error);
        alert('Error de conexión con el servidor.');
    }
}

