
/*
 * js/tutor.js
 * Portal del Tutor - Sistema de Gestión de Tutorías
 * Corrección: guardar y mostrar todos los días seleccionados.
 */

let idTutorSesion = 0;
let sliderTutorPosicion = 0;
let misTutoriasCache = [];

const NOMBRES_DIAS = {
    1: 'Lunes',
    2: 'Martes',
    3: 'Miércoles',
    4: 'Jueves',
    5: 'Viernes',
    6: 'Sábado',
    7: 'Domingo'
};

/* ===================================================
   INICIALIZACIÓN DEL PORTAL
=================================================== */

document.addEventListener('DOMContentLoaded', async () => {

    try {
        const respSes = await fetch('php/session_check.php');
        const sesData = await respSes.json();

        if (!sesData.autenticado || !sesData.usuario) {
            window.location.reload();
            return;
        }

        idTutorSesion = Number(sesData.usuario.idTutor || 0);

        if (idTutorSesion <= 0) {
            console.error('No se encontró el tutor de la sesión.');
            return;
        }

        await Promise.all([
            cargarCursosAsignadosTutor(),
            cargarMisTutoriasPublicadas(),
            cargarSolicitudesTutor()
        ]);

        document.querySelectorAll('.disp-dia').forEach(cb => {
            cb.addEventListener('change', recalcularFechasAuto);
        });

        recalcularFechasAuto();

        const actualizarTutorias = () => {

            if (document.visibilityState === 'hidden') {
                return;
            }

            cargarMisTutoriasPublicadas();
            cargarSolicitudesTutor();
        };

        setInterval(actualizarTutorias, 15000);

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                actualizarTutorias();
            }
        });

    } catch (error) {
        console.error('Error al iniciar el portal del tutor:', error);
    }
});

/* ===================================================
   FUNCIONES AUXILIARES
=================================================== */

function esc(texto) {
    const elemento = document.createElement('div');
    elemento.textContent = texto ?? '';
    return elemento.innerHTML;
}

function obtenerUrlApi(archivo) {
    return window.location.pathname.includes('/vistas/')
        ? `../api/${archivo}`
        : `api/${archivo}`;
}

function formatearFecha(fecha) {

    const anio = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');

    return `${anio}-${mes}-${dia}`;
}

function obtenerNumeroDia(fecha) {

    const numero = fecha.getDay();

    return numero === 0 ? 7 : numero;
}

function obtenerDiasSeleccionados(clase = '.disp-dia:checked') {

    return Array.from(document.querySelectorAll(clase))
        .map(cb => Number(cb.value))
        .filter(dia => dia >= 1 && dia <= 7)
        .sort((a, b) => a - b);
}

/* ===================================================
   CALCULAR FECHAS AUTOMÁTICAMENTE
=================================================== */

function recalcularFechasAuto() {

    const diasSeleccionados = obtenerDiasSeleccionados();

    const inputInicio = document.getElementById('disp-fecha-inicio');
    const inputFin = document.getElementById('disp-fecha-fin');
    const contenedorFin = document.getElementById('contenedor-fecha-fin');
    const inputTotal = document.getElementById('disp-total-dias');

    if (!inputInicio || !inputTotal) {
        return;
    }

    if (contenedorFin) {
        contenedorFin.style.display = 'block';
    }

    if (diasSeleccionados.length === 0) {

        inputInicio.value = '';

        if (inputFin) {
            inputFin.value = '';
        }

        inputTotal.value = '0 días seleccionados';

        return;
    }

    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);

    const diaActual = obtenerNumeroDia(hoy);

    const primerDia = diasSeleccionados[0];
    const ultimoDia = diasSeleccionados[diasSeleccionados.length - 1];

    // Buscar la próxima fecha correspondiente al primer día.
    const diferenciaInicio = (primerDia - diaActual + 7) % 7;

    const fechaInicio = new Date(hoy);
    fechaInicio.setDate(hoy.getDate() + diferenciaInicio);

    // La fecha final corresponde al último día seleccionado.
    const diferenciaFin = ultimoDia - primerDia;

    const fechaFin = new Date(fechaInicio);
    fechaFin.setDate(fechaInicio.getDate() + diferenciaFin);

    inputInicio.value = formatearFecha(fechaInicio);

    if (inputFin) {
        inputFin.value = formatearFecha(fechaFin);
    }

    inputTotal.value =
        `${diasSeleccionados.length} día(s) seleccionado(s)`;
}

