let usuarioAutenticado = null;
let listaDisponibilidadGlobal = []; // Almacena los datos originales para filtrado rápido

document.addEventListener('DOMContentLoaded', () => {
    verificarSesion();
    configurarEventos();
    configurarFiltros();
});

// ---------------------------------------------------------------------------
// 1. CONTROL DE SESIÓN Y AUTENTICACIÓN
// ---------------------------------------------------------------------------

async function verificarSesion() {
    try {
        const respuesta = await fetch('php/session_check.php');
        const data = await respuesta.json();

        if (data.autenticado && data.usuario) {
            usuarioAutenticado = data.usuario;
            actualizarInterfazUsuario();
        } else {
            usuarioAutenticado = null;
            actualizarInterfazUsuario();
        }
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        usuarioAutenticado = null;
        actualizarInterfazUsuario();
    }
}

async function procesarLogin(event) {
    if (event) event.preventDefault();

    const correoInput = document.getElementById('login-correo');
    const claveInput  = document.getElementById('login-clave');
    const formLogin   = document.getElementById('form-login');

    if (!correoInput || !claveInput) {
        alert('Error: No se encontraron los campos del formulario.');
        return;
    }

    const correo = correoInput.value.trim();
    const clave  = claveInput.value.trim();
    const rolRadio = document.querySelector('input[name="rol"]:checked');
    const rolSeleccionado = rolRadio ? rolRadio.value : 'estudiante';

    if (!correo || !clave) {
        alert('Por favor, ingresa tu correo electrónico y contraseña.');
        return;
    }

    try {
        const respuesta = await fetch('php/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                correo: correo,
                clave: clave,
                rol: rolSeleccionado
            })
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            usuarioAutenticado = resultado.usuario;
            alert('¡Bienvenido ' + (resultado.usuario.nombre || resultado.usuario.username) + '!');

            if (formLogin) formLogin.reset();
            actualizarInterfazUsuario();
        } else {
            alert(resultado.mensaje || 'Credenciales incorrectas');
        }
    } catch (error) {
        console.error('Error en procesarLogin:', error);
        alert('Error de conexión al intentar ingresar.');
    }
}

async function cerrarSesion() {
    try {
        await fetch('php/logout.php');
    } catch (e) {
        console.error('Error al cerrar sesión:', e);
    }

    usuarioAutenticado = null;
    const formLogin = document.getElementById('form-login');
    if (formLogin) formLogin.reset();

    actualizarInterfazUsuario();
    alert('Sesión cerrada correctamente.');
}

// ---------------------------------------------------------------------------
// 2. CONTROL VISUAL DE VISTAS (INDEX.HTML)
// ---------------------------------------------------------------------------

function actualizarInterfazUsuario() {
    const vistaLogin      = document.getElementById('vista-login');
    const vistaEstudiante = document.getElementById('vista-estudiante');
    const vistaTutor      = document.getElementById('vista-tutor');
    const encabezado      = document.getElementById('encabezado');

    const infoNombre = document.getElementById('info-usuario-nombre');
    const infoRol    = document.getElementById('info-usuario-rol');

    if (usuarioAutenticado) {
        const nombre = usuarioAutenticado.nombre || usuarioAutenticado.username;
        const rol    = usuarioAutenticado.rol || 'Usuario';

        if (infoNombre) infoNombre.innerText = nombre;
        if (infoRol) infoRol.innerText = rol;

        if (vistaLogin) vistaLogin.classList.add('oculto');
        if (encabezado) encabezado.classList.remove('oculto');

        const esTutor = rol.toLowerCase() === 'tutor' || rol.toLowerCase() === 'administrador';

        if (esTutor) {
            if (vistaTutor) vistaTutor.classList.remove('oculto');
            if (vistaEstudiante) vistaEstudiante.classList.add('oculto');
            
            // Cargar datos del Tutor
            cargarSolicitudesTutor();
        } else {
            if (vistaEstudiante) vistaEstudiante.classList.remove('oculto');
            if (vistaTutor) vistaTutor.classList.add('oculto');

            // Cargar datos del Estudiante
            cargarDisponibilidad();
            cargarSolicitudesEstudiante();
        }

        cargarMetricsDashboard();
    } else {
        if (vistaLogin) vistaLogin.classList.remove('oculto');
        if (encabezado) encabezado.classList.add('oculto');
        if (vistaEstudiante) vistaEstudiante.classList.add('oculto');
        if (vistaTutor) vistaTutor.classList.add('oculto');
    }
}

