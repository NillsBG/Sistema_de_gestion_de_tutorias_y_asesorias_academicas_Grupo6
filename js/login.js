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
    for (const id of ['reg-carnet','reg-carrera']) document.getElementById(id).required = estudiante;
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
        data.datos.forEach(c => select.add(new Option(`${c.codigoCarrera} - ${c.nombreCarrera}`, c.idCarrera)));
        select.dataset.cargado = '1';
    } catch (e) {
        select.replaceChildren(new Option('No se pudieron cargar las carreras', ''));
    }
}
async function enviarSolicitudCuenta(event) {
    event.preventDefault();
    const obtener = id => document.getElementById(id).value;
    const datos = {
        rolSolicitado: obtener('reg-rol'), nombreCompleto: obtener('reg-nombre'),
        correo: obtener('reg-correo'), username: obtener('reg-usuario'),
        noCarnetCompleto: obtener('reg-carnet'), idCarrera: obtener('reg-carrera'),
        tituloProfesional: obtener('reg-titulo'), especialidad: obtener('reg-especialidad'),
        contrasena: obtener('reg-clave'), confirmarContrasena: obtener('reg-confirmar')
    };
    if (datos.contrasena !== datos.confirmarContrasena) { alert('Las contraseñas no coinciden.'); return; }
    try {
        const resp = await fetchSeguro('api/registro.php', {
            method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(datos)
        });
        const data = await resp.json();
        alert(data.mensaje || 'No fue posible enviar la solicitud.');
        if (data.exito) {
            document.getElementById('form-registro').reset();
            cambiarCamposRegistro();
            mostrarRegistro(false);
        }
    } catch (e) { alert('No fue posible contactar al servidor.'); }
}
