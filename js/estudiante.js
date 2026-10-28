// js/estudiante.js - Lógica Exclusiva del Portal Estudiante (Carrusel + Carrera 0901)
let listaDisponibilidadGlobal = [];
let carruselPosicionActual = 0;

document.addEventListener('DOMContentLoaded', () => {
    cargarDisponibilidadEstudiante();
    cargarSolicitudesEstudiante();
    configurarFiltrosEstudiante();
});

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
        select.innerHTML += `<option value="${m}">${m}</option>`;
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
            const cuposLibres = (item.cupoMaximo || 30) - (item.cuposOcupados || 0);
            const badgeCupoClass = cuposLibres <= 5 ? 'cupo-alerta' : 'cupo-normal';

            const tarjeta = document.createElement('div');
            tarjeta.className = 'tarjeta-carrusel-item';
            tarjeta.innerHTML = `
                <div class="tarjeta-carrusel-contenido">
                    <div>
                        <div class="encabezado-materia">
                            <span class="etiqueta-materia">${item.nombreMateria}</span>
                            <span class="duracion-materia"><i class="fa-regular fa-clock"></i> ${item.duracionMinutos || 45} min</span>
                        </div>
                        <h4 class="nombre-tutor">${item.nombreTutor}</h4>
                        <div class="detalles-horario">
                            <div><i class="fa-regular fa-calendar"></i> Fecha: <strong>${item.fecha}</strong></div>
                            <div><i class="fa-solid fa-clock"></i> Horario: <strong>${item.horaInicio} - ${item.horaFin}</strong></div>
                        </div>
                        <div class="cupos-info ${badgeCupoClass}">
                            <i class="fa-solid fa-users"></i> Cupos Disponibles: <strong>${cuposLibres} / 30</strong>
                        </div>
                    </div>
                    <button type="button" onclick="solicitarTutoria(${item.idDisponibilidad})" class="btn btn-oro ancho-total mt-sm" ${cuposLibres <= 0 ? 'disabled' : ''}>
                        <i class="fa-solid fa-paper-plane"></i> ${cuposLibres > 0 ? 'Solicitar Tutoría' : 'Cupo Lleno'}
                    </button>
                </div>
            `;
            contenedor.appendChild(tarjeta);
        });

        // Puntos de paginación
        if (contIndicadores) {
            const totalPaginas = Math.ceil(datos.length / obtenerTarjetasPorVista());
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

function obtenerTarjetasPorVista() {
    if (window.innerWidth <= 600) return 1;
    if (window.innerWidth <= 992) return 2;
    return 3;
}

function moverCarrusel(dir) {
    const contenedor = document.getElementById('contenedor-disponibilidad');
    if (!contenedor) return;

    const totalItems = contenedor.children.length;
    const porVista   = obtenerTarjetasPorVista();
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

    const puntos = document.querySelectorAll('.punto-indicador');
    puntos.forEach((p, idx) => {
        if (idx === carruselPosicionActual) p.classList.add('activo');
        else p.classList.remove('activo');
    });
}

async function solicitarTutoria(idDisp) {
    if (!confirm('¿Deseas solicitar este horario de tutoría?')) return;

    // Obtener carnet de sesión
    const respSes = await fetch('php/session_check.php');
    const sesData = await respSes.json();
    const carnet  = sesData.usuario.noCarnetCompleto || sesData.usuario.username;

    // 1. Validar Cupos (30) y Traslapes en Servidor
    const resVal = await fetch('api/validar_reserva.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ noCarnetEstudiante: carnet, idDisponibilidad: idDisp })
    });
    const valData = await resVal.json();

    if (!valData.valido) {
        alert(valData.mensaje);
        return;
    }

    // 2. Guardar Solicitud
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

async function cargarSolicitudesEstudiante() {
    const respSes = await fetch('php/session_check.php');
    const sesData = await respSes.json();
    const carnet  = sesData.usuario.noCarnetCompleto || sesData.usuario.username;

    const resp = await fetch(`api/solicitudes.php?noCarnetEstudiante=${encodeURIComponent(carnet)}`);
    const data = await resp.json();

    const tabla = document.getElementById('tabla-solicitudes-estudiante');
    if (!tabla) return;
    tabla.innerHTML = '';

    if (data.exito && Array.isArray(data.datos) && data.datos.length > 0) {
        data.datos.forEach(s => {
            const tr = document.createElement('tr');
            let btnCancelar = s.estadoSolicitud === 'Pendiente' ? 
                `<button type="button" class="btn btn-peligro-suave" onclick="cancelarSolicitud(${s.idSolicitud})">Cancelar</button>` : '-';

            tr.innerHTML = `
                <td class="texto-resaltado">${s.nombreMateria}</td>
                <td>${s.nombreTutor || 'Tutor UMG'}</td>
                <td>${s.fecha} - ${s.horaInicio}</td>
                <td><span class="estado-badge estado-${s.estadoSolicitud.toLowerCase()}">${s.estadoSolicitud}</span></td>
                <td class="texto-derecha">${btnCancelar}</td>
            `;
            tabla.appendChild(tr);
        });
    } else {
        tabla.innerHTML = `<tr><td colspan="5" class="texto-centro">No tienes solicitudes reservadas.</td></tr>`;
    }
}

async function cancelarSolicitud(idSol) {
    if (!confirm('¿Estás seguro de cancelar esta reserva?')) return;

    const resp = await fetch('api/solicitudes.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idSolicitud: idSol, estadoSolicitud: 'Cancelada' })
    });
    const data = await resp.json();

    if (data.exito) {
        alert('✅ Reserva cancelada.');
        cargarSolicitudesEstudiante();
        cargarDisponibilidadEstudiante();
    }
}

function configurarFiltrosEstudiante() {
    const buscarTexto = document.getElementById('buscar-texto');
    const filtrarMat  = document.getElementById('filtrar-materia');

    const filtrar = () => {
        const texto = (buscarTexto ? buscarTexto.value : '').toLowerCase().trim();
        const mat   = (filtrarMat ? filtrarMat.value : '').toLowerCase().trim();

        const filtrados = listaDisponibilidadGlobal.filter(item => {
            const coincideTexto = !texto || item.nombreMateria.toLowerCase().includes(texto) || item.nombreTutor.toLowerCase().includes(texto);
            const coincideMat   = !mat || item.nombreMateria.toLowerCase() === mat;
            return coincideTexto && coincideMat;
        });

        carruselPosicionActual = 0;
        renderizarTarjetasCarrusel(filtrados);
    };

    if (buscarTexto) buscarTexto.addEventListener('input', filtrar);
    if (filtrarMat) filtrarMat.addEventListener('change', filtrar);
}

async function cerrarSesion() {
    await fetch('php/logout.php');
    window.location.reload();
}
