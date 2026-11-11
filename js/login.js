// js/login.js - Lógica Exclusiva para el Inicio de Sesión
async function procesarLogin(event) {
    if (event) event.preventDefault();

    const usuarioInput = document.getElementById('login-usuario');
    const claveInput   = document.getElementById('login-clave');

    if (!usuarioInput || !claveInput) return;

    const usuarioVal = usuarioInput.value.trim();
    const claveVal   = claveInput.value;

    if (!usuarioVal || !claveVal) {
        alert('Por favor ingresa tu usuario y contraseña.');
        return;
    }

    try {
        const respuesta = await fetchSeguro('php/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario: usuarioVal, clave: claveVal })
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            window.location.reload(); // Recarga y index.php redirecciona al rol correcto
        } else {
            alert('❌ ' + (resultado.mensaje || 'Credenciales incorrectas'));
        }
    } catch (error) {
        console.error('Error en procesarLogin:', error);
        alert('Error de conexión con el servidor.');
    }
}



// Registro con aprobación administrativa. No crea sesiones ni usuarios activos.
function mostrarRegistro(mostrar) {
    document.querySelector('.tarjeta-login').style.display = mostrar ? 'none' : '';
    document.getElementById('panel-registro').style.display = mostrar ? '' : 'none';
    if (mostrar) cargarCarrerasRegistro();
}
function cambiarCamposRegistro() {
    const estudiante = document.getElementById('reg-rol').value === 'Estudiante';
    document.getElementById('campos-estudiante').style.display = estudiante ? '' : 'none';
    document.getElementById('campos-tutor').style.display = estudiante ? 'none' : '';
    document.getElementById('reg-carrera').required = estudiante;
    actualizarVistaCarnetRegistro();
    for (const id of ['reg-titulo','reg-especialidad']) document.getElementById(id).required = !estudiante;
}
async function cargarCarrerasRegistro() {
    const select = document.getElementById('reg-carrera');
    if (select.dataset.cargado === '1') return;
    try {
        const resp = await fetchSeguro('api/registro.php');
        const data = await resp.json();
        if (!data.exito) throw new Error(data.mensaje);
        select.replaceChildren(new Option('Selecciona una carrera', ''));
        data.datos.forEach(c => {
            const opcion = new Option(`${c.codigoCarrera} - ${c.nombreCarrera}`, c.idCarrera);
            opcion.dataset.codigo = c.codigoCarrera;
            select.add(opcion);
        });
        select.dataset.cargado = '1';
        actualizarVistaCarnetRegistro();
    } catch (e) {
        select.replaceChildren(new Option('No se pudieron cargar las carreras', ''));
    }
}
async function enviarSolicitudCuenta(event) {
    event.preventDefault();
    const obtener = id => document.getElementById(id).value;
    mayusculasRegistro();
    const datos = {
        rolSolicitado: obtener('reg-rol'), nombreCompleto: obtener('reg-nombre'),
        correo: obtener('reg-correo'), username: obtener('reg-usuario'),
        idCarrera: obtener('reg-carrera'),
        tituloProfesional: obtener('reg-titulo'), especialidad: obtener('reg-especialidad'),
        contrasena: obtener('reg-clave'), confirmarContrasena: obtener('reg-confirmar')
    };
    if ([...datos.contrasena].length < 8) { alert('La contraseña debe contener al menos 8 caracteres.'); return; }
    if (datos.contrasena !== datos.confirmarContrasena) { alert('Las contraseñas no coinciden.'); return; }
    try {
        const resp = await fetchSeguro('api/registro.php', {
            method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(datos)
        });
        const data = await resp.json();
        alert(data.mensaje || 'No fue posible enviar la solicitud.');
        if (data.exito) {
            document.getElementById('form-registro').reset();
            actualizarVistaCarnetRegistro();
            cambiarCamposRegistro();
            mostrarRegistro(false);
        }
    } catch (e) { alert('No fue posible contactar al servidor.'); }
}


// Los caracteres se muestran y guardan en mayúsculas; la contraseña no se altera.
function mayusculasRegistro() {
    const campo = document.getElementById('reg-usuario');
    if (campo) campo.value = campo.value.toLocaleUpperCase('es-GT');
}

function actualizarVistaCarnetRegistro() {
    const campo = document.getElementById('reg-carnet');
    const carrera = document.getElementById('reg-carrera');
    if (!campo || !carrera) return;
    const seleccion = carrera.options[carrera.selectedIndex];
    const codigo = seleccion && seleccion.dataset.codigo;
    const anio = String(new Date().getFullYear()).slice(-2);
    campo.value = codigo ? `${codigo}-${anio}-XXXX (al aprobar)` : 'Selecciona una carrera';
}

document.addEventListener('DOMContentLoaded', () => {
    const usuario = document.getElementById('reg-usuario');
    if (usuario) usuario.addEventListener('input', mayusculasRegistro);
    const carrera = document.getElementById('reg-carrera');
    if (carrera) carrera.addEventListener('change', actualizarVistaCarnetRegistro);
});