/* ===================================================
   CARGAR CURSOS ASIGNADOS AL TUTOR
=================================================== */

async function cargarCursosAsignadosTutor() {

    const select = document.getElementById('disp-curso');

    if (!select) {
        return;
    }

    try {

        const respuesta = await fetch(
            obtenerUrlApi('admin_asignaciones.php'),
            { cache: 'no-store' }
        );

        const data = await respuesta.json();

        select.innerHTML =
            '<option value="">Selecciona un curso asignado...</option>';

        if (data.exito && Array.isArray(data.datos)) {

            const misCursos = data.datos.filter(curso =>
                Number(curso.idTutor) === idTutorSesion
            );

            if (misCursos.length > 0) {

                misCursos.forEach(curso => {

                    const opcion = document.createElement('option');

                    opcion.value = curso.idCurso;

                    opcion.textContent =
                        `${curso.codigoCurso} - ${curso.nombreCurso} (${curso.nombreCarrera})`;

                    select.appendChild(opcion);
                });

            } else {

                select.innerHTML =
                    '<option value="">El administrador aún no te ha asignado cursos.</option>';
            }
        }

    } catch (error) {
        console.error('Error al cargar los cursos:', error);
    }
}

/* ===================================================
   GUARDAR DISPONIBILIDAD CON TODOS LOS DÍAS
=================================================== */

async function guardarDisponibilidad(event) {

    if (event) {
        event.preventDefault();
    }

    const selectCurso = document.getElementById('disp-curso');
    const inputInicio = document.getElementById('disp-fecha-inicio');
    const inputFin = document.getElementById('disp-fecha-fin');
    const inputHora = document.getElementById('disp-inicio');
    const selectDuracion = document.getElementById('disp-duracion');

    const idCurso = Number(selectCurso.value);
    const fecha = inputInicio.value;
    const fechaFin = inputFin.value;
    const horaInicio = inputHora.value;
    const duracionMinutos = Number(selectDuracion.value);

    const diasSemana = obtenerDiasSeleccionados();

    if (!idCurso) {
        alert('Selecciona un curso asignado.');
        return;
    }

    if (diasSemana.length === 0) {
        alert('Selecciona al menos un día para impartir la tutoría.');
        return;
    }

    if (!fecha || !fechaFin) {
        alert('Debes completar las fechas de inicio y finalización.');
        return;
    }

    if (fechaFin < fecha) {
        alert('La fecha de finalización no puede ser anterior al inicio.');
        return;
    }

    if (!horaInicio) {
        alert('Selecciona la hora de inicio.');
        return;
    }

    if (!duracionMinutos) {
        alert('Selecciona la duración de la tutoría.');
        return;
    }

    // Estos valores se envían al PHP para guardarlos en MySQL.
    const datos = {
        idTutor: idTutorSesion,
        idCurso: idCurso,
        fecha: fecha,
        fechaFin: fechaFin,
        horaInicio: horaInicio,
        duracionMinutos: duracionMinutos,
        diasSemana: diasSemana
    };

    try {

        const respuesta = await fetch(
            obtenerUrlApi('disponibilidad.php'),
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(datos)
            }
        );

        const data = await respuesta.json();

        if (data.exito) {

            alert('✅ ' + data.mensaje);

            const formulario = document.getElementById('form-disponibilidad');

            if (formulario) {
                formulario.reset();
            }

            recalcularFechasAuto();

            await cargarMisTutoriasPublicadas();
            await cargarSolicitudesTutor();

        } else {
            alert('❌ ' + data.mensaje);
        }

    } catch (error) {

        console.error('Error al guardar la tutoría:', error);

        alert('Error de conexión al guardar la tutoría.');
    }
}

