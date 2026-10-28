// js/admin.js - Lógica del Administrador para Asignación de Cursos a Tutores por Perfil
let listaTutoresGlobal = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarMetricsDashboard();
    cargarTutoresAdmin();
    cargarCarrerasAdmin();
    cargarCursosPorCarreraAdmin();
    cargarAsignacionesActivasAdmin();
});

async function cargarMetricsDashboard() {
    const resp = await fetch('api/dashboard.php');
    const data = await resp.json();
    if (data.exito && data.metricas) {
        document.getElementById('metric-total').innerText = data.metricas.total_solicitudes;
        document.getElementById('metric-aprobadas').innerText = data.metricas.aprobadas;
        document.getElementById('metric-pendientes').innerText = data.metricas.pendientes;
        document.getElementById('metric-tasa').innerText = data.metricas.tasa_atencion;
    }
}

async function cargarTutoresAdmin() {
    const select = document.getElementById('admin-select-tutor');
    const resp = await fetch('api/admin_asignaciones.php?accion=tutores');
    const data = await resp.json();

    if (data.exito && Array.isArray(data.datos)) {
        listaTutoresGlobal = data.datos;
        select.innerHTML = '<option value="">Selecciona un tutor docente...</option>';
        data.datos.forEach(t => {
            select.innerHTML += `<option value="${t.idTutor}">${t.nombreTutor} (${t.tituloProfesional})</option>`;
        });
    }
}

function cargarPerfilTutorSeleccionado() {
    const idTutor = document.getElementById('admin-select-tutor').value;
    const box = document.getElementById('info-perfil-tutor');
    if (!idTutor) {
        box.innerHTML = '';
        return;
    }

    const tutor = listaTutoresGlobal.find(t => parseInt(t.idTutor) === parseInt(idTutor));
    if (tutor) {
        box.innerHTML = `
            <div class="perfil-card-info">
                <strong>Perfil Docente:</strong> ${tutor.tituloProfesional}<br>
                <strong>Especialidad:</strong> ${tutor.especialidad}
            </div>
        `;
    }
}

async function cargarCarrerasAdmin() {
    const select = document.getElementById('admin-select-carrera');
    const resp = await fetch('api/admin_asignaciones.php?accion=carreras');
    const data = await resp.json();

    if (data.exito && Array.isArray(data.datos)) {
        select.innerHTML = '<option value="">Todas las Carreras</option>';
        data.datos.forEach(c => {
            select.innerHTML += `<option value="${c.idCarrera}">${c.codigoCarrera} - ${c.nombreCarrera}</option>`;
        });
    }
}

async function cargarCursosPorCarreraAdmin() {
    const idCarrera = document.getElementById('admin-select-carrera').value;
    const select = document.getElementById('admin-select-curso');

    const resp = await fetch(`api/admin_asignaciones.php?accion=cursos&idCarrera=${idCarrera}`);
    const data = await resp.json();

    select.innerHTML = '<option value="">Selecciona un curso a habilitar...</option>';
    if (data.exito && Array.isArray(data.datos)) {
        data.datos.forEach(c => {
            select.innerHTML += `<option value="${c.idCurso}">${c.codigoCurso} - ${c.nombreCurso} (${c.codigoCarrera})</option>`;
        });
    }
}

async function guardarAsignacionCurso(event) {
    if (event) event.preventDefault();

    const idTutor = document.getElementById('admin-select-tutor').value;
    const idCurso = document.getElementById('admin-select-curso').value;

    if (!idTutor || !idCurso) {
        alert('Selecciona un tutor y un curso.');
        return;
    }

    const resp = await fetch('api/admin_asignaciones.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idTutor: idTutor, idCurso: idCurso })
    });

    const data = await resp.json();
    if (data.exito) {
        alert('✅ Curso asignado al tutor exitosamente.');
        cargarAsignacionesActivasAdmin();
    } else {
        alert('❌ ' + data.mensaje);
    }
}

async function cargarAsignacionesActivasAdmin() {
    const tabla = document.getElementById('tabla-asignaciones-admin');
    const resp = await fetch('api/admin_asignaciones.php?accion=listar_asignaciones');
    const data = await resp.json();

    tabla.innerHTML = '';
    if (data.exito && Array.isArray(data.datos) && data.datos.length > 0) {
        data.datos.forEach(a => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="texto-resaltado">${a.nombreTutor}</td>
                <td>${a.codigoCurso} - ${a.nombreCurso}</td>
                <td><span class="badge-carrera-small">${a.codigoCarrera}</span></td>
                <td class="texto-derecha">
                    <button type="button" class="btn btn-peligro-suave" onclick="eliminarAsignacion(${a.idTutor}, ${a.idCurso})">Quitar</button>
                </td>
            `;
            tabla.appendChild(tr);
        });
    } else {
        tabla.innerHTML = `<tr><td colspan="4" class="texto-centro">No hay asignaciones registradas.</td></tr>`;
    }
}

async function eliminarAsignacion(idTutor, idCurso) {
    if (!confirm('¿Deseas revocar la asignación de este curso para el tutor?')) return;

    const resp = await fetch('api/admin_asignaciones.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idTutor: idTutor, idCurso: idCurso })
    });

    const data = await resp.json();
    if (data.exito) {
        alert('✅ Asignación revocada.');
        cargarAsignacionesActivasAdmin();
    }
}

async function cerrarSesion() {
    await fetch('php/logout.php');
    window.location.reload();
}