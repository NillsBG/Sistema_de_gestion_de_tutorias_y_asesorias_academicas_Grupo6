let usuarioAutenticado = null;
let listaDisponibilidadGlobal = [];
let carruselPosicionActual = 0;

document.addEventListener('DOMContentLoaded', () => {
    verificarSesion();
    configurarEventos();
    cargarDisponibilidad();
    cargarMetricsDashboard();
});

// ---------------------------------------------------------------------------
// 1. AUTENTICACIÓN Y SESIÓN
// ---------------------------------------------------------------------------

async function verificarSesion() {
    try {
        const respuesta = await fetch('php/session_check.php');
        const data = await respuesta.json();

        if (data.autenticado && data.usuario) {
            usuarioAutenticado = data.usuario;
            actualizarInterfazUsuario();
            cargarDisponibilidad();
            cargarSolicitudesUsuario();
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

    const usuarioInput = document.getElementById('login-usuario');
    const claveInput   = document.getElementById('login-clave');
    const formLogin    = document.getElementById('form-login');

    if (!usuarioInput || !claveInput) return;

    const usuarioVal = usuarioInput.value.trim();
    const claveVal   = claveInput.value.trim();

    if (!usuarioVal || !claveVal) {
        alert('Por favor ingresa tu usuario y contraseña.');
        return;
    }

    try {
        const respuesta = await fetch('php/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario: usuarioVal, clave: claveVal })
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            usuarioAutenticado = resultado.usuario;
            alert('¡Bienvenido ' + (resultado.usuario.nombre || resultado.usuario.username) + '! (' + resultado.usuario.rol + ')');

            if (formLogin) formLogin.reset();

            actualizarInterfazUsuario();
            cargarDisponibilidad();
            cargarMetricsDashboard();
            cargarSolicitudesUsuario();
        } else {
            alert('❌ ' + (resultado.mensaje || 'Credenciales incorrectas'));
        }
    } catch (error) {
        console.error('Error en procesarLogin:', error);
        alert('Error de conexión al intentar ingresar.');
    }
}

async function cerrarSesion() {
    try { await fetch('php/logout.php'); } catch (e) {}
    usuarioAutenticado = null;

    const formLogin = document.getElementById('form-login');
    if (formLogin) formLogin.reset();

    actualizarInterfazUsuario();
    alert('Sesión cerrada correctamente.');
}

function actualizarInterfazUsuario() {
    const vistaLogin      = document.getElementById('vista-login');
    const vistaEstudiante = document.getElementById('vista-estudiante');
    const vistaTutor      = document.getElementById('vista-tutor');
    const encabezado      = document.getElementById('encabezado');

    const infoNombre = document.getElementById('info-usuario-nombre');
    const infoRol    = document.getElementById('info-usuario-rol');
    const badgeCarrera = document.getElementById('nombre-carrera-texto');

    if (usuarioAutenticado) {
        const nombre = usuarioAutenticado.nombre || usuarioAutenticado.username;
        const rol    = usuarioAutenticado.rol || 'Usuario';
        const codCarrera = usuarioAutenticado.codigoCarrera || '0901';
        const nomCarrera = usuarioAutenticado.nombreCarrera || 'Ingeniería en Sistemas';

        if (infoNombre) infoNombre.innerText = nombre;
        if (infoRol) infoRol.innerText = rol;
        if (badgeCarrera) badgeCarrera.innerText = `${codCarrera} - ${nomCarrera}`;

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

// ---------------------------------------------------------------------------
// 2. CARGA DE DISPONIBILIDAD POR CARRERA Y LÓGICA DEL SLIDER
// ---------------------------------------------------------------------------

async function cargarDisponibilidad() {
    const contenedor = document.getElementById('contenedor-disponibilidad');
    if (!contenedor) return;

    let codCarrera = '0901';
    if (usuarioAutenticado && usuarioAutenticado.codigoCarrera) {
        codCarrera = usuarioAutenticado.codigoCarrera;
    }

    try {
        const respuesta = await fetch(`api/disponibilidad.php?codigoCarrera=${encodeURIComponent(codCarrera)}`);
        if (!respuesta.ok) return;
        const resultado = await respuesta.json();

        if (resultado.exito && Array.isArray(resultado.datos)) {
            listaDisponibilidadGlobal = resultado.datos;
            carruselPosicionActual = 0;
            renderizarTarjetasDisponibilidad(listaDisponibilidadGlobal);
        }
    } catch (error) {
        console.error('Error al cargar disponibilidad:', error);
    }
}

function renderizarTarjetasDisponibilidad(datos) {
    const contenedor = document.getElementById('contenedor-disponibilidad');
    const contIndicadores = document.getElementById('carrusel-indicadores');
    if (!contenedor) return;

    contenedor.innerHTML = '';
    if (contIndicadores) contIndicadores.innerHTML = '';

    if (Array.isArray(datos) && datos.length > 0) {
        datos.forEach(item => {
            const idDisp  = item.idDisponibilidad || item.id;
            const materia = item.nombreMateria || 'Tutoría Académica';
            const tutor   = item.nombreTutor || 'Tutor UMG';
            const fecha   = item.fecha || '';
            const hInicio = item.horaInicio || '';
            const hFin    = item.horaFin || '';

            const tarjeta = document.createElement('div');
            tarjeta.className = 'tarjeta-carrusel-item';
            tarjeta.innerHTML = `
                <div class="tarjeta-carrusel-contenido">
                    <div>
                        <div class="encabezado-materia">
                            <span class="etiqueta-materia">${materia}</span>
                            <span class="duracion-materia"><i class="fa-regular fa-clock"></i> Cita</span>
                        </div>
                        <h4 class="nombre-tutor">${tutor}</h4>
                        <div class="detalles-horario">
                            <div><i class="fa-regular fa-calendar"></i> Fecha: <strong>${fecha}</strong></div>
                            <div><i class="fa-solid fa-clock"></i> Horario: <strong>${hInicio} - ${hFin}</strong></div>
                        </div>
                    </div>
                    <button type="button" onclick="solicitarTutoria(${idDisp})" class="btn btn-oro ancho-total mt-sm">
                        <i class="fa-solid fa-paper-plane"></i> Solicitar Tutoría
                    </button>
                </div>
            `;
            contenedor.appendChild(tarjeta);
        });

        // Crear Puntos de Paginación
        if (contIndicadores) {
            const porVista = obtenerTarjetasPorVista();
            const totalPaginas = Math.ceil(datos.length / porVista);

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
                <p class="subtitulo-seccion"><i class="fa-solid fa-circle-info"></i> No hay tutorías disponibles de tu carrera actualmente.</p>
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

function moverCarrusel(direccion) {
    const contenedor = document.getElementById('contenedor-disponibilidad');
    if (!contenedor) return;

    const totalItems = contenedor.children.length;
    const porVista   = obtenerTarjetasPorVista();
    const maxIndice  = Math.max(0, Math.ceil(totalItems / porVista) - 1);

    carruselPosicionActual += direccion;

    if (carruselPosicionActual < 0) carruselPosicionActual = maxIndice;
    if (carruselPosicionActual > maxIndice) carruselPosicionActual = 0;

    actualizarPosicionCarrusel();
}

function irASlide(indice) {
    carruselPosicionActual = indice;
    actualizarPosicionCarrusel();
}

function actualizarPosicionCarrusel() {
    const contenedor = document.getElementById('contenedor-disponibilidad');
    if (!contenedor) return;

    // Calcular el porcentaje exacto de desplazamiento por bloque de página
    const porVista = obtenerTarjetasPorVista();
    // Cada bloque de tarjetas tiene un ancho igual al ancho de la ventana del carrusel
    const porcentajeDesplazamiento = carruselPosicionActual * (100 + (16 / contenedor.clientWidth * 100));
    contenedor.style.transform = `translateX(-${porcentajeDesplazamiento}%)`;

    const puntos = document.querySelectorAll('.punto-indicador');
    puntos.forEach((p, idx) => {
        if (idx === carruselPosicionActual) p.classList.add('activo');
        else p.classList.remove('activo');
    });
}

function aplicarFiltrosTutorias() {
    const buscarTextoInput   = document.getElementById('buscar-texto');
    const filtrarMateriaSelect = document.getElementById('filtrar-materia');

    const texto   = buscarTextoInput ? buscarTextoInput.value.toLowerCase().trim() : '';
    const materia = filtrarMateriaSelect ? filtrarMateriaSelect.value.toLowerCase().trim() : '';

    const filtrados = listaDisponibilidadGlobal.filter(item => {
        const itemMateria = (item.nombreMateria || '').toLowerCase();
        const itemTutor   = (item.nombreTutor || '').toLowerCase();

        const coincideTexto   = !texto || itemMateria.includes(texto) || itemTutor.includes(texto);
        const coincideMateria = !materia || itemMateria === materia;

        return coincideTexto && coincideMateria;
    });

    carruselPosicionActual = 0;
    renderizarTarjetasDisponibilidad(filtrados);
}

// ---------------------------------------------------------------------------
// 3. SOLICITUDES Y DASHBOARD
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
    } catch (e) {}
}

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
        alert('Error de conexión al procesar la reserva.');
    }
}

async function cargarSolicitudesUsuario() {
    if (!usuarioAutenticado) return;

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

        if (idTutor) {
            const tablaTutor = document.getElementById('tabla-solicitudes-tutor');
            if (tablaTutor) {
                tablaTutor.innerHTML = '';
                if (resultado.exito && Array.isArray(resultado.datos) && resultado.datos.length > 0) {
                    resultado.datos.forEach(s => {
                        const tr = document.createElement('tr');
                        let celdaAcciones = s.estadoSolicitud === 'Pendiente' ? `
                            <button type="button" class="btn btn-exito-suave" onclick="cambiarEstadoSolicitud(${s.idSolicitud}, 'Aprobada')">Aceptar</button>
                            <button type="button" class="btn btn-peligro-suave" onclick="cambiarEstadoSolicitud(${s.idSolicitud}, 'Rechazada')">Rechazar</button>
                        ` : `<span class="estado-badge estado-${s.estadoSolicitud.toLowerCase()}">${s.estadoSolicitud}</span>`;

                        tr.innerHTML = `
                            <td class="texto-resaltado">${s.nombreEstudiante || s.noCarnetEstudiante}</td>
                            <td>${s.nombreMateria}</td>
                            <td>${s.fecha} - ${s.horaInicio}</td>
                            <td class="texto-centro contenedor-acciones">${celdaAcciones}</td>
                        `;
                        tablaTutor.appendChild(tr);
                    });
                } else {
                    tablaTutor.innerHTML = `<tr><td colspan="4" class="texto-centro">No hay solicitudes recibidas.</td></tr>`;
                }
            }
        } else {
            const tablaEstudiante = document.getElementById('tabla-solicitudes-estudiante');
            if (tablaEstudiante) {
                tablaEstudiante.innerHTML = '';
                if (resultado.exito && Array.isArray(resultado.datos) && resultado.datos.length > 0) {
                    resultado.datos.forEach(s => {
                        const tr = document.createElement('tr');
                        let celdaBoton = s.estadoSolicitud === 'Pendiente' ? 
                            `<button type="button" class="btn btn-peligro-suave" onclick="cancelarSolicitud(${s.idSolicitud})">Cancelar</button>` : 
                            `<span class="subtitulo-seccion">-</span>`;

                        tr.innerHTML = `
                            <td class="texto-resaltado">${s.nombreMateria}</td>
                            <td>${s.nombreTutor || 'Tutor UMG'}</td>
                            <td>${s.fecha} - ${s.horaInicio}</td>
                            <td><span class="estado-badge estado-${s.estadoSolicitud.toLowerCase()}">${s.estadoSolicitud}</span></td>
                            <td class="texto-derecha">${celdaBoton}</td>
                        `;
                        tablaEstudiante.appendChild(tr);
                    });
                } else {
                    tablaEstudiante.innerHTML = `<tr><td colspan="5" class="texto-centro">No tienes solicitudes de tutoría reservadas actualmente.</td></tr>`;
                }
            }
        }
    } catch (error) {}
}

async function cambiarEstadoSolicitud(idSolicitud, nuevoEstado) {
    if (!confirm(`¿Deseas cambiar el estado a '${nuevoEstado}'?`)) return;

    try {
        const respuesta = await fetch('api/solicitudes.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idSolicitud: idSolicitud, estadoSolicitud: nuevoEstado })
        });
        const resultado = await respuesta.json();

        if (resultado.exito) {
            alert('✅ ' + resultado.mensaje);
            cargarSolicitudesUsuario();
            cargarMetricsDashboard();
        } else {
            alert('❌ ' + resultado.mensaje);
        }
    } catch (error) {}
}

async function cancelarSolicitud(idSolicitud) {
    if (!confirm('¿Deseas cancelar esta solicitud?')) return;

    try {
        const respuesta = await fetch('api/solicitudes.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idSolicitud: idSolicitud, estadoSolicitud: 'Cancelada' })
        });
        const resultado = await respuesta.json();

        if (resultado.exito) {
            alert('✅ Solicitud cancelada correctamente.');
            cargarSolicitudesUsuario();
            cargarDisponibilidad();
            cargarMetricsDashboard();
        }
    } catch (error) {}
}

async function guardarDisponibilidad(event) {
    if (event) event.preventDefault();

    if (!usuarioAutenticado || !usuarioAutenticado.idTutor) {
        alert('Debes iniciar sesión como tutor.');
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
            cargarDisponibilidad();
        } else {
            alert('❌ ' + resultado.mensaje);
        }
    } catch (error) {}
}

function configurarEventos() {
    const buscarTextoInput     = document.getElementById('buscar-texto');
    const filtrarMateriaSelect = document.getElementById('filtrar-materia');

    if (buscarTextoInput) buscarTextoInput.addEventListener('input', aplicarFiltrosTutorias);
    if (filtrarMateriaSelect) filtrarMateriaSelect.addEventListener('change', aplicarFiltrosTutorias);

    window.addEventListener('resize', () => actualizarPosicionCarrusel());
}

window.procesarLogin = procesarLogin;
window.cerrarSesion = cerrarSesion;
window.solicitarTutoria = solicitarTutoria;
window.cambiarEstadoSolicitud = cambiarEstadoSolicitud;
window.cancelarSolicitud = cancelarSolicitud;
window.guardarDisponibilidad = guardarDisponibilidad;
window.moverCarrusel = moverCarrusel;
window.irASlide = irASlide;