/* ===================================================
   MOSTRAR MIS TUTORÍAS PUBLICADAS
=================================================== */

async function cargarMisTutoriasPublicadas() {

    const pista = document.getElementById('slider-tutor-pista');
    const indicadores = document.getElementById('indicadores-tutor-pista');

    if (!pista) {
        return;
    }

    try {

        const url =
            `${obtenerUrlApi('disponibilidad.php')}?idTutor=${idTutorSesion}`;

        const respuesta = await fetch(url, { cache: 'no-store' });
        const data = await respuesta.json();

        pista.innerHTML = '';

        if (indicadores) {
            indicadores.innerHTML = '';
        }

        sliderTutorPosicion = 0;
        pista.style.transform = 'translateX(0)';

        if (!data.exito || !Array.isArray(data.datos) ||
            data.datos.length === 0) {

            misTutoriasCache = [];

            pista.innerHTML = `
                <p class="subtitulo-seccion p-sm">
                    No tienes tutorías activas actualmente.
                </p>
            `;

            return;
        }

        misTutoriasCache = data.datos;

        data.datos.forEach(item => {

            const fechaObjeto = new Date(item.fecha + 'T00:00:00');
            const numeroDia = obtenerNumeroDia(fechaObjeto);

            const diasMostrar =
                item.diasSeleccionados ||
                NOMBRES_DIAS[numeroDia];

            const fechaFinalMostrar =
                item.fechaFin || item.fecha;

            const tarjeta = document.createElement('div');
            tarjeta.className = 'tarjeta-carrusel-item';

            tarjeta.innerHTML = `
                <div class="tarjeta-carrusel-contenido">
                    <div>

                        <span class="etiqueta-materia">
                            ${esc(item.nombreMateria)}
                        </span>

                        <div class="info-detalle-linea">
                            <i class="fa-solid fa-calendar-days"></i>
                            <strong>Días:</strong>
                            ${esc(diasMostrar)}
                        </div>

                        <div class="info-detalle-linea">
                            <i class="fa-regular fa-calendar"></i>
                            <strong>Fecha Inicio:</strong>
                            ${esc(item.fecha)}
                        </div>

                        <div class="info-detalle-linea">
                            <i class="fa-regular fa-calendar-check"></i>
                            <strong>Fecha Fin:</strong>
                            ${esc(fechaFinalMostrar)}
                        </div>

                        <div class="info-detalle-linea">
                            <i class="fa-solid fa-clock"></i>
                            <strong>Horario:</strong>
                            ${esc(item.horaInicio.substring(0, 5))}
                            -
                            ${esc(item.horaFin.substring(0, 5))}
                        </div>

                        <div class="info-detalle-linea">
                            <i class="fa-solid fa-users"></i>
                            <strong>Inscritos:</strong>
                            ${Number(item.cuposOcupados) || 0} /
                            ${Number(item.cupoMaximo) || 30} alumnos
                        </div>

                    </div>

                    <div class="mt-sm acciones-tarjeta"
                         style="display:flex;gap:0.4rem;flex-wrap:wrap;">

                        <button
                            type="button"
                            class="btn btn-exito-suave"
                            style="flex:1;"
                            onclick="abrirModalEditar(${Number(item.idDisponibilidad)})">

                            <i class="fa-solid fa-pen"></i>
                            Editar
                        </button>

                        <button
                            type="button"
                            class="btn btn-peligro-suave"
                            style="flex:1;"
                            onclick="eliminarTutoria(${Number(item.idDisponibilidad)})">

                            <i class="fa-solid fa-trash"></i>
                            Eliminar
                        </button>

                    </div>
                </div>
            `;

            pista.appendChild(tarjeta);
        });

        if (indicadores) {

            const porVista = obtenerTarjetasPorVistaTutor();

            const totalPaginas = Math.ceil(
                data.datos.length / porVista
            );

            for (let i = 0; i < totalPaginas; i++) {

                const punto = document.createElement('button');

                punto.className =
                    `punto-indicador ${i === 0 ? 'activo' : ''}`;

                punto.onclick = () => irASlideTutor(i);

                indicadores.appendChild(punto);
            }
        }

    } catch (error) {

        console.error('Error al cargar tutorías:', error);
    }
}

