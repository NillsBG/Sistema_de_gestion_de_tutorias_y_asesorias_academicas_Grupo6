// ============================================================================
// PROYECTO FINAL: SISTEMA DE GESTIÓN DE TUTORÍAS Y ASESORÍAS ACADÉMICAS (UMG)
// Archivo: js/app.js
// Integración Frontend - Lógica de Interfaz, Autenticación, Disponibilidad y Reservas
// ============================================================================

// ----------------------------------------------------------------------------
// 1. ESTADO GLOBAL DE LA APLICACIÓN
// ----------------------------------------------------------------------------
let usuarioAutenticado = null;

// ----------------------------------------------------------------------------
// 2. INICIALIZACIÓN AL CARGAR EL DOCUMENTO
// ----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    verificarSesion();
    configurarEventosForms();
    cargarDisponibilidad();
    cargarMetricsDashboard();
});

// ----------------------------------------------------------------------------
// 3. CONTROL DE SESIÓN Y AUTENTICACIÓN
// ----------------------------------------------------------------------------

/**
 * Verifica si existe una sesión activa en PHP
 */
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

/**
 * Inicia sesión procesando el formulario de login
 */
async function iniciarSesion(event) {
    if (event) event.preventDefault();

    const inputUser = document.getElementById('username') || document.getElementById('correo') || document.getElementById('login-usuario');
    const inputPass = document.getElementById('contrasena') || document.getElementById('clave') || document.getElementById('login-password');

    if (!inputUser || !inputPass) {
        alert('Por favor completa todos los campos del formulario de inicio de sesión.');
        return;
    }

    const username = inputUser.value.trim();
    const contrasena = inputPass.value.trim();

    if (!username || !contrasena) {
        alert('Por favor, ingresa tu usuario y contraseña.');
        return;
    }

    try {
        const respuesta = await fetch('php/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, contrasena })
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            usuarioAutenticado = resultado.usuario;
            alert(resultado.mensaje || '¡Bienvenido al sistema!');
            
            // Limpiar formulario y cerrar modal si existe
            if (inputUser.form) inputUser.form.reset();
            const modalLogin = document.getElementById('modal-login');
            if (modalLogin) modalLogin.style.display = 'none';

            actualizarInterfazUsuario();
            cargarDisponibilidad();
            cargarSolicitudesUsuario();
            cargarMetricsDashboard();
        } else {
            alert('Error: ' + (resultado.mensaje || 'Credenciales incorrectas'));
        }
    } catch (error) {
        console.error('Error en login:', error);
        alert('Error al conectar con el servidor de autenticación.');
    }
}

/**
 * Cierra la sesión activa
 */
async function cerrarSesion() {
    try {
        const respuesta = await fetch('php/logout.php');
        const resultado = await respuesta.json();

        if (resultado.exito) {
            usuarioAutenticado = null;
            alert('Sesión cerrada correctamente.');
            actualizarInterfazUsuario();
            cargarDisponibilidad();
            cargarMetricsDashboard();
        }
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        usuarioAutenticado = null;
        actualizarInterfazUsuario();
    }
}

// ----------------------------------------------------------------------------
// 4. ACTUALIZACIÓN DINÁMICA DE LA INTERFAZ
// ----------------------------------------------------------------------------

