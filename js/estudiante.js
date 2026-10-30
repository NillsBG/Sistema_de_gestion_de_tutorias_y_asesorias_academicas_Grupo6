// js/estudiante.js - Lógica del Estudiante con Renderizado Seguro de Cursos Inscritos
let listaDisponibilidadGlobal = [];
let carruselPosicionActual = 0;
let sliderInscritosPosicion = 0;

document.addEventListener('DOMContentLoaded', () => {
    cargarDisponibilidadEstudiante();
    cargarSolicitudesEstudiante();
    configurarFiltrosEstudiante();
});

function esc(texto) {
    const d = document.createElement('div');
    d.textContent = texto ?? '';
    return d.innerHTML;
}

function formatearHora(horaStr) {
    if (!horaStr) return '00:00';
    return horaStr.substring(0, 5);
}

async function cargarDisponibilidadEstudiante() {
    try {
        const respuesta = await fetch('api/disponibilidad.php');
        const resultado = await respuesta.json();

        if (resultado.exito && Array.isArray(resultado.datos)) {
            listaDisponibilidadGlobal = resultado.datos;
            poblarSelectMateriasCarrera(listaDisponibilidadGlobal);
            carruselPosicionActual = 0;
            renderizarTarjetasCarrusel(listaDisponibilidadGlobal);
        }
    } catch (e) {
        console.error('Error al cargar disponibilidad:', e);
    }
}

function poblarSelectMateriasCarrera(datos) {
    const select = document.getElementById('filtrar-materia');
    if (!select) return;

    const materiasUnicas = [...new Set(datos.map(d => d.nombreMateria))];
    select.innerHTML = '<option value="">Todas las materias de tu carrera</option>';
    materiasUnicas.forEach(m => {
        select.innerHTML += `<option value="${esc(m)}">${esc(m)}</option>`;
    });
}

function renderizarTarjetasCarrusel(datos) {
    const contenedor = document.getElementById('contenedor-disponibilidad');
    const contIndicadores = document.getElementById('carrusel-indicadores');
    if (!contenedor) return;

    contenedor.innerHTML = '';
    if (contIndicadores) contIndicadores.innerHTML = '';

    if (Array.isArray(datos) && datos.length > 0) {
        datos.forEach(item => {
            const cuposLibres = (parseInt(item.cupoMaximo) || 30) - (parseInt(item.cuposOcupados) || 0);

            const tarjeta = document.createElement('div');
            tarjeta.className = 'tarjeta-carrusel-item';
            tarjeta.innerHTML = `
                <div class="tarjeta-carrusel-contenido">
                    <div>
                        <span class="etiqueta-materia">${esc(item.nombreMateria)}</span>
                        <h4 class="nombre-tutor" style="font-size:1.05rem; font-weight:700; margin-bottom:0.4rem;">
                            <i class="fa-solid fa-chalkboard-user"></i> ${esc(item.nombreTutor)}
                        </h4>
                        <div class="info-detalle-linea"><i class="fa-solid fa-calendar-days"></i> <strong>Días:</strong> ${esc(item.diasSemana || 'Lunes')}</div>
                        <div class="info-detalle-linea"><i class="fa-regular fa-calendar"></i> <strong>Inicio:</strong> ${esc(item.fecha)}</div>
                        <div class="info-detalle-linea"><i class="fa-solid fa-clock"></i> <strong>Horario:</strong> ${formatearHora(item.horaInicio)} - ${formatearHora(item.horaFin)}</div>
                        <div class="info-detalle-linea"><i class="fa-solid fa-users"></i> <strong>Cupos:</strong> ${cuposLibres} / 30 disponibles</div>
                    </div>
                    <button type="button" onclick="solicitarTutoria(${item.idDisponibilidad})" class="btn btn-oro ancho-total mt-sm" ${cuposLibres <= 0 ? 'disabled' : ''}>
                        <i class="fa-solid fa-paper-plane"></i> ${cuposLibres > 0 ? 'Solicitar Tutoría' : 'Cupo Lleno'}
                    </button>
                </div>
            `;
            contenedor.appendChild(tarjeta);
        });

        if (contIndicadores) {
            const totalPaginas = Math.ceil(datos.length / obtenerTarjetasPorVistaEstudiante());
            for (let i = 0; i < totalPaginas; i++) {
                const punto = document.createElement('button');
                punto.className = `punto-indicador ${i === 0 ? 'activo' : ''}`;
                punto.onclick = () => irASlide(i);
                contIndicadores.appendChild(punto);
            }
        }
    } else {
        contenedor.innerHTML = `
            <div class="tarjeta texto-centro" style="width: 100%;">
                <p class="subtitulo-seccion"><i class="fa-solid fa-circle-info"></i> No hay tutorías disponibles para tu carrera actualmente.</p>
            </div>
        `;
    }

    actualizarPosicionCarrusel();
}

