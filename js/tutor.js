// js/tutor.js - Portal Tutor con Slider Organizado de 4 Tarjetas por Vista
let idTutorSesion = 0;
let sliderTutorPosicion = 0;
let misTutoriasCache = [];

const NOMBRES_DIAS = { 1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves', 5: 'Viernes', 6: 'Sábado', 7: 'Domingo' };

document.addEventListener('DOMContentLoaded', async () => {
    const respSes = await fetch('php/session_check.php');
    const sesData = await respSes.json();
    idTutorSesion = sesData.usuario.idTutor;

    cargarCursosAsignadosTutor();
    cargarMisTutoriasPublicadas();
    cargarSolicitudesTutor();
});

function esc(texto) {
    const d = document.createElement('div');
    d.textContent = texto ?? '';
    return d.innerHTML;
}

/* ===================== CURSOS ASIGNADOS ===================== */
async function cargarCursosAsignadosTutor() {
    const select = document.getElementById('disp-curso');
    if (!select) return;

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

function limitarDiasSeleccionados() {
    const max = parseInt(document.getElementById('disp-dias-semana').value) || 1;
    const marcados = Array.from(document.querySelectorAll('.disp-dia:checked'));
    marcados.slice(max).forEach(cb => cb.checked = false);
}

function validarDiasSeleccionados(checkbox) {
    const max = parseInt(document.getElementById('disp-dias-semana').value) || 1;
    const marcados = document.querySelectorAll('.disp-dia:checked').length;
    if (marcados > max) {
        checkbox.checked = false;
        alert(`Elegiste impartir ${max} día(s) a la semana. Cambia la cantidad si necesitas más días.`);
    }
}

/* ===================== PUBLICAR ===================== */
async function guardarDisponibilidad(event) {
    if (event) event.preventDefault();

    const idCurso    = document.getElementById('disp-curso').value;
    const fecha      = document.getElementById('disp-fecha').value;
    const horaInicio = document.getElementById('disp-inicio').value;
    const duracion   = document.getElementById('disp-duracion').value;
    const cantDias   = parseInt(document.getElementById('disp-dias-semana')?.value) || 1;
    const diasSemana = Array.from(document.querySelectorAll('.disp-dia:checked')).map(cb => parseInt(cb.value));

    if (!idCurso || !fecha || !horaInicio) {
        alert('Por favor completa todos los campos.');
        return;
    }
    if (diasSemana.length === 0) {
        alert('Selecciona al menos un día de la semana.');
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
            duracionMinutos: duracion,
            diasSemana: diasSemana
        })
    });

    const data = await resp.json();
    if (data.exito) {
        alert('✅ ' + data.mensaje);
        document.getElementById('form-disponibilidad').reset();
        cargarMisTutoriasPublicadas();
    } else {
        alert('❌ ' + data.mensaje);
    }
}

/* ===================== MIS TUTORÍAS (LISTAR EN SLIDER 4 COLUMNAS) ===================== */
async function cargarMisTutoriasPublicadas() {
    const pista = document.getElementById('slider-tutor-pista');
    const contInd = document.getElementById('indicadores-tutor-pista');
    if (!pista) return;

    const resp = await fetch(`api/disponibilidad.php?idTutor=${idTutorSesion}`);
    const data = await resp.json();

    pista.innerHTML = '';
    if (contInd) contInd.innerHTML = '';
    sliderTutorPosicion = 0;
    pista.style.transform = 'translateX(0)';

    if (data.exito && Array.isArray(data.datos) && data.datos.length > 0) {
        misTutoriasCache = data.datos;
        data.datos.forEach(item => {
            const div = document.createElement('div');
            div.className = 'tarjeta-carrusel-item';

            // Días de la semana
            const diasTexto = item.diasSemana || NOMBRES_DIAS[new Date(item.fecha + 'T00:00:00').getDay() || 7];

            div.innerHTML = `
                <div class="tarjeta-carrusel-contenido">
                    <div>
                        <span class="etiqueta-materia">${esc(item.nombreMateria)}</span>
                        <div class="info-detalle-linea"><i class="fa-solid fa-calendar-days"></i> <strong>Días:</strong> ${esc(diasTexto)}</div>
                        <div class="info-detalle-linea"><i class="fa-regular fa-calendar"></i> <strong>Inicio:</strong> ${esc(item.fecha)}</div>
                        <div class="info-detalle-linea"><i class="fa-solid fa-clock"></i> <strong>Horario:</strong> ${esc(item.horaInicio.substring(0,5))} - ${esc(item.horaFin.substring(0,5))}</div>
                        <div class="info-detalle-linea"><i class="fa-solid fa-users"></i> <strong>Inscritos:</strong> ${item.cuposOcupados || 0} / 30 alumnos</div>
                    </div>
                    <div class="mt-sm acciones-tarjeta" style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                        <button type="button" class="btn btn-exito-suave" style="flex:1;" onclick="abrirModalEditar(${item.idDisponibilidad})">
                            <i class="fa-solid fa-pen"></i> Editar
                        </button>
                        <button type="button" class="btn btn-peligro-suave" style="flex:1;" onclick="eliminarTutoria(${item.idDisponibilidad})">
                            <i class="fa-solid fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            `;
            pista.appendChild(div);
        });

        // Generar Indicadores
        if (contInd) {
            const porVista = obtenerTarjetasPorVistaTutor();
            const totalPaginas = Math.ceil(data.datos.length / porVista);
            for (let i = 0; i < totalPaginas; i++) {
                const punto = document.createElement('button');
                punto.className = `punto-indicador ${i === 0 ? 'activo' : ''}`;
                punto.onclick = () => irASlideTutor(i);
                contInd.appendChild(punto);
            }
        }
    } else {
        misTutoriasCache = [];
        pista.innerHTML = `<p class="subtitulo-seccion p-sm">No tienes tutorías activas actualmente.</p>`;
    }
}