function actualizarInterfazUsuario() {
    const contenedorUsuario = document.getElementById('info-usuario');
    const btnLoginModal    = document.getElementById('btn-abrir-login');
    const btnLogout        = document.getElementById('btn-logout');
    const panelEstudiante  = document.getElementById('panel-estudiante');
    const panelTutor       = document.getElementById('panel-tutor');
    const formDisponibilidad = document.getElementById('form-publicar-disponibilidad');

    if (usuarioAutenticado) {
        const nombre = usuarioAutenticado.nombre || usuarioAutenticado.username;
        const rol    = usuarioAutenticado.rol || 'Usuario';

        if (contenedorUsuario) {
            contenedorUsuario.innerHTML = `
                <span class="badge-rol">${rol}</span>
                <strong>${nombre}</strong>
            `;
            contenedorUsuario.style.display = 'inline-block';
        }

        if (btnLoginModal) btnLoginModal.style.display = 'none';
        if (btnLogout) btnLogout.style.display = 'inline-block';

        // Mostrar u ocultar paneles según el rol
        const esTutor = rol.toLowerCase() === 'tutor' || rol.toLowerCase() === 'administrador';

        if (panelTutor) panelTutor.style.display = esTutor ? 'block' : 'none';
        if (formDisponibilidad) formDisponibilidad.style.display = esTutor ? 'block' : 'none';
        if (panelEstudiante) panelEstudiante.style.display = !esTutor ? 'block' : 'none';

        cargarSolicitudesUsuario();
    } else {
        if (contenedorUsuario) contenedorUsuario.style.display = 'none';
        if (btnLoginModal) btnLoginModal.style.display = 'inline-block';
        if (btnLogout) btnLogout.style.display = 'none';
        if (panelTutor) panelTutor.style.display = 'none';
        if (formDisponibilidad) formDisponibilidad.style.display = 'none';
        if (panelEstudiante) panelEstudiante.style.display = 'none';
    }
}

// ----------------------------------------------------------------------------
// 5. CARGA Y MUESTRA DE DISPONIBILIDAD (DISPONIBILIDAD DE TUTORÍAS)
// ----------------------------------------------------------------------------

/**
 * Consulta las tutorías disponibles en api/disponibilidad.php
 */