function obtenerTarjetasPorVistaEstudiante() {
    if (window.innerWidth <= 550) return 1;
    if (window.innerWidth <= 850) return 2;
    if (window.innerWidth <= 1100) return 3;
    return 4;
}

function moverCarrusel(dir) {
    const contenedor = document.getElementById('contenedor-disponibilidad');
    if (!contenedor) return;

    const totalItems = contenedor.children.length;
    const porVista   = obtenerTarjetasPorVistaEstudiante();
    const maxIndice  = Math.max(0, Math.ceil(totalItems / porVista) - 1);

    carruselPosicionActual += dir;
    if (carruselPosicionActual < 0) carruselPosicionActual = maxIndice;
    if (carruselPosicionActual > maxIndice) carruselPosicionActual = 0;

    actualizarPosicionCarrusel();
}

function irASlide(idx) {
    carruselPosicionActual = idx;
    actualizarPosicionCarrusel();
}

function actualizarPosicionCarrusel() {
    const contenedor = document.getElementById('contenedor-disponibilidad');
    if (!contenedor) return;

    const desplazamiento = carruselPosicionActual * 100;
    contenedor.style.transform = `translateX(-${desplazamiento}%)`;

    const puntos = document.querySelectorAll('#carrusel-indicadores .punto-indicador');
    puntos.forEach((p, idx) => {
        if (idx === carruselPosicionActual) p.classList.add('activo');
        else p.classList.remove('activo');
    });
}

