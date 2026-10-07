let usuarioAutenticado = null;
function procesarLogin(event) {
    event.preventDefault();
    const correoInput = document.getElementById('login-correo');
    const rolInput = document.querySelector('input[name="rol"]:checked');

    if (!correoInput || !rolInput) return;
    const correo = correoInput.value;
    const rolSeleccionado = rolInput.value;
    usuarioAutenticado = {
        correo: correo,
        nombre: correo.split('@')[0],
        rol: rolSeleccionado
    };

    const vistaLogin = document.getElementById('vista-login');
    if (vistaLogin) vistaLogin.classList.add('oculto');

    const encabezado = document.getElementById('encabezado');
    if (encabezado) encabezado.classList.remove('oculto');

    const elemNombre = document.getElementById('info-usuario-nombre');
    const elemRol = document.getElementById('info-usuario-rol');

    if (elemNombre) elemNombre.textContent = usuarioAutenticado.nombre;
    if (elemRol) elemRol.textContent = usuarioAutenticado.rol === 'estudiante' ? 'Estudiante' : 'Tutor';

    const vistaEstudiante = document.getElementById('vista-estudiante');
    const vistaTutor = document.getElementById('vista-tutor');

    if (usuarioAutenticado.rol === 'estudiante') {
        if (vistaEstudiante) vistaEstudiante.classList.remove('oculto');
        if (vistaTutor) vistaTutor.classList.add('oculto');
    } else {
        if (vistaTutor) vistaTutor.classList.remove('oculto');
        if (vistaEstudiante) vistaEstudiante.classList.add('oculto');
    }
}

function cerrarSesion() {
    usuarioAutenticado = null;

    const vistaEstudiante = document.getElementById('vista-estudiante');
    const vistaTutor = document.getElementById('vista-tutor');
    const encabezado = document.getElementById('encabezado');

    if (vistaEstudiante) vistaEstudiante.classList.add('oculto');
    if (vistaTutor) vistaTutor.classList.add('oculto');
    if (encabezado) encabezado.classList.add('oculto');

    const vistaLogin = document.getElementById('vista-login');
    if (vistaLogin) vistaLogin.classList.remove('oculto');

    const formLogin = document.getElementById('form-login');
    if (formLogin) formLogin.reset();
}

function solicitarTutoria(materia, tutor, fecha) {
    alert(`Solicitud enviada exitosamente para la materia de "${materia}" con el tutor ${tutor}.`);
}

function cancelarSolicitud(boton) {
    if (confirm('¿Deseas cancelar esta solicitud de tutoría?')) {
        const fila = boton.closest('tr');
        if (fila) fila.remove();
    }
}

function guardarDisponibilidad(event) {
    event.preventDefault();
    alert('Nuevo espacio de disponibilidad publicado correctamente.');
    const form = document.getElementById('form-disponibilidad');
    if (form) form.reset();
}

function cambiarEstadoSolicitud(boton, nuevoEstado) {
    const celdaAcciones = boton.closest('td');
    if (!celdaAcciones) return;

    if (nuevoEstado === 'Aprobada') {
        celdaAcciones.innerHTML = `<span>Aprobada</span>`;
    } else {
        celdaAcciones.innerHTML = `<span>Rechazada</span>`;
    }
}