/* ===================================================
   CONTROL DEL CARRUSEL
=================================================== */

function obtenerTarjetasPorVistaTutor() {

    if (window.innerWidth <= 550) return 1;
    if (window.innerWidth <= 850) return 2;
    if (window.innerWidth <= 1100) return 3;

    return 4;
}

function moverSliderTutor(direccion) {

    const pista = document.getElementById('slider-tutor-pista');

    if (!pista) return;

    const total = misTutoriasCache.length;
    const porVista = obtenerTarjetasPorVistaTutor();

    const maxIndice = Math.max(
        0,
        Math.ceil(total / porVista) - 1
    );

    sliderTutorPosicion += direccion;

    if (sliderTutorPosicion < 0) {
        sliderTutorPosicion = maxIndice;
    }

    if (sliderTutorPosicion > maxIndice) {
        sliderTutorPosicion = 0;
    }

    actualizarSliderTutor();
}

function irASlideTutor(indice) {

    sliderTutorPosicion = indice;

    actualizarSliderTutor();
}

function actualizarSliderTutor() {

    const pista = document.getElementById('slider-tutor-pista');

    if (!pista) return;

    pista.style.transform =
        `translateX(-${sliderTutorPosicion * 100}%)`;

    const puntos = document.querySelectorAll(
        '#indicadores-tutor-pista .punto-indicador'
    );

    puntos.forEach((punto, indice) => {

        punto.classList.toggle(
            'activo',
            indice === sliderTutorPosicion
        );
    });
}

/* ===================================================
   MODAL PARA EDITAR TUTORÍAS
=================================================== */

function abrirModalEditar(idDisponibilidad) {

    const item = misTutoriasCache.find(
        tutoria =>
            Number(tutoria.idDisponibilidad) ===
            Number(idDisponibilidad)
    );

    if (!item) return;

    cerrarModalEditar();

    const diasActuales = (item.diasSeleccionados || '')
        .split(',')
        .map(dia => dia.trim());

    const fechaObjeto = new Date(item.fecha + 'T00:00:00');
    const diaInicial = NOMBRES_DIAS[obtenerNumeroDia(fechaObjeto)];

    if (diasActuales.length === 1 && !diasActuales[0]) {
        diasActuales[0] = diaInicial;
    }

    const checkboxes = Object.entries(NOMBRES_DIAS)
        .map(([numero, nombre]) => {

            const marcado = diasActuales.includes(nombre)
                ? 'checked'
                : '';

            return `
                <label style="display:inline-flex;align-items:center;gap:0.3rem;white-space:nowrap;">
                    <input
                        type="checkbox"
                        class="edit-dia"
                        value="${numero}"
                        ${marcado}
                    >
                    ${nombre}
                </label>
            `;
        })
        .join('');

    const overlay = document.createElement('div');

    overlay.id = 'modal-editar-tutoria';

    overlay.style.cssText = `
        position:fixed;
        inset:0;
        background:rgba(0,0,0,0.45);
        display:flex;
        align-items:center;
        justify-content:center;
        z-index:1000;
        padding:1rem;
    `;

    overlay.innerHTML = `
        <div class="tarjeta"
             style="max-width:460px;width:100%;max-height:95vh;overflow:auto;margin:0;">

            <h3 style="margin-bottom:0.25rem;">
                Modificar tutoría
            </h3>

            <p class="subtitulo-seccion" style="margin-bottom:1rem;">
                ${esc(item.nombreMateria)}
            </p>

            <div class="grupo-campo">
                <label class="etiqueta-campo" for="edit-fecha">
                    Fecha Inicio
                </label>
                <input
                    type="date"
                    id="edit-fecha"
                    class="campo-entrada"
                    value="${esc(item.fecha)}"
                >
            </div>

            <div class="grupo-campo">
                <label class="etiqueta-campo" for="edit-fecha-fin">
                    Fecha de finalización
                </label>
                <input
                    type="date"
                    id="edit-fecha-fin"
                    class="campo-entrada"
                    value="${esc(item.fechaFin || item.fecha)}"
                >
            </div>

            <div class="grupo-campo">
                <label class="etiqueta-campo">
                    Días a impartir la tutoría
                </label>

                <div style="display:flex;flex-wrap:wrap;gap:0.65rem;padding:0.75rem;border:1px solid var(--color-borde);border-radius:10px;">
                    ${checkboxes}
                </div>
            </div>

            <div class="grupo-campo">
                <label class="etiqueta-campo" for="edit-inicio">
                    Hora de inicio
                </label>
                <input
                    type="time"
                    id="edit-inicio"
                    class="campo-entrada"
                    value="${esc(item.horaInicio.substring(0, 5))}"
                >
            </div>

            <div class="grupo-campo">
                <label class="etiqueta-campo" for="edit-duracion">
                    Duración (minutos)
                </label>
                <input
                    type="number"
                    id="edit-duracion"
                    class="campo-entrada"
                    min="15"
                    max="240"
                    step="5"
                    value="${Number(item.duracionMinutos) || 45}"
                >
            </div>

            <div style="display:flex;gap:0.5rem;justify-content:flex-end;">

                <button
                    type="button"
                    class="btn btn-peligro-suave"
                    onclick="cerrarModalEditar()">
                    Cancelar
                </button>

                <button
                    type="button"
                    class="btn btn-oro"
                    onclick="guardarEdicionTutoria(${Number(item.idDisponibilidad)})">
                    Guardar cambios
                </button>

            </div>
        </div>
    `;

    document.body.appendChild(overlay);
}