/* ===================== SOLICITUDES Y MIS TUTORÍAS ASIGNADAS ===================== */
async function cargarSolicitudesEstudiante() {
    try {
        const respSes = await fetch('php/session_check.php');
        const sesData = await respSes.json();
        const carnet  = sesData.usuario.noCarnetCompleto || sesData.usuario.username;

        // Trae todas las solicitudes del alumno
        const resp = await fetch(`api/solicitudes.php?noCarnetEstudiante=${encodeURIComponent(carnet)}`);
        const data = await resp.json();

        const pistaInscritos = document.getElementById('slider-inscritos-pista');
        const contIndInscritos = document.getElementById('indicadores-inscritos-pista');
        const tabla = document.getElementById('tabla-solicitudes-estudiante');

        if (pistaInscritos) pistaInscritos.innerHTML = '';
        if (contIndInscritos) contIndInscritos.innerHTML = '';
        if (tabla) tabla.innerHTML = '';

        if (data.exito && Array.isArray(data.datos) && data.datos.length > 0) {
            // Filtrar inscripciones activas (Aprobada o Pendiente)
            const solicitudesActivas = data.datos.filter(s => s.estadoSolicitud === 'Aprobada' || s.estadoSolicitud === 'Pendiente');

            if (pistaInscritos) {
                if (solicitudesActivas.length > 0) {
                    solicitudesActivas.forEach(s => {
                        const div = document.createElement('div');
                        div.className = 'tarjeta-carrusel-item';

                        const esAprobada = s.estadoSolicitud === 'Aprobada';
                        const badgeIcono = esAprobada ? '<i class="fa-solid fa-circle-check"></i> Cita Confirmada' : '<i class="fa-solid fa-clock"></i> Pendientes de Aprobación';
                        const badgeColor = esAprobada ? '#10b981' : '#d97706';

                        let btnCancelar = s.estadoSolicitud === 'Pendiente' ? 
                            `<button type="button" class="btn btn-peligro-suave ancho-total mt-sm" onclick="cancelarSolicitud(${s.idSolicitud})"><i class="fa-solid fa-ban"></i> Cancelar Reserva</button>` :
                            `<div class="text-center mt-sm" style="font-size:0.85rem; font-weight:700; color:${badgeColor};">${badgeIcono}</div>`;

                        div.innerHTML = `
                            <div class="tarjeta-carrusel-contenido">
                                <div>
                                    <span class="etiqueta-materia">${esc(s.nombreMateria)}</span>
                                    <h4 class="nombre-tutor" style="font-size:1rem; font-weight:700; margin-bottom:0.4rem;">
                                        <i class="fa-solid fa-chalkboard-user"></i> ${esc(s.nombreTutor || 'Tutor UMG')}
                                    </h4>
                                    <div class="info-detalle-linea"><i class="fa-solid fa-calendar-days"></i> <strong>Días:</strong> ${esc(s.diasSemana || 'Lunes')}</div>
                                    <div class="info-detalle-linea"><i class="fa-regular fa-calendar"></i> <strong>Fecha:</strong> ${esc(s.fecha)}</div>
                                    <div class="info-detalle-linea"><i class="fa-solid fa-clock"></i> <strong>Horario:</strong> ${formatearHora(s.horaInicio)} - ${formatearHora(s.horaFin)}</div>
                                    <div class="info-detalle-linea"><i class="fa-solid fa-users"></i> <strong>Inscritos:</strong> ${parseInt(s.cuposOcupados) || 1} / 30 alumnos</div>
                                    <div style="margin-top:0.5rem;">
                                        <span class="estado-badge estado-${(s.estadoSolicitud || 'pendiente').toLowerCase()}">${esc(s.estadoSolicitud)}</span>
                                    </div>
                                </div>
                                ${btnCancelar}
                            </div>
                        `;
                        pistaInscritos.appendChild(div);
                    });
                } else {
                    pistaInscritos.innerHTML = `<p class="subtitulo-seccion p-sm" style="padding:1rem;">No tienes tutorías asignadas ni reservas activas actualmente.</p>`;
                }
            }

            // Renderizar la tabla de historial completa abajo
            if (tabla) {
                data.datos.forEach(s => {
                    const tr = document.createElement('tr');
                    let btnAccion = s.estadoSolicitud === 'Pendiente' ? 
                        `<button type="button" class="btn btn-peligro-suave" onclick="cancelarSolicitud(${s.idSolicitud})">Cancelar</button>` : '-';

                    tr.innerHTML = `
                        <td class="texto-resaltado">${esc(s.nombreMateria)}</td>
                        <td>${esc(s.nombreTutor || 'Tutor UMG')}</td>
                        <td>${esc(s.fecha)} - ${formatearHora(s.horaInicio)}</td>
                        <td><span class="estado-badge estado-${(s.estadoSolicitud || 'pendiente').toLowerCase()}">${esc(s.estadoSolicitud)}</span></td>
                        <td class="texto-derecha">${btnAccion}</td>
                    `;
                    tabla.appendChild(tr);
                });
            }

            // Paginación para slider inscritos
            if (contIndInscritos && pistaInscritos && solicitudesActivas.length > 0) {
                const porVista = obtenerTarjetasPorVistaEstudiante();
                const totalPaginas = Math.ceil(solicitudesActivas.length / porVista);
                for (let i = 0; i < totalPaginas; i++) {
                    const punto = document.createElement('button');
                    punto.className = `punto-indicador ${i === 0 ? 'activo' : ''}`;
                    punto.onclick = () => irASlideInscritos(i);
                    contIndInscritos.appendChild(punto);
                }
            }
        } else {
            if (pistaInscritos) pistaInscritos.innerHTML = `<p class="subtitulo-seccion p-sm" style="padding:1rem;">No te has inscrito en ninguna tutoría aún.</p>`;
            if (tabla) tabla.innerHTML = `<tr><td colspan="5" class="texto-centro">No tienes solicitudes de tutoría registradas.</td></tr>`;
        }

        sliderInscritosPosicion = 0;
        actualizarSliderInscritos();
    } catch (e) {
        console.error('Error al cargar solicitudes de estudiante:', e);
    }
}