async function cargarDisponibilidad() {
    const contenedor = document.getElementById('contenedor-disponibilidad') || document.getElementById('lista-disponibilidad');
    if (!contenedor) return;

    try {
        const respuesta = await fetch('api/disponibilidad.php');
        const resultado = await respuesta.json();

        contenedor.innerHTML = '';

        if (resultado.exito && Array.isArray(resultado.datos) && resultado.datos.length > 0) {
            resultado.datos.forEach(item => {
                // Normalización de nombres de columnas
                const idDisp  = item.idDisponibilidad || item.id;
                const materia = item.nombreMateria || item.materia || 'Tutoría Académica';
                const tutor   = item.nombreTutor || item.tutor || 'Tutor UMG';
                const fecha   = item.fecha || '';
                const hInicio = item.horaInicio || item.hora_inicio || '';
                const hFin    = item.horaFin || item.hora_fin || '';

                const tarjeta = document.createElement('div');
                tarjeta.className = 'tarjeta-disponibilidad';
                tarjeta.innerHTML = `
                    <div class="tarjeta-header">
                        <span class="materia-tag">${materia}</span>
                    </div>
                    <div class="tarjeta-body">
                        <h4 class="nombre-tutor"><i class="fa-solid fa-user-tie"></i> ${tutor}</h4>
                        <p class="detalle-horario"><i class="fa-regular fa-calendar"></i> Fecha: <strong>${fecha}</strong></p>
                        <p class="detalle-horario"><i class="fa-regular fa-clock"></i> Hora: <strong>${hInicio} - ${hFin}</strong></p>
                    </div>
                    <div class="tarjeta-footer">
                        <button onclick="solicitarTutoria(${idDisp})" class="btn btn-solicitar">
                            <i class="fa-solid fa-paper-plane"></i> Solicitar Tutoría
                        </button>
                    </div>
                `;
                contenedor.appendChild(tarjeta);
            });
        } else {
            contenedor.innerHTML = `
                <div class="alerta-vacio">
                    <p><i class="fa-solid fa-circle-info"></i> No hay horarios de tutoría disponibles en este momento.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error al cargar disponibilidad:', error);
        contenedor.innerHTML = `
            <div class="alerta-error">
                <p>Error al conectar con la base de datos de disponibilidades.</p>
            </div>
        `;
    }
}

// ----------------------------------------------------------------------------
// 6. SOLICITUD Y RESERVA DE TUTORÍAS (VALIDACIÓN DE CRUCES)
// ----------------------------------------------------------------------------

/**
 * Valida y procesa la solicitud de tutoría de un estudiante
 */
async function solicitarTutoria(idDisponibilidad) {
    if (!usuarioAutenticado) {
        alert('Debes iniciar sesión con tu cuenta de estudiante para solicitar una tutoría.');
        const btnLogin = document.getElementById('btn-abrir-login');
        if (btnLogin) btnLogin.click();
        return;
    }

    const noCarnet = usuarioAutenticado.noCarnetCompleto || usuarioAutenticado.username;

    if (!noCarnet) {
        alert('No se encontró el número de carnet asociado a tu usuario.');
        return;
    }

    if (!confirm('¿Deseas confirmar la solicitud para este horario de tutoría?')) {
        return;
    }

    try {
        // STEP 1: Validar traslapes/cruces y solicitudes duplicadas con Nills API
        const resValidar = await fetch('api/validar_reserva.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                noCarnetEstudiante: noCarnet,
                idDisponibilidad: idDisponibilidad
            })
        });

        const valData = await resValidar.json();

        if (!valData.valido) {
            alert('❌ ' + (valData.mensaje || 'No se puede reservar este horario.'));
            return;
        }

        // STEP 2: Crear la solicitud en la base de datos
        const resSolicitud = await fetch('api/solicitudes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                noCarnetEstudiante: noCarnet,
                idDisponibilidad: idDisponibilidad,
                estudiante_id: noCarnet,
                disponibilidad_id: idDisponibilidad
            })
        });

        const solData = await resSolicitud.json();

        if (solData.exito || solData.valido) {
            alert('✅ ' + (solData.mensaje || 'Solicitud registrada exitosamente.'));
            cargarDisponibilidad();
            cargarSolicitudesUsuario();
            cargarMetricsDashboard();
        } else {
            alert('❌ ' + (solData.mensaje || 'Error al registrar la solicitud.'));
        }

    } catch (error) {
        console.error('Error al procesar la reserva:', error);
        alert('Error de conexión al procesar la solicitud.');
    }
}

// ----------------------------------------------------------------------------
// 7. PUBLICACIÓN DE DISPONIBILIDAD POR PARTE DE TUTORES
// ----------------------------------------------------------------------------

async function publicarDisponibilidad(event) {
    if (event) event.preventDefault();

    if (!usuarioAutenticado || !usuarioAutenticado.idTutor) {
        alert('Debes iniciar sesión como Tutor para publicar horarios.');
        return;
    }

    const inputMateria = document.getElementById('input-materia');
    const inputFecha   = document.getElementById('input-fecha');
    const inputInicio  = document.getElementById('input-hora-inicio');
    const inputFin     = document.getElementById('input-hora-fin');

    if (!inputMateria || !inputFecha || !inputInicio || !inputFin) {
        alert('Error: Campos de formulario no encontrados.');
        return;
    }

    const payload = {
        idTutor: usuarioAutenticado.idTutor,
        tutor_id: usuarioAutenticado.idTutor,
        nombreMateria: inputMateria.value.trim(),
        materia: inputMateria.value.trim(),
        fecha: inputFecha.value,
        horaInicio: inputInicio.value,
        hora_inicio: inputInicio.value,
        horaFin: inputFin.value,
        hora_fin: inputFin.value
    };

    try {
        const respuesta = await fetch('api/disponibilidad.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            alert('✅ ' + (resultado.mensaje || 'Horario de tutoría publicado correctamente.'));
            if (inputMateria.form) inputMateria.form.reset();
            cargarDisponibilidad();
        } else {
            alert('❌ ' + (resultado.mensaje || 'Error al publicar disponibilidad.'));
        }
    } catch (error) {
        console.error('Error al publicar disponibilidad:', error);
        alert('Error de conexión al guardar el horario.');
    }
}

// ----------------------------------------------------------------------------
// 8. GESTIÓN Y LISTADO DE SOLICITUDES (ESTUDIANTE Y TUTOR)
// ----------------------------------------------------------------------------

async function cargarSolicitudesUsuario() {
    if (!usuarioAutenticado) return;

    const tablaSolicitudes = document.getElementById('tabla-solicitudes-body') || document.getElementById('lista-solicitudes');
    if (!tablaSolicitudes) return;

    const noCarnet = usuarioAutenticado.noCarnetCompleto || usuarioAutenticado.username;
    const idTutor  = usuarioAutenticado.idTutor;

    let url = 'api/solicitudes.php?';
    if (idTutor) {
        url += 'idTutor=' + idTutor + '&tutor_id=' + idTutor;
    } else {
        url += 'noCarnetEstudiante=' + encodeURIComponent(noCarnet) + '&estudiante_id=' + encodeURIComponent(noCarnet);
    }

    try {
        const respuesta = await fetch(url);
        const resultado = await respuesta.json();

        tablaSolicitudes.innerHTML = '';

        if (resultado.exito && Array.isArray(resultado.datos) && resultado.datos.length > 0) {
            resultado.datos.forEach(s => {
                const idSol   = s.idSolicitud || s.id;
                const materia = s.nombreMateria || s.materia || 'Tutoría';
                const persona = s.nombreEstudiante || s.nombreTutor || s.tutor || s.estudiante || 'Usuario';
                const fecha   = s.fecha || '';
                const hora    = s.horaInicio || s.hora_inicio || '';
                const estado  = s.estadoSolicitud || s.estado || 'Pendiente';

                let badgeClass = 'badge-pendiente';
                if (estado === 'Aprobada') badgeClass = 'badge-aprobada';
                if (estado === 'Rechazada') badgeClass = 'badge-rechazada';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${idSol}</td>
                    <td><strong>${materia}</strong></td>
                    <td>${persona}</td>
                    <td>${fecha} ${hora}</td>
                    <td><span class="badge ${badgeClass}">${estado}</span></td>
                    <td>
                        ${idTutor && estado === 'Pendiente' ? `
                            <button onclick="actualizarEstadoSolicitud(${idSol}, 'Aprobada')" class="btn btn-sm btn-aprobar">Aprobar</button>
                            <button onclick="actualizarEstadoSolicitud(${idSol}, 'Rechazada')" class="btn btn-sm btn-rechazar">Rechazar</button>
                        ` : '-'}
                    </td>
                `;
                tablaSolicitudes.appendChild(tr);
            });
        } else {
            tablaSolicitudes.innerHTML = `<tr><td colspan="6" class="text-center">No hay solicitudes registradas.</td></tr>`;
        }
    } catch (error) {
        console.error('Error al cargar solicitudes:', error);
    }
}

