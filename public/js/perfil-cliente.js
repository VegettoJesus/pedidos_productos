document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formPerfilCliente');
    if (!form) return;

    const $ = (sel) => document.querySelector(sel);

    const inputImagen   = $('#inputImagen');
    const avatarPreview = $('#avatarPreview');
    const deptoSelect   = $('#perfilDepartamento');
    const provSelect    = $('#perfilProvincia');
    const distSelect    = $('#perfilDistrito');

    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    inputImagen?.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            Swal.fire('Archivo no válido', 'Selecciona una imagen.', 'warning');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire('Imagen muy grande', 'Máximo 2 MB.', 'warning');
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => { avatarPreview.src = e.target.result; };
        reader.readAsDataURL(file);
        const fd = new FormData();
        fd.append('imagen', file);
        const labelBtn = this.closest('.avatar-wrapper')?.querySelector('.avatar-edit-btn');
        labelBtn?.classList.add('uploading');

        fetch('/perfil-cliente/actualizar-imagen', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: fd,
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.imagen_url) {
                avatarPreview.src = data.imagen_url + '?t=' + Date.now();
                Swal.fire({
                    icon: 'success',
                    title: 'Foto actualizada',
                    timer: 1500,
                    showConfirmButton: false,
                });
            } else {
                Swal.fire('Error', data.message || 'No se pudo subir la imagen.', 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'No se pudo subir la imagen.', 'error');
        })
        .finally(() => {
            labelBtn?.classList.remove('uploading');
            this.value = ''; 
        });
    });

    const currentProv = deptoSelect?.dataset.currentProvincia || '';
    const currentDist = deptoSelect?.dataset.currentDistrito || '';

    function llenarSelect(select, items, selected) {
        select.innerHTML = '<option value="">Seleccione</option>';
        items.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.nombre;
            if (selected !== null && selected !== '' && String(selected) === String(item.id)) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });
    }

    function cargarProvincias(deptoId, selected = null) {
        if (!deptoId) {
            llenarSelect(provSelect, [], null);
            llenarSelect(distSelect, [], null);
            provSelect.disabled = true;
            distSelect.disabled = true;
            return Promise.resolve();
        }
        provSelect.disabled = true;
        provSelect.innerHTML = '<option value="">Cargando...</option>';
        distSelect.innerHTML = '<option value="">Seleccione</option>';

        return fetch(`/get-provincias/${deptoId}`, {
            headers: { 'Accept': 'application/json' }
        })
            .then(r => r.json())
            .then(data => {
                llenarSelect(provSelect, data, selected);
                provSelect.disabled = false;
            })
            .catch(err => {
                provSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    }

    function cargarDistritos(provId, selected = null) {
        if (!provId) {
            llenarSelect(distSelect, [], null);
            distSelect.disabled = true;
            return Promise.resolve();
        }
        distSelect.disabled = true;
        distSelect.innerHTML = '<option value="">Cargando...</option>';

        return fetch(`/get-distritos/${provId}`, {
            headers: { 'Accept': 'application/json' }
        })
            .then(r => r.json())
            .then(data => {
                llenarSelect(distSelect, data, selected);
                distSelect.disabled = false;
            })
            .catch(err => {
                distSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    }

    const currentDepto = deptoSelect?.value;
    if (currentDepto) {
        cargarProvincias(currentDepto, currentProv).then(() => {
            if (currentProv) {
                return cargarDistritos(currentProv, currentDist);
            }
        });
    } else {
        provSelect.disabled = true;
        distSelect.disabled = true;
    }

    deptoSelect?.addEventListener('change', function () {
        cargarProvincias(this.value);
    });
    provSelect?.addEventListener('change', function () {
        cargarDistritos(this.value);
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const pass = $('#perfilPassword').value;
        const passConfirm = $('#perfilPasswordConfirm').value;
        if (pass && pass.length < 6) {
            Swal.fire('Contraseña corta', 'Mínimo 6 caracteres.', 'warning');
            return;
        }
        if (pass && pass !== passConfirm) {
            Swal.fire('No coinciden', 'Las contraseñas no coinciden.', 'warning');
            return;
        }

        const btn = $('#btnGuardarPerfil');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Guardando...';

        try {
            const fd = new FormData(form);
            const resp = await fetch('/perfil-cliente/actualizar', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: fd,
            });
            const data = await resp.json();

            if (resp.ok && data.success) {
                if (data.imagen_url) avatarPreview.src = data.imagen_url + '?t=' + Date.now();
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: data.message || 'Perfil actualizado.',
                    timer: 1800,
                    showConfirmButton: false,
                });
                $('#perfilPassword').value = '';
                $('#perfilPasswordConfirm').value = '';
            } else {
                const msg = data.message || (data.errors ? Object.values(data.errors)[0][0] : 'Error al guardar.');
                Swal.fire('Error', msg, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'No se pudo guardar. Intenta de nuevo.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Guardar cambios';
        }
    });

    $('#btnCancelarPerfil')?.addEventListener('click', () => {
        Swal.fire({
            title: '¿Descartar cambios?',
            text: 'Se recargará la página.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, descartar',
            cancelButtonText: 'Cancelar',
        }).then(r => { if (r.isConfirmed) location.reload(); });
    });
});