function moverSliderInscritos(dir) {
    const pista = document.getElementById('slider-inscritos-pista');
    if (!pista) return;

    const totalItems = pista.children.length;
    const porVista   = obtenerTarjetasPorVistaEstudiante();
    const maxIndice  = Math.max(0, Math.ceil(totalItems / porVista) - 1);

    sliderInscritosPosicion += dir;
    if (sliderInscritosPosicion < 0) sliderInscritosPosicion = maxIndice;
    if (sliderInscritosPosicion > maxIndice) sliderInscritosPosicion = 0;

    actualizarSliderInscritos();
}

function irASlideInscritos(idx) {
    sliderInscritosPosicion = idx;
    actualizarSliderInscritos();
}

function actualizarSliderInscritos() {
    const pista = document.getElementById('slider-inscritos-pista');
    if (!pista) return;

    const desplazamiento = sliderInscritosPosicion * 100;
    pista.style.transform = `translateX(-${desplazamiento}%)`;

    const puntos = document.querySelectorAll('#indicadores-inscritos-pista .punto-indicador');
    puntos.forEach((p, idx) => {
        if (idx === sliderInscritosPosicion) p.classList.add('activo');
        else p.classList.remove('activo');
    });
}

async function solicitarTutoria(idDisp) {
    if (!confirm('¿Deseas solicitar este horario de tutoría?')) return;

    const respSes = await fetch('php/session_check.php');
    const sesData = await respSes.json();
    const carnet  = sesData.usuario.noCarnetCompleto || sesData.usuario.username;

    const resVal = await fetch('api/validar_reserva.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ noCarnetEstudiante: carnet, idDisponibilidad: idDisp })
    });
    const valData = await resVal.json();

    if (!valData.valido) {
        alert('❌ ' + valData.mensaje);
        return;
    }

    const resSol = await fetch('api/solicitudes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ noCarnetEstudiante: carnet, idDisponibilidad: idDisp })
    });
    const solData = await resSol.json();

    if (solData.exito) {
        alert('✅ Solicitud enviada exitosamente.');
        cargarDisponibilidadEstudiante();
        cargarSolicitudesEstudiante();
    } else {
        alert('❌ ' + solData.mensaje);
    }
}

async function cancelarSolicitud(idSol) {
    if (!confirm('¿Estás seguro de cancelar esta reserva de tutoría?')) return;

    const resp = await fetch('api/solicitudes.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idSolicitud: idSol, estadoSolicitud: 'Cancelada' })
    });
    const data = await resp.json();

    if (data.exito) {
        alert('✅ Reserva cancelada correctamente.');
        cargarDisponibilidadEstudiante();
        cargarSolicitudesEstudiante();
    } else {
        alert('❌ ' + data.mensaje);
    }
}

function configurarFiltrosEstudiante() {
    const buscarInput  = document.getElementById('buscar-texto');
    const selectMateria = document.getElementById('filtrar-materia');

    function filtrar() {
        const txt = buscarInput ? buscarInput.value.toLowerCase().trim() : '';
        const mat = selectMateria ? selectMateria.value.toLowerCase().trim() : '';

        const filtrados = listaDisponibilidadGlobal.filter(item => {
            const nomMat = (item.nombreMateria || '').toLowerCase();
            const nomTut = (item.nombreTutor || '').toLowerCase();

            const coincideTxt = !txt || nomMat.includes(txt) || nomTut.includes(txt);
            const coincideMat = !mat || nomMat === mat;

            return coincideTxt && coincideMat;
        });

        carruselPosicionActual = 0;
        renderizarTarjetasCarrusel(filtrados);
    }

    if (buscarInput) buscarInput.addEventListener('input', filtrar);
    if (selectMateria) selectMateria.addEventListener('change', filtrar);
}

async function cerrarSesion() {
    await fetch('php/logout.php');
    window.location.reload();
}
