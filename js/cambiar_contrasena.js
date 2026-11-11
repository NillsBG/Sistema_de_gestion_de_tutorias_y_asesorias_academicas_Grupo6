// Formulario de cambio de contraseña, compatible con los tres perfiles.
function abrirCambioContrasena() {
    if (document.getElementById('modal-cambiar-contrasena')) return;
    const capa = document.createElement('div');
    capa.id = 'modal-cambiar-contrasena';
    capa.style.cssText = 'position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;padding:16px;';
    capa.innerHTML = `
        <div class="tarjeta" role="dialog" aria-modal="true" aria-labelledby="titulo-cambio-clave" style="width:100%;max-width:450px;max-height:95vh;overflow:auto;">
            <h3 id="titulo-cambio-clave" class="subtitulo-bloque">Cambiar contraseña</h3>
            <p class="subtitulo-seccion">Al guardar deberás iniciar sesión nuevamente.</p>
            <form id="form-cambiar-contrasena" autocomplete="off">
                <div class="grupo-campo"><label class="etiqueta-campo" for="clave-actual">Contraseña actual</label>
                    <input class="campo-entrada" type="password" id="clave-actual" autocomplete="current-password" required></div>
                <div class="grupo-campo"><label class="etiqueta-campo" for="clave-nueva">Nueva contraseña (mínimo 8 caracteres)</label>
                    <input class="campo-entrada" type="password" id="clave-nueva" autocomplete="new-password" minlength="8" maxlength="72" required></div>
                <div class="grupo-campo"><label class="etiqueta-campo" for="clave-confirmar">Confirmar nueva contraseña</label>
                    <input class="campo-entrada" type="password" id="clave-confirmar" autocomplete="new-password" minlength="8" maxlength="72" required></div>
                <p id="clave-mensaje" role="status" aria-live="polite" style="margin:12px 0;"></p>
                <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                    <button class="btn btn-secundario" type="button" id="clave-cancelar">Cancelar</button>
                    <button class="btn btn-oro" type="submit" id="clave-guardar">Guardar contraseña</button>
                </div>
            </form>
        </div>`;
    document.body.appendChild(capa);
    const form = document.getElementById('form-cambiar-contrasena');
    document.getElementById('clave-cancelar').addEventListener('click', cerrarCambioContrasena);
    document.getElementById('clave-actual').focus();
    capa.addEventListener('keydown', e => {if (e.key === 'Escape') cerrarCambioContrasena();});
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const actual = document.getElementById('clave-actual').value;
        const nueva = document.getElementById('clave-nueva').value;
        const confirmar = document.getElementById('clave-confirmar').value;
        const mensaje = document.getElementById('clave-mensaje');
        if ([...nueva].length < 8) { mensaje.textContent = 'Usa al menos 8 caracteres.'; return; }
        if (nueva !== confirmar) { mensaje.textContent = 'Las contraseñas no coinciden.'; return; }
        if (actual === nueva) { mensaje.textContent = 'Elige una contraseña diferente a la actual.'; return; }
        const boton = document.getElementById('clave-guardar');
        boton.disabled = true;
        mensaje.textContent = 'Guardando...';
        try {
            const res = await fetchSeguro('api/cambiar_contrasena.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({contrasenaActual: actual, contrasenaNueva: nueva, confirmarContrasena: confirmar})
            });
            const data = await res.json();
            mensaje.textContent = data.mensaje || 'No se pudo actualizar.';
            if (res.ok && data.exito) {
                form.reset();
                alert('Contraseña cambiada. Vuelve a iniciar sesión.');
                window.location.replace('index.php');
                return;
            }
        } catch (err) {
            mensaje.textContent = 'No se pudo conectar con el servidor.';
        } finally {
            boton.disabled = false;
        }
    });
}
function cerrarCambioContrasena() {
    const modal = document.getElementById('modal-cambiar-contrasena');
    if (modal) { const form = modal.querySelector('form'); if (form) form.reset(); modal.remove(); }
}