// ---------------------------------------------------------------------------
// 3. OPORTUNIDADES DE TUTORÍA Y FILTROS (VISTA ESTUDIANTE)
// ---------------------------------------------------------------------------

async function cargarDisponibilidad() {
    const contenedor = document.getElementById('contenedor-disponibilidad');
    if (!contenedor) return;

    try {
        const respuesta = await fetch('api/disponibilidad.php');
        if (!respuesta.ok) return;
        const resultado = await respuesta.json();

        if (resultado.exito && Array.isArray(resultado.datos)) {
            listaDisponibilidadGlobal = resultado.datos;
            aplicarFiltrosEImprimir();
        } else {
            listaDisponibilidadGlobal = [];
            contenedor.innerHTML = `
                <div class="tarjeta texto-centro">
                    <p><i class="fa-solid fa-circle-info icono-oro"></i> No hay tutorías disponibles en este momento.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error al cargar disponibilidad:', error);
    }
}

function configurarFiltros() {
    const inputBuscar = document.getElementById('buscar-texto');
    const selectMateria = document.getElementById('filtrar-materia');

    if (inputBuscar) {
        inputBuscar.addEventListener('input', aplicarFiltrosEImprimir);
    }

    if (selectMateria) {
        selectMateria.addEventListener('change', aplicarFiltrosEImprimir);
    }
}

function aplicarFiltrosEImprimir() {
    const contenedor = document.getElementById('contenedor-disponibilidad');
    if (!contenedor) return;

    const textoBuscar = (document.getElementById('buscar-texto')?.value || '').toLowerCase().trim();
    const materiaSel  = (document.getElementById('filtrar-materia')?.value || '').toLowerCase().trim();

    const filtrados = listaDisponibilidadGlobal.filter(item => {
        const materiaMatch = !materiaSel || (item.nombreMateria || '').toLowerCase().includes(materiaSel);
        
        const tutorNombre = (item.nombreTutor || '').toLowerCase();
        const materiaNombre = (item.nombreMateria || '').toLowerCase();
        const textoMatch = !textoBuscar || tutorNombre.includes(textoBuscar) || materiaNombre.includes(textoBuscar);

        return materiaMatch && textoMatch;
    });

    contenedor.innerHTML = '';

    if (filtrados.length === 0) {
        contenedor.innerHTML = `
            <div class="tarjeta texto-centro" style="grid-column: 1 / -1; padding: 2rem;">
                <p><i class="fa-solid fa-filter-circle-xmark icono-oro" style="font-size: 1.5rem;"></i></p>
                <p class="mt-sm">No se encontraron tutorías que coincidan con la búsqueda o filtro seleccionado.</p>
            </div>
        `;
        return;
    }

    filtrados.forEach(item => {
        const idDisp  = item.idDisponibilidad;
        const materia = item.nombreMateria || 'Tutoría Académica';
        const tutor   = item.nombreTutor || 'Tutor UMG';
        const fecha   = item.fecha || '';
        const hInicio = item.horaInicio || '';
        const hFin    = item.horaFin || '';

        const tarjeta = document.createElement('div');
        tarjeta.className = 'tarjeta-disponibilidad';
        tarjeta.innerHTML = `
            <div>
                <div class="encabezado-materia">
                    <span class="etiqueta-materia">${materia}</span>
                    <span class="duracion-materia"><i class="fa-regular fa-clock"></i> 45 min</span>
                </div>
                <h4 class="nombre-tutor">${tutor}</h4>
                <div class="detalles-horario">
                    <div><i class="fa-regular fa-calendar"></i> ${fecha}</div>
                    <div><i class="fa-solid fa-clock"></i> ${hInicio} - ${hFin}</div>
                    <div><i class="fa-solid fa-location-dot"></i> Laboratorio / En línea</div>
                </div>
            </div>
            <button type="button" onclick="solicitarTutoria(${idDisp})" class="btn btn-oro ancho-total mt-sm">
                <i class="fa-solid fa-paper-plane"></i> Solicitar Tutoría
            </button>
        `;
        contenedor.appendChild(tarjeta);
    });
}

// ---------------------------------------------------------------------------
// 4. CREAR SOLICITUD DE TUTORÍA (ESTUDIANTE -> API -> BASE DE DATOS)
// ---------------------------------------------------------------------------

async function solicitarTutoria(idDisponibilidad) {
    if (!usuarioAutenticado) {
        alert('Debes iniciar sesión para solicitar una tutoría.');
        return;
    }

    const noCarnet = usuarioAutenticado.noCarnetCompleto || usuarioAutenticado.username;

    if (!confirm('¿Deseas confirmar la reserva para este horario de tutoría?')) return;

    try {
        // 1. Validar reglas de negocio (Traslapes y Duplicados)
        const resVal = await fetch('api/validar_reserva.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                noCarnetEstudiante: noCarnet,
                idDisponibilidad: idDisponibilidad
            })
        });

        const valData = await resVal.json();

        if (!valData.valido) {
            alert('❌ ' + (valData.mensaje || 'No es posible solicitar esta tutoría.'));
            return;
        }

        // 2. Insertar solicitud en la Base de Datos
        const resSol = await fetch('api/solicitudes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                noCarnetEstudiante: noCarnet,
                idDisponibilidad: idDisponibilidad
            })
        });

        const solData = await resSol.json();

        if (solData.exito) {
            alert('✅ ¡Solicitud de tutoría registrada con éxito!');
            cargarDisponibilidad();
            cargarSolicitudesEstudiante();
            cargarMetricsDashboard();
        } else {
            alert('❌ ' + (solData.mensaje || 'Error al enviar la solicitud.'));
        }
    } catch (error) {
        console.error('Error al solicitar tutoría:', error);
        alert('Error de conexión al procesar la solicitud.');
    }
}

// ---------------------------------------------------------------------------
// 5. MIS SOLICITUDES (VISTA ESTUDIANTE -> CARGA Y CANCELACIÓN EN BASE DE DATOS)
// ---------------------------------------------------------------------------

async function cargarSolicitudesEstudiante() {
    if (!usuarioAutenticado) return;

    const tablaBody = document.getElementById('tabla-solicitudes-estudiante');
    if (!tablaBody) return;

    const noCarnet = usuarioAutenticado.noCarnetCompleto || usuarioAutenticado.username;

    try {
        const respuesta = await fetch(`api/solicitudes.php?noCarnetEstudiante=${encodeURIComponent(noCarnet)}`);
        if (!respuesta.ok) return;

        const resultado = await respuesta.json();
        tablaBody.innerHTML = '';

        if (resultado.exito && Array.isArray(resultado.datos) && resultado.datos.length > 0) {
            resultado.datos.forEach(item => {
                const tr = document.createElement('tr');
                
                let badgeClass = 'estado-pendiente';
                if (item.estadoSolicitud === 'Aprobada') badgeClass = 'estado-aprobado';
                if (item.estadoSolicitud === 'Rechazada') badgeClass = 'estado-rechazado';
                if (item.estadoSolicitud === 'Cancelada') badgeClass = 'estado-rechazado';

                const puedeCancelar = item.estadoSolicitud === 'Pendiente' || item.estadoSolicitud === 'Aprobada';

                tr.innerHTML = `
                    <td class="texto-resaltado">${item.nombreMateria}</td>
                    <td>${item.nombreTutor || 'Tutor UMG'}</td>
                    <td>${item.fecha} - ${item.horaInicio}</td>
                    <td><span class="estado-badge ${badgeClass}">${item.estadoSolicitud}</span></td>
                    <td class="texto-derecha">
                        ${puedeCancelar ? `
                            <button type="button" class="btn btn-peligro-suave" onclick="cancelarSolicitudEstudiante(${item.idSolicitud})">
                                Cancelar
                            </button>
                        ` : `
                            <span style="font-size: 0.75rem; color: #888;">N/A</span>
                        `}
                    </td>
                `;
                tablaBody.appendChild(tr);
            });
        } else {
            tablaBody.innerHTML = `
                <tr>
                    <td colspan="5" class="texto-centro" style="padding: 1.5rem; color: #666;">
                        No tienes solicitudes de tutoría reservadas actualmente.
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error al cargar solicitudes del estudiante:', error);
    }
}

async function cancelarSolicitudEstudiante(idSolicitud) {
    if (!confirm('¿Estás seguro de que deseas cancelar esta solicitud de tutoría?')) return;

    try {
        const respuesta = await fetch('api/solicitudes.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                idSolicitud: idSolicitud,
                estadoSolicitud: 'Cancelada'
            })
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            alert('✅ La solicitud ha sido cancelada exitosamente.');
            cargarSolicitudesEstudiante();
            cargarDisponibilidad();
            cargarMetricsDashboard();
        } else {
            alert('❌ ' + (resultado.mensaje || 'Error al cancelar la solicitud.'));
        }
    } catch (error) {
        console.error('Error al cancelar solicitud:', error);
        alert('Error de conexión al intentar cancelar.');
    }
}