async function actualizarEstadoSolicitud(idSolicitud, nuevoEstado) {
    try {
        const respuesta = await fetch('api/solicitudes.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                idSolicitud: idSolicitud,
                solicitud_id: idSolicitud,
                estadoSolicitud: nuevoEstado,
                estado: nuevoEstado
            })
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            alert('Estado de la solicitud actualizado a: ' + nuevoEstado);
            cargarSolicitudesUsuario();
            cargarMetricsDashboard();
        } else {
            alert('Error al actualizar el estado.');
        }
    } catch (error) {
        console.error('Error al actualizar estado:', error);
    }
}

// ----------------------------------------------------------------------------
// 9. INDICADORES Y MÉTRICAS DEL DASHBOARD
// ----------------------------------------------------------------------------

async function cargarMetricsDashboard() {
    try {
        const respuesta = await fetch('api/dashboard.php');
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
        console.error('Error al cargar métricas del dashboard:', error);
    }
}

// ----------------------------------------------------------------------------
// 10. CONFIGURACIÓN DE LISTENERS Y EVENTOS
// ----------------------------------------------------------------------------

function configurarEventosForms() {
    const formLogin = document.getElementById('form-login');
    if (formLogin) {
        formLogin.addEventListener('submit', iniciarSesion);
    }

    const formDisp = document.getElementById('form-publicar-disponibilidad');
    if (formDisp) {
        formDisp.addEventListener('submit', publicarDisponibilidad);
    }

    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', cerrarSesion);
    }
}
