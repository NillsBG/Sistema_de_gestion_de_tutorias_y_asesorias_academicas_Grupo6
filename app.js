let usuarioAutenticado = null;
function procesarLogin(event) {
    event.preventDefault();

    const correo = document.getElementById('login-correo').value;
    const rolSeleccionado = document.querySelector('input[name="rol"]:checked').value;

    // Guardamos datos temporales para la sesión
    usuarioAutenticado = {
        correo: correo,
        nombre: correo.split('@')[0],
        rol: rolSeleccionado
    };
    // Ocultamos el Login y mostramos la barra superior 
    document.getElementById('vista-login').classList.add('oculto');
    document.getElementById('barra-usuario').classList.remove('oculto');

    // Actualizamos el nombre y rol en pantalla
    document.getElementById('info-usuario-nombre').textContent = usuarioAutenticado.nombre;
    document.getElementById('info-usuario-rol').textContent = usuarioAutenticado.rol === 'estudiante' ? 'Estudiante' : 'Tutor';

    // Redirigimos a la vista según el rol seleccionado 
    if (usuarioAutenticado.rol === 'estudiante') {
        document.getElementById('vista-estudiante').classList.remove('oculto');
    } else {
        document.getElementById('vista-tutor').classList.remove('oculto');
    }
}
function cerrarSesion() {
    usuarioAutenticado = null;
    document.getElementById('vista-estudiante').classList.add('oculto');
    document.getElementById('vista-tutor').classList.add('oculto');
    document.getElementById('barra-usuario').classList.add('oculto');
    document.getElementById('vista-login').classList.remove('oculto');
    document.getElementById('form-login').reset();
}
function solicitarTutoria(materia, tutor, fecha) {
    alert(`Solicitud enviada exitosamente para la materia de "${materia}" con el tutor ${tutor}.`);
}
function cancelarSolicitud(boton) {
    if (confirm('¿Deseas cancelar esta solicitud de tutoría?')) {
        boton.closest('tr').remove();
    }
}
function guardarDisponibilidad(event) {
    event.preventDefault();
    alert('Nuevo espacio de disponibilidad publicado correctamente.');
    document.getElementById('form-disponibilidad').reset();
}
function cambiarEstadoSolicitud(boton, nuevoEstado) {
    const celdaAcciones = boton.closest('td');
    if (nuevoEstado === 'Aprobada') {
        celdaAcciones.innerHTML = `<span>Aprobada</span>`;
    } else {
        celdaAcciones.innerHTML = `<span>Rechazada</span>`;
    }
}