function cerrarModalEditar() {

    const modal = document.getElementById('modal-editar-tutoria');

    if (modal) {
        modal.remove();
    }
}

/* ===================================================
   GUARDAR CAMBIOS DE UNA TUTORÍA
=================================================== */

async function guardarEdicionTutoria(idDisponibilidad) {

    const fecha = document.getElementById('edit-fecha').value;
    const fechaFin = document.getElementById('edit-fecha-fin').value;
    const horaInicio = document.getElementById('edit-inicio').value;

    const duracionMinutos = Number(
        document.getElementById('edit-duracion').value
    );

    const diasSemana = obtenerDiasSeleccionados('.edit-dia:checked');

    if (!fecha || !fechaFin || !horaInicio ||
        !duracionMinutos || diasSemana.length === 0) {

        alert('Completa todos los campos y selecciona al menos un día.');
        return;
    }

    if (fechaFin < fecha) {
        alert('La fecha final debe ser igual o posterior a la fecha inicial.');
        return;
    }

    try {

        const respuesta = await fetch(
            obtenerUrlApi('disponibilidad.php'),
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    idDisponibilidad: idDisponibilidad,
                    fecha: fecha,
                    fechaFin: fechaFin,
                    horaInicio: horaInicio,
                    duracionMinutos: duracionMinutos,
                    diasSemana: diasSemana
                })
            }
        );

        const data = await respuesta.json();

        if (data.exito) {

            alert('✅ ' + data.mensaje);

            cerrarModalEditar();

            cargarMisTutoriasPublicadas();
            cargarSolicitudesTutor();

        } else {
            alert('❌ ' + data.mensaje);
        }

    } catch (error) {
        console.error('Error al editar tutoría:', error);
        alert('Error de conexión al modificar la tutoría.');
    }
}

/* ===================================================
   ELIMINAR TUTORÍA
=================================================== */