function obtenerTarjetasPorVistaTutor() {
    if (window.innerWidth <= 550) return 1;
    if (window.innerWidth <= 850) return 2;
    if (window.innerWidth <= 1100) return 3;
    return 4; // 4 tarjetas por vista en escritorio
}

function moverSliderTutor(dir) {
    const pista = document.getElementById('slider-tutor-pista');
    if (!pista) return;

    const totalItems = pista.children.length;
    const porVista   = obtenerTarjetasPorVistaTutor();
    const maxIndice  = Math.max(0, Math.ceil(totalItems / porVista) - 1);

    sliderTutorPosicion += dir;
    if (sliderTutorPosicion < 0) sliderTutorPosicion = maxIndice;
    if (sliderTutorPosicion > maxIndice) sliderTutorPosicion = 0;

    actualizarSliderTutor();
}

function irASlideTutor(idx) {
    sliderTutorPosicion = idx;
    actualizarSliderTutor();
}

function actualizarSliderTutor() {
    const pista = document.getElementById('slider-tutor-pista');
    if (!pista) return;

    const porcentaje = sliderTutorPosicion * 100;
    pista.style.transform = `translateX(-${porcentaje}%)`;

    const puntos = document.querySelectorAll('#indicadores-tutor-pista .punto-indicador');
    puntos.forEach((p, idx) => {
        if (idx === sliderTutorPosicion) p.classList.add('activo');
        else p.classList.remove('activo');
    });
}

/* ===================== EDITAR / ELIMINAR / SOLICITUDES ===================== */
function abrirModalEditar(idDisp) {
    const item = misTutoriasCache.find(t => parseInt(t.idDisponibilidad) === parseInt(idDisp));
    if (!item) return;

    cerrarModalEditar();

    const overlay = document.createElement('div');
    overlay.id = 'modal-editar-tutoria';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;z-index:1000;padding:1rem;';
    overlay.innerHTML = `
        <div class="tarjeta" style="max-width:420px;width:100%;margin:0;">
            <h3 style="margin-bottom:0.25rem;">Modificar tutoría</h3>
            <p class="subtitulo-seccion" style="margin-bottom:1rem;">${esc(item.nombreMateria)}</p>
            <div class="grupo-campo">
                <label class="etiqueta-campo" for="edit-fecha">Fecha</label>
                <input type="date" id="edit-fecha" class="campo-entrada" value="${esc(item.fecha)}">
            </div>
            <div class="grupo-campo">
                <label class="etiqueta-campo" for="edit-inicio">Hora de inicio</label>
                <input type="time" id="edit-inicio" class="campo-entrada" value="${esc(item.horaInicio.substring(0, 5))}">
            </div>
            <div class="grupo-campo">
                <label class="etiqueta-campo" for="edit-duracion">Duración (minutos)</label>
                <input type="number" id="edit-duracion" class="campo-entrada" min="15" max="240" step="5" value="${parseInt(item.duracionMinutos) || 45}">
            </div>
            <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                <button type="button" class="btn btn-peligro-suave" onclick="cerrarModalEditar()">Cancelar</button>
                <button type="button" class="btn btn-oro" onclick="guardarEdicionTutoria(${item.idDisponibilidad})">Guardar cambios</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
}

function cerrarModalEditar() {
    const m = document.getElementById('modal-editar-tutoria');
    if (m) m.remove();
}

async function guardarEdicionTutoria(idDisp) {
    const fecha      = document.getElementById('edit-fecha').value;
    const horaInicio = document.getElementById('edit-inicio').value;
    const duracion   = parseInt(document.getElementById('edit-duracion').value);

    if (!fecha || !horaInicio || !duracion) {
        alert('Completa todos los campos.');
        return;
    }

    const resp = await fetch('api/disponibilidad.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idDisponibilidad: idDisp, fecha, horaInicio, duracionMinutos: duracion })
    });
    const data = await resp.json();

    if (data.exito) {
        alert('✅ ' + data.mensaje);
        cerrarModalEditar();
        cargarMisTutoriasPublicadas();
        cargarSolicitudesTutor();
    } else {
        alert('❌ ' + data.mensaje);
    }
}

async function eliminarTutoria(idDisp) {
    if (!confirm('¿Seguro que deseas eliminar esta tutoría?')) return;

    let resp = await fetch('api/disponibilidad.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idDisponibilidad: idDisp })
    });
    let data = await resp.json();

    if (!data.exito && data.requiereConfirmacion) {
        if (!confirm(`⚠️ ${data.mensaje}
Si la eliminas, sus solicitudes también se borrarán. ¿Continuar?`)) return;

        resp = await fetch('api/disponibilidad.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idDisponibilidad: idDisp, forzar: true })
        });
        data = await resp.json();
    }

    if (data.exito) {
        alert('✅ ' + data.mensaje);
        cargarMisTutoriasPublicadas();
        cargarSolicitudesTutor();
    } else {
        alert('❌ ' + data.mensaje);
    }
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
                <td class="texto-resaltado">${esc(s.nombreEstudiante || s.noCarnetEstudiante)}</td>
                <td>${esc(s.nombreMateria)}</td>
                <td>${esc(s.fecha)} - ${esc(s.horaInicio)}</td>
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