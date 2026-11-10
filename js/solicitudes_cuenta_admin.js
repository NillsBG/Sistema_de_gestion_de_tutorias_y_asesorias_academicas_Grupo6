// Solicitudes de cuentas: únicamente el administrador ve este apartado.
document.addEventListener('DOMContentLoaded', cargarSolicitudesCuenta);
async function cargarSolicitudesCuenta() {
    const tbody = document.getElementById('tabla-solicitudes-cuenta');
    if (!tbody) return;
    try {
        const res = await fetchSeguro('api/solicitudes_cuenta.php', {cache:'no-store'});
        const data = await res.json();
        if (!data.exito) throw new Error(data.mensaje || 'Error de consulta');
        tbody.replaceChildren();
        if (!data.datos.length) {
            const fila = tbody.insertRow(); const celda = fila.insertCell();
            celda.colSpan = 7; celda.textContent = 'No hay solicitudes pendientes.';
            return;
        }
        data.datos.forEach(s => {
            const fila = tbody.insertRow();
            const info = s.rolSolicitado === 'Estudiante'
                ? `${s.noCarnetCompleto || ''} · ${s.nombreCarrera || ''}`
                : `${s.tituloProfesional || ''} · ${s.especialidad || ''}`;
            [s.username,s.nombreCompleto,s.rolSolicitado,info,s.correo,s.fechaSolicitud].forEach(v => {
                fila.insertCell().textContent = v ?? '';
            });
            const celda = fila.insertCell();
            const aceptar = document.createElement('button');
            aceptar.type = 'button'; aceptar.className = 'btn btn-exito-suave';
            aceptar.textContent = 'Aceptar'; aceptar.onclick = () => revisarSolicitudCuenta(s.idSolicitudCuenta,'aprobar');
            const rechazar = document.createElement('button');
            rechazar.type = 'button'; rechazar.className = 'btn btn-peligro-suave';
            rechazar.textContent = 'Rechazar'; rechazar.style.marginLeft = '0.4rem';
            rechazar.onclick = () => revisarSolicitudCuenta(s.idSolicitudCuenta,'rechazar');
            celda.append(aceptar,rechazar);
        });
    } catch (e) { tbody.replaceChildren(); const f=tbody.insertRow(); const c=f.insertCell(); c.colSpan=7; c.textContent='No se pudieron cargar las solicitudes.'; }
}
async function revisarSolicitudCuenta(id,accion) {
    const mensaje = accion === 'aprobar' ? '¿Aprobar esta cuenta y habilitar su acceso?' : '¿Rechazar esta solicitud?';
    if (!confirm(mensaje)) return;
    try {
        const res = await fetchSeguro('api/solicitudes_cuenta.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({idSolicitudCuenta:Number(id),accion})
        });
        const data = await res.json();
        alert(data.mensaje || 'Operación no completada.');
        if (data.exito) {
            await cargarSolicitudesCuenta();
            if (typeof cargarTutoresAdmin === 'function') cargarTutoresAdmin();
        }
    } catch (e) { alert('Error de conexión con el servidor.'); }
}
