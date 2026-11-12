// Cambio de carrera para el panel de administración (no afecta admin.js).
let estudiantesCambio = [];
let carrerasCambio = [];
document.addEventListener('DOMContentLoaded', () => {
    const buscar = document.getElementById('buscar-estudiante-cambio');
    const lista = document.getElementById('select-estudiante-cambio');
    buscar?.addEventListener('input', filtrarEstudiantesCambio);
    lista?.addEventListener('change', actualizarDetalleEstudianteCambio);
    cargarDatosCambioCarrera();
});

async function cargarDatosCambioCarrera() {
    const lista = document.getElementById('select-estudiante-cambio');
    const carreras = document.getElementById('select-nueva-carrera-cambio');
    if (!lista || !carreras) return;
    try {
        const resp = await fetchSeguro('api/cambio_carrera.php', {cache: 'no-store'});
        const datos = await resp.json();
        if (!resp.ok || !datos.exito) throw new Error(datos.mensaje || 'No se pudieron cargar datos.');
        estudiantesCambio = Array.isArray(datos.estudiantes) ? datos.estudiantes : [];
        carrerasCambio = Array.isArray(datos.carreras) ? datos.carreras : [];
        carreras.replaceChildren(new Option('Selecciona una carrera...', ''));
        carrerasCambio.forEach(c => carreras.add(new Option(`${c.codigoCarrera} - ${c.nombreCarrera}`, c.idCarrera)));
        filtrarEstudiantesCambio();
    } catch (e) {
        lista.replaceChildren(new Option('Error consultando estudiantes', ''));
        console.error('Error de cambio de carrera:', e);
    }
}

function filtrarEstudiantesCambio() {
    const lista = document.getElementById('select-estudiante-cambio');
    const buscar = document.getElementById('buscar-estudiante-cambio');
    if (!lista) return;
    const seleccionado = lista.value;
    const q = (buscar?.value || '').trim().toLocaleLowerCase('es');
    const filtrados = estudiantesCambio.filter(e =>
        [e.nombreEstudiante, e.username, e.noCarnetCompleto].some(v => String(v || '').toLocaleLowerCase('es').includes(q))
    );
    lista.replaceChildren(new Option(filtrados.length ? 'Selecciona un estudiante...' : 'No se encontraron estudiantes', ''));
    filtrados.forEach(e => lista.add(new Option(`${e.nombreEstudiante} | ${e.username} | ${e.noCarnetCompleto}`, e.idUsuario)));
    if (filtrados.some(e => String(e.idUsuario) === seleccionado)) lista.value = seleccionado;
    actualizarDetalleEstudianteCambio();
}

function actualizarDetalleEstudianteCambio() {
    const id = Number(document.getElementById('select-estudiante-cambio')?.value || 0);
    const panel = document.getElementById('info-estudiante-cambio');
    const alumno = estudiantesCambio.find(e => Number(e.idUsuario) === id);
    if (!panel) return;
    panel.replaceChildren();
    panel.style.display = alumno ? 'block' : 'none';
    if (!alumno) return;
    const titulo = document.createElement('strong');
    titulo.textContent = alumno.nombreEstudiante;
    const detalle = document.createElement('p');
    detalle.textContent = `Usuario: ${alumno.username} | Carné: ${alumno.noCarnetCompleto} | Carrera actual: ${alumno.codigoCarrera} - ${alumno.nombreCarrera}`;
    panel.append(titulo, detalle);
    const carrera = document.getElementById('select-nueva-carrera-cambio');
    if (carrera) carrera.value = '';
}

async function ejecutarCambioCarrera() {
    const idUsuario = Number(document.getElementById('select-estudiante-cambio')?.value || 0);
    const idCarreraNueva = Number(document.getElementById('select-nueva-carrera-cambio')?.value || 0);
    const alumno = estudiantesCambio.find(e => Number(e.idUsuario) === idUsuario);
    const carrera = carrerasCambio.find(c => Number(c.idCarrera) === idCarreraNueva);
    if (!alumno || !carrera) return alert('Selecciona un estudiante y una carrera de destino.');
    if (Number(alumno.idCarrera) === idCarreraNueva) return alert('El estudiante ya pertenece a esa carrera.');
    if (!confirm(`¿Cambiar a ${alumno.nombreEstudiante} a ${carrera.nombreCarrera}?\nSe asignará un nuevo carné interno, se cancelarán las reservas activas y se conservará el historial.`)) return;
    try {
        const resp = await fetchSeguro('api/cambio_carrera.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({idUsuario, idCarreraNueva})
        });
        const datos = await resp.json();
        if (!resp.ok || !datos.exito) return alert(datos.mensaje || 'No se pudo completar el cambio.');
        alert(`Cambio realizado.\nCarné anterior: ${datos.carnetAnterior}\nCarné nuevo: ${datos.carnetNuevo}\nReservas canceladas: ${datos.solicitudesCanceladas}`);
        await cargarDatosCambioCarrera();
    } catch (e) {
        console.error(e);
        alert('No se pudo contactar al servidor.');
    }
}
