// js/tutor.js - Lógica Exclusiva del Portal Tutor (Cursos Asignados por Admin + Slider + Solicitudes)
let idTutorSesion = 0;
let sliderTutorPosicion = 0;

document.addEventListener('DOMContentLoaded', async () => {
    const respSes = await fetch('php/session_check.php');
    const sesData = await respSes.json();
    idTutorSesion = sesData.usuario.idTutor;

    cargarCursosAsignadosTutor();
    cargarMisTutoriasPublicadas();
    cargarSolicitudesTutor();
});

async function cargarCursosAsignadosTutor() {
    const select = document.getElementById('disp-curso');
    if (!select) return;

    // Obtener asignaciones hechas por el Administrador
    const resp = await fetch('api/admin_asignaciones.php');
    const data = await resp.json();

    select.innerHTML = '<option value="">Selecciona un curso de tu lista autorizada...</option>';

    if (data.exito && Array.isArray(data.datos)) {
        const misCursos = data.datos.filter(c => parseInt(c.idTutor) === parseInt(idTutorSesion));
        
        if (misCursos.length > 0) {
            misCursos.forEach(c => {
                select.innerHTML += `<option value="${c.idCurso}">${c.codigoCurso} - ${c.nombreCurso} (${c.nombreCarrera})</option>`;
            });
        } else {
            select.innerHTML = '<option value="">❌ El Administrador aún no te ha asignado cursos.</option>';
        }
    }
}

async function guardarDisponibilidad(event) {
    if (event) event.preventDefault();

    const idCurso    = document.getElementById('disp-curso').value;
    const fecha      = document.getElementById('disp-fecha').value;
    const horaInicio = document.getElementById('disp-inicio').value;
    const duracion   = document.getElementById('disp-duracion').value;

    if (!idCurso || !fecha || !horaInicio) {
        alert('Por favor completa todos los campos.');
        return;
    }

    const resp = await fetch('api/disponibilidad.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            idTutor: idTutorSesion,
            idCurso: idCurso,
            fecha: fecha,
            horaInicio: horaInicio,
            duracionMinutos: duracion
        })
    });

    const data = await resp.json();
    if (data.exito) {
        alert('✅ Horario de tutoría publicado exitosamente.');
        document.getElementById('form-disponibilidad').reset();
        cargarMisTutoriasPublicadas();
    } else {
        alert('❌ ' + data.mensaje);
    }
}

async function cargarMisTutoriasPublicadas() {
    const pista = document.getElementById('slider-tutor-pista');
    if (!pista) return;

    const resp = await fetch(`api/disponibilidad.php?idTutor=${idTutorSesion}`);
    const data = await resp.json();

    pista.innerHTML = '';

    if (data.exito && Array.isArray(data.datos) && data.datos.length > 0) {
        data.datos.forEach(item => {
            const div = document.createElement('div');
            div.className = 'tarjeta-carrusel-item';
            div.innerHTML = `
                <div class="tarjeta-carrusel-contenido">
                    <span class="etiqueta-materia">${item.nombreMateria}</span>
                    <p class="mt-xs"><strong>Fecha:</strong> ${item.fecha}</p>
                    <p><strong>Horario:</strong> ${item.horaInicio} - ${item.horaFin}</p>
                    <p><strong>Inscriptos:</strong> ${item.cuposOcupados || 0} / 30 alumnos</p>
                </div>
            `;
            pista.appendChild(div);
        });
    } else {
        pista.innerHTML = `<p class="subtitulo-seccion p-sm">No tienes tutorías activas actualmente.</p>`;
    }
}

function moverSliderTutor(dir) {
    const pista = document.getElementById('slider-tutor-pista');
    if (!pista) return;

    const max = Math.max(0, pista.children.length - 1);
    sliderTutorPosicion += dir;
    if (sliderTutorPosicion < 0) sliderTutorPosicion = max;
    if (sliderTutorPosicion > max) sliderTutorPosicion = 0;

    pista.style.transform = `translateX(-${sliderTutorPosicion * 100}%)`;
}

async function cargarSolicitudesTutor() {
    const resp = await fetch(`api/solicitudes.php?idTutor=${idTutorSesion}`);
    const data = await resp.json();

    const tabla = document.getElementById('tabla-solicitudes-tutor');
    if (!tabla) return;
    tabla.innerHTML = '';

    if (data.exito && Array.isArray(data.datos) && data.datos.length > 0) {
        data.datos.forEach(s => {
            const tr = document.createElement('tr');
            let acciones = s.estadoSolicitud === 'Pendiente' ? `
                <button type="button" class="btn btn-exito-suave" onclick="cambiarEstado(${s.idSolicitud}, 'Aprobada')">Aceptar</button>
                <button type="button" class="btn btn-peligro-suave" onclick="cambiarEstado(${s.idSolicitud}, 'Rechazada')">Rechazar</button>
            ` : `<span class="estado-badge estado-${s.estadoSolicitud.toLowerCase()}">${s.estadoSolicitud}</span>`;

            tr.innerHTML = `
                <td class="texto-resaltado">${s.nombreEstudiante || s.noCarnetEstudiante}</td>
                <td>${s.nombreMateria}</td>
                <td>${s.fecha} - ${s.horaInicio}</td>
                <td class="texto-centro">${acciones}</td>
            `;
            tabla.appendChild(tr);
        });
    } else {
        tabla.innerHTML = `<tr><td colspan="4" class="texto-centro">No hay solicitudes recibidas.</td></tr>`;
    }
}

async function cambiarEstado(idSol, estado) {
    if (!confirm(`¿Estás seguro de cambiar el estado a '${estado}'?`)) return;

    const resp = await fetch('api/solicitudes.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idSolicitud: idSol, estadoSolicitud: estado })
    });
    const data = await resp.json();

    if (data.exito) {
        alert('✅ Estado actualizado.');
        cargarSolicitudesTutor();
    }
}

async function cerrarSesion() {
    await fetch('php/logout.php');
    window.location.reload();
}
