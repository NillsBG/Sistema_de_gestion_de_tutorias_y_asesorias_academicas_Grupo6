let usuarioAutenticado = null;

document.addEventListener('DOMContentLoaded', () => {
    verificarSesion();
    configurarEventos();
    cargarDisponibilidad();
    cargarMetricsDashboard();
});

// ----------------------------------------------------------------------------
// 1. CONTROL DE SESIÓN Y AUTENTICACIÓN
// ----------------------------------------------------------------------------

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
 * Procesa el inicio de sesión desde el formulario
 */
async function procesarLogin(event) {
    if (event) event.preventDefault(); // Detiene la recarga de página

    const correoInput = document.getElementById('login-correo');
    const claveInput  = document.getElementById('login-clave');
    const formLogin   = document.getElementById('form-login');

    if (!correoInput || !claveInput) {
        alert('Error: No se encontraron los campos de inicio de sesión.');
        return;
    }

    const correo = correoInput.value.trim();
    const clave  = claveInput.value.trim();

    // Leer el rol seleccionado (estudiante / tutor)
    const rolRadio = document.querySelector('input[name="rol"]:checked');
    const rolSeleccionado = rolRadio ? rolRadio.value : 'estudiante';

    if (!correo || !clave) {
        alert('Por favor ingresa tu correo y contraseña.');
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

            // 1. Limpiar campos del formulario de login
            if (formLogin) {
                formLogin.reset();
            } else {
                correoInput.value = '';
                claveInput.value = '';
            }

            // 2. Transición de vistas visuales en el HTML
            actualizarInterfazUsuario();

            // 3. Cargar datos requeridos en segundo plano
            cargarDisponibilidad();
            cargarMetricsDashboard();
            cargarSolicitudesUsuario();

        } else {
            alert(resultado.mensaje || 'Credenciales incorrectas');
        }
    } catch (error) {
        console.error('Error en procesarLogin:', error);
        alert('Error de conexión al intentar ingresar.');
    }
}

/**
 * Cierra la sesión activa y borra cualquier residuo de credenciales
 */
async function cerrarSesion() {
    try {
        await fetch('php/logout.php');
    } catch (e) {
        console.error('Error al cerrar sesión:', e);
    }

    usuarioAutenticado = null;

    // Limpiar campos de login al cerrar sesión
    const formLogin = document.getElementById('form-login');
    if (formLogin) formLogin.reset();

    const correoInput = document.getElementById('login-correo');
    const claveInput  = document.getElementById('login-clave');
    if (correoInput) correoInput.value = '';
    if (claveInput) claveInput.value = '';

    actualizarInterfazUsuario();
    alert('Sesión cerrada correctamente.');
}

// ----------------------------------------------------------------------------
// 2. CONTROL VISUAL DE VISTAS (INDEX.HTML)
// ----------------------------------------------------------------------------

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
        } else {
            if (vistaEstudiante) vistaEstudiante.classList.remove('oculto');
            if (vistaTutor) vistaTutor.classList.add('oculto');
        }
    } else {
        if (vistaLogin) vistaLogin.classList.remove('oculto');
        if (encabezado) encabezado.classList.add('oculto');
        if (vistaEstudiante) vistaEstudiante.classList.add('oculto');
        if (vistaTutor) vistaTutor.classList.add('oculto');
    }
}

// ----------------------------------------------------------------------------
// 3. CARGA DE DISPONIBILIDAD Y DASHBOARD
// ----------------------------------------------------------------------------

async function cargarDisponibilidad() {
    const contenedor = document.getElementById('contenedor-disponibilidad') || document.getElementById('lista-disponibilidad');
    if (!contenedor) return;

    try {
        const respuesta = await fetch('api/disponibilidad.php');
        if (!respuesta.ok) return;
        const resultado = await respuesta.json();

        contenedor.innerHTML = '';

        if (resultado.exito && Array.isArray(resultado.datos) && resultado.datos.length > 0) {
            resultado.datos.forEach(item => {
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
                        <h4><i class="fa-solid fa-user-tie"></i> ${tutor}</h4>
                        <p><i class="fa-regular fa-calendar"></i> Fecha: <strong>${fecha}</strong></p>
                        <p><i class="fa-regular fa-clock"></i> Horario: <strong>${hInicio} - ${hFin}</strong></p>
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
                    <p><i class="fa-solid fa-circle-info"></i> No hay tutorías disponibles en este momento.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error al cargar disponibilidad:', error);
    }
}

/**
 * Carga estadísticas y métricas sin interrumpir la ejecución si falla
 */
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
        console.error('Error al cargar métricas del dashboard:', error);
    }
}

// ----------------------------------------------------------------------------
// 4. RESERVA Y CONSULTA DE SOLICITUDES
// ----------------------------------------------------------------------------

async function solicitarTutoria(idDisponibilidad) {
    if (!usuarioAutenticado) {
        alert('Debes iniciar sesión para solicitar una tutoría.');
        return;
    }

    const noCarnet = usuarioAutenticado.noCarnetCompleto || usuarioAutenticado.username;

    if (!confirm('¿Deseas solicitar este horario de tutoría?')) return;

    try {
        const resVal = await fetch('api/validar_reserva.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ noCarnetEstudiante: noCarnet, idDisponibilidad: idDisponibilidad })
        });
        const valData = await resVal.json();

        if (!valData.valido) {
            alert('❌ ' + (valData.mensaje || 'No se puede reservar este horario.'));
            return;
        }

        const resSol = await fetch('api/solicitudes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ noCarnetEstudiante: noCarnet, idDisponibilidad: idDisponibilidad })
        });
        const solData = await resSol.json();

        if (solData.exito) {
            alert('✅ Solicitud enviada exitosamente.');
            cargarDisponibilidad();
            cargarMetricsDashboard();
            cargarSolicitudesUsuario();
        } else {
            alert('❌ ' + (solData.mensaje || 'Error al enviar solicitud.'));
        }
    } catch (error) {
        console.error('Error al procesar tutoría:', error);
        alert('Error de conexión al procesar la reserva.');
    }
}