// ---------------------------------------------------------------------------
// 6. SOLICITUDES RECIBIDAS (VISTA TUTOR -> ACEPTAR/RECHAZAR EN BASE DE DATOS)
// ---------------------------------------------------------------------------

async function cargarSolicitudesTutor() {
    if (!usuarioAutenticado || !usuarioAutenticado.idTutor) return;

    const tablaBody = document.getElementById('tabla-solicitudes-tutor');
    if (!tablaBody) return;

    try {
        const respuesta = await fetch(`api/solicitudes.php?idTutor=${usuarioAutenticado.idTutor}`);
        if (!respuesta.ok) return;

        const resultado = await respuesta.json();
        tablaBody.innerHTML = '';

        if (resultado.exito && Array.isArray(resultado.datos) && resultado.datos.length > 0) {
            resultado.datos.forEach(item => {
                const tr = document.createElement('tr');

                let badgeClass = 'estado-pendiente';
                if (item.estadoSolicitud === 'Aprobada') badgeClass = 'estado-aprobado';
                if (item.estadoSolicitud === 'Rechazada') badgeClass = 'estado-rechazado';
                if (item.estadoSolicitud === 'Cancelada') badgeClass = 'estado-rechazado';

                const esPendiente = item.estadoSolicitud === 'Pendiente';

                tr.innerHTML = `
                    <td class="texto-resaltado">${item.nombreEstudiante || 'Estudiante'}</td>
                    <td>${item.nombreMateria}</td>
                    <td>${item.fecha} - ${item.horaInicio}</td>
                    <td class="texto-centro">
                        ${esPendiente ? `
                            <div class="contenedor-acciones">
                                <button type="button" class="btn btn-exito-suave" onclick="cambiarEstadoSolicitudTutor(${item.idSolicitud}, 'Aprobada')">
                                    Aceptar
                                </button>
                                <button type="button" class="btn btn-peligro-suave" onclick="cambiarEstadoSolicitudTutor(${item.idSolicitud}, 'Rechazada')">
                                    Rechazar
                                </button>
                            </div>
                        ` : `
                            <span class="estado-badge ${badgeClass}">${item.estadoSolicitud}</span>
                        `}
                    </td>
                `;
                tablaBody.appendChild(tr);
            });
        } else {
            tablaBody.innerHTML = `
                <tr>
                    <td colspan="4" class="texto-centro" style="padding: 1.5rem; color: #666;">
                        No has recibido solicitudes de tutoría aún.
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error al cargar solicitudes del tutor:', error);
    }
}

async function cambiarEstadoSolicitudTutor(idSolicitud, nuevoEstado) {
    const accionTexto = nuevoEstado === 'Aprobada' ? 'aceptar' : 'rechazar';
    if (!confirm(`¿Estás seguro de que deseas ${accionTexto} esta solicitud?`)) return;

    try {
        const respuesta = await fetch('api/solicitudes.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                idSolicitud: idSolicitud,
                estadoSolicitud: nuevoEstado
            })
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            alert(`✅ La solicitud ha sido ${nuevoEstado.toLowerCase()} correctamente.`);
            cargarSolicitudesTutor();
            cargarMetricsDashboard();
        } else {
            alert('❌ ' + (resultado.mensaje || 'Error al actualizar el estado.'));
        }
    } catch (error) {
        console.error('Error al actualizar estado:', error);
        alert('Error de conexión con el servidor.');
    }
}

async function guardarDisponibilidad(event) {
    if (event) event.preventDefault();

    if (!usuarioAutenticado || !usuarioAutenticado.idTutor) {
        alert('Acceso no autorizado. Debes iniciar sesión como tutor.');
        return;
    }

    const materia = document.getElementById('disp-materia').value.trim();
    const fecha   = document.getElementById('disp-fecha').value;
    const hInicio = document.getElementById('disp-inicio').value;
    const hFin    = document.getElementById('disp-fin').value;

    if (!materia || !fecha || !hInicio || !hFin) {
        alert('Por favor completa todos los campos del horario.');
        return;
    }

    try {
        const respuesta = await fetch('api/disponibilidad.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                idTutor: usuarioAutenticado.idTutor,
                nombreMateria: materia,
                fecha: fecha,
                horaInicio: hInicio,
                horaFin: hFin
            })
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            alert('✅ ' + resultado.mensaje);
            document.getElementById('form-disponibilidad').reset();
            cargarSolicitudesTutor();
            cargarMetricsDashboard();
        } else {
            alert('❌ ' + resultado.mensaje);
        }
    } catch (error) {
        console.error('Error al guardar disponibilidad:', error);
        alert('Error de conexión con el servidor.');
    }
}

// ---------------------------------------------------------------------------
// 7. DASHBOARD METRICS
// ---------------------------------------------------------------------------

async function cargarMetricsDashboard() {
    try {
        const respuesta = await fetch('api/dashboard.php');
        if (!respuesta.ok) return;
        const resultado = await respuesta.json();

        if (resultado.exito && resultado.metricas) {
            const m = resultado.metricas;
            const elTotal     = document.getElementById('metric-total');
            const elAprobadas = document.getElementById('metric-aprobadas');
            const elPend      = document.getElementById('metric-pendientes');
            const elTasa      = document.getElementById('metric-tasa');

            if (elTotal) elTotal.innerText = m.total_solicitudes ?? 0;
            if (elAprobadas) elAprobadas.innerText = m.aprobadas ?? 0;
            if (elPend) elPend.innerText = m.pendientes ?? 0;
            if (elTasa) elTasa.innerText = m.tasa_atencion ?? '0%';
        }
    } catch (error) {
        console.error('Error al cargar métricas:', error);
    }
}

// ---------------------------------------------------------------------------
// 8. EVENT LISTENERS Y FUNCIONES GLOBALES
// ---------------------------------------------------------------------------

function configurarEventos() {
    const btnCerrarSesion = document.getElementById('btn-cerrar-sesion') || document.getElementById('btn-logout');
    if (btnCerrarSesion) {
        btnCerrarSesion.addEventListener('click', cerrarSesion);
    }
}

// Exponer funciones globales para el HTML
window.procesarLogin = procesarLogin;
window.cerrarSesion = cerrarSesion;
window.solicitarTutoria = solicitarTutoria;
window.cancelarSolicitudEstudiante = cancelarSolicitudEstudiante;
window.cambiarEstadoSolicitudTutor = cambiarEstadoSolicitudTutor;
window.guardarDisponibilidad = guardarDisponibilidad;