async function eliminarTutoria(idDisponibilidad) {

    if (!confirm('¿Seguro que deseas eliminar esta tutoría?')) {
        return;
    }

    try {

        const url = obtenerUrlApi('disponibilidad.php');

        let respuesta = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                idDisponibilidad: idDisponibilidad
            })
        });

        let data = await respuesta.json();

        if (!data.exito && data.requiereConfirmacion) {

            const confirmar = confirm(
                `${data.mensaje}\nSi la eliminas, sus solicitudes también se borrarán. ¿Continuar?`
            );

            if (!confirmar) return;

            respuesta = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    idDisponibilidad: idDisponibilidad,
                    forzar: true
                })
            });

            data = await respuesta.json();
        }

        if (data.exito) {

            alert('✅ ' + data.mensaje);

            cargarMisTutoriasPublicadas();
            cargarSolicitudesTutor();

        } else {
            alert('❌ ' + data.mensaje);
        }

    } catch (error) {
        console.error('Error al eliminar tutoría:', error);
    }
}

/* ===================================================
   CARGAR SOLICITUDES RECIBIDAS
=================================================== */

async function cargarSolicitudesTutor() {

    const tabla = document.getElementById('tabla-solicitudes-tutor');

    if (!tabla) return;

    try {

        const url =
            `${obtenerUrlApi('solicitudes.php')}?idTutor=${idTutorSesion}&soloActivas=1`;

        const respuesta = await fetch(url, { cache: 'no-store' });
        const data = await respuesta.json();

        tabla.innerHTML = '';

        if (!data.exito || !Array.isArray(data.datos) ||
            data.datos.length === 0) {

            tabla.innerHTML = `
                <tr>
                    <td colspan="4" class="texto-centro">
                        No hay solicitudes recibidas.
                    </td>
                </tr>
            `;

            return;
        }

        data.datos.forEach(solicitud => {

            const fila = document.createElement('tr');

            let acciones = '';

            if (solicitud.estadoSolicitud === 'Pendiente') {

                acciones = `
                    <button
                        type="button"
                        class="btn btn-exito-suave"
                        onclick="cambiarEstado(${Number(solicitud.idSolicitud)}, 'Aprobada')">
                        Aceptar
                    </button>

                    <button
                        type="button"
                        class="btn btn-peligro-suave"
                        onclick="cambiarEstado(${Number(solicitud.idSolicitud)}, 'Rechazada')">
                        Rechazar
                    </button>
                `;

            } else {

                const estado = esc(solicitud.estadoSolicitud);

                acciones = `
                    <span class="estado-badge estado-${estado.toLowerCase()}">
                        ${estado}
                    </span>
                `;
            }

            fila.innerHTML = `
                <td class="texto-resaltado">
                    ${esc(solicitud.nombreEstudiante ||
                        solicitud.noCarnetEstudiante)}
                </td>

                <td>
                    ${esc(solicitud.nombreMateria)}
                </td>

                <td>
                    ${esc(solicitud.fecha)} -
                    ${esc(solicitud.horaInicio)}
                </td>

                <td class="texto-centro">
                    ${acciones}
                </td>
            `;

            tabla.appendChild(fila);
        });

    } catch (error) {
        console.error('Error al cargar solicitudes:', error);
    }
}

/* ===================================================
   ACEPTAR O RECHAZAR SOLICITUDES
=================================================== */

async function cambiarEstado(idSolicitud, estadoSolicitud) {

    if (!confirm(
        `¿Estás seguro de cambiar el estado a '${estadoSolicitud}'?`
    )) {
        return;
    }

    try {

        const respuesta = await fetch(
            obtenerUrlApi('solicitudes.php'),
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    idSolicitud: idSolicitud,
                    estadoSolicitud: estadoSolicitud
                })
            }
        );

        const data = await respuesta.json();

        if (data.exito) {

            alert('✅ Estado actualizado.');

            cargarSolicitudesTutor();

        } else {

            alert('❌ ' + data.mensaje);
        }

    } catch (error) {
        console.error('Error al actualizar la solicitud:', error);
    }
}

/* ===================================================
   CERRAR SESIÓN
=================================================== */

async function cerrarSesion() {

    const url = window.location.pathname.includes('/vistas/')
        ? '../php/logout.php'
        : 'php/logout.php';

    await fetch(url);

    window.location.reload();
}