async function cargarSolicitudesUsuario() {
    if (!usuarioAutenticado) return;

    const tablaBody = document.getElementById('tabla-solicitudes-body') || document.getElementById('lista-solicitudes');
    if (!tablaBody) return;

    const noCarnet = usuarioAutenticado.noCarnetCompleto || usuarioAutenticado.username;
    const idTutor  = usuarioAutenticado.idTutor;

    let url = 'api/solicitudes.php?';
    if (idTutor) {
        url += 'idTutor=' + idTutor;
    } else {
        url += 'noCarnetEstudiante=' + encodeURIComponent(noCarnet);
    }

    try {
        const respuesta = await fetch(url);
        if (!respuesta.ok) return;
        const resultado = await respuesta.json();

        tablaBody.innerHTML = '';

        if (resultado.exito && Array.isArray(resultado.datos) && resultado.datos.length > 0) {
            resultado.datos.forEach(s => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${s.idSolicitud}</td>
                    <td><strong>${s.nombreMateria}</strong></td>
                    <td>${s.nombreEstudiante || s.nombreTutor || 'Usuario'}</td>
                    <td>${s.fecha} ${s.horaInicio}</td>
                    <td><span class="badge">${s.estadoSolicitud}</span></td>
                `;
                tablaBody.appendChild(tr);
            });
        }
    } catch (error) {
        console.error('Error al cargar solicitudes:', error);
    }
}

// ----------------------------------------------------------------------------
// 5. EVENT LISTENERS
// ----------------------------------------------------------------------------

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
window.cargarMetricsDashboard = cargarMetricsDashboard;

/**
 * Envía el formulario para guardar o publicar una nueva disponibilidad
 */
async function guardarDisponibilidad(event) {
    if (event) event.preventDefault();

    if (!usuarioAutenticado || !usuarioAutenticado.idTutor) {
        alert('Acceso no autorizado. Debes iniciar sesión como tutor.');
        return;
    }

    const materia = document.getElementById('disp-materia').value.trim();
    const fecha = document.getElementById('disp-fecha').value;
    const horaInicio = document.getElementById('disp-inicio').value;
    const horaFin = document.getElementById('disp-fin').value;

    try {
        const respuesta = await fetch('api/disponibilidad.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                idTutor: usuarioAutenticado.idTutor,
                nombreMateria: materia,
                fecha: fecha,
                horaInicio: horaInicio,
                horaFin: horaFin
            })
        });

        const resultado = await respuesta.json();
        if (resultado.exito) {
            alert('✅ ' + resultado.mensaje);
            document.getElementById('form-disponibilidad').reset();
            cargarDisponibilidad(); // Refrescar vistas
        } else {
            alert('❌ ' + resultado.mensaje);
        }
    } catch (error) {
        console.error('Error al guardar disponibilidad:', error);
        alert('Error de conexión con el servidor.');
    }
}

/**
 * Elimina un horario de disponibilidad publicado por el tutor
 */
async function eliminarDisponibilidad(idDisponibilidad) {
    if (!confirm('¿Estás seguro de eliminar este horario publicado?')) return;

    try {
        const respuesta = await fetch('api/disponibilidad.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idDisponibilidad: idDisponibilidad })
        });

        const resultado = await respuesta.json();
        if (resultado.exito) {
            alert('✅ ' + resultado.mensaje);
            cargarDisponibilidad();
        } else {
            alert('❌ ' + resultado.mensaje);
        }
    } catch (error) {
        console.error('Error al eliminar:', error);
        alert('Error al conectar con el servidor.');
    }
}