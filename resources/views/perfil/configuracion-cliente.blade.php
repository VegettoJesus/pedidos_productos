{{-- perfil/configuracion-cliente.blade.php --}}
@php
    $u = $usuario;
    $d = $u->datos;
    $imgUrl = ($d && $d->imagen && file_exists(public_path('perfil_usuario/'.$d->imagen)))
        ? asset('perfil_usuario/'.$d->imagen)
        : asset('img/user.png');
@endphp

<div class="perfil-cliente-page">
    <div class="perfil-cliente-header">
        <h1><i class="bi bi-person-circle"></i> Mi perfil</h1>
        <p>Actualiza tus datos personales y de contacto.</p>
    </div>

    <div class="perfil-cliente-grid">
        {{-- ====== COLUMNA IZQUIERDA: foto + resumen ====== --}}
        <aside class="perfil-sidebar">
            <div class="avatar-card">
                <div class="avatar-wrapper">
                    <img src="{{ $imgUrl }}" alt="Foto de perfil" id="avatarPreview">
                    <label for="inputImagen" class="avatar-edit-btn" title="Cambiar foto">
                        <i class="bi bi-camera"></i>
                    </label>
                    <input type="file" id="inputImagen" accept="image/*" hidden>
                </div>
                <h3>{{ $u->nombres }} {{ $u->apellidos }}</h3>
                <p class="text-muted small">{{ $u->email }}</p>
                <span class="badge-role">Cliente</span>
            </div>
        </aside>

        {{-- ====== COLUMNA DERECHA: formulario ====== --}}
        <section class="perfil-form-area">
            <form id="formPerfilCliente" enctype="multipart/form-data">
                @csrf

                {{-- Datos personales --}}
                <div class="form-card">
                    <h3 class="form-card-title">
                        <i class="bi bi-person-vcard"></i> Datos personales
                    </h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombres <span class="text-danger">*</span></label>
                            <input type="text" name="nombres" class="form-control" required
                                   value="{{ $u->nombres }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" name="apellidos" class="form-control" required
                                   value="{{ $u->apellidos }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required
                                   value="{{ $u->email }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Celular</label>
                            <input type="text" name="celular" class="form-control"
                                   value="{{ $d->celular ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de documento</label>
                            <select name="tipoDoc" class="form-select">
                                <option value="">Seleccione</option>
                                <option value="DNI" {{ ($d->tipoDoc ?? '') === 'DNI' ? 'selected' : '' }}>DNI</option>
                                <option value="CARNET DE EXTRANJERIA" {{ ($d->tipoDoc ?? '') === 'CARNET DE EXTRANJERIA' ? 'selected' : '' }}>Carnet de Extranjería</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">N° documento</label>
                            <input type="text" name="numeroDoc" class="form-control"
                                   value="{{ $d->numeroDoc ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control"
                                   value="{{ $d->fecha_nacimiento ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nacionalidad</label>
                            <select name="nacionalidad" class="form-select">
                                <option value="">Seleccione</option>
                                @php $nac = $d->nacionalidad ?? ''; @endphp
                                <option value="Perú" {{ $nac === 'Perú' ? 'selected' : '' }}>Perú</option>
                                <option value="Argentina" {{ $nac === 'Argentina' ? 'selected' : '' }}>Argentina</option>
                                <option value="Bolivia" {{ $nac === 'Bolivia' ? 'selected' : '' }}>Bolivia</option>
                                <option value="Brasil" {{ $nac === 'Brasil' ? 'selected' : '' }}>Brasil</option>
                                <option value="Chile" {{ $nac === 'Chile' ? 'selected' : '' }}>Chile</option>
                                <option value="Colombia" {{ $nac === 'Colombia' ? 'selected' : '' }}>Colombia</option>
                                <option value="Ecuador" {{ $nac === 'Ecuador' ? 'selected' : '' }}>Ecuador</option>
                                <option value="España" {{ $nac === 'España' ? 'selected' : '' }}>España</option>
                                <option value="Estados Unidos" {{ $nac === 'Estados Unidos' ? 'selected' : '' }}>Estados Unidos</option>
                                <option value="México" {{ $nac === 'México' ? 'selected' : '' }}>México</option>
                                <option value="Venezuela" {{ $nac === 'Venezuela' ? 'selected' : '' }}>Venezuela</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Dirección --}}
                <div class="form-card">
                    <h3 class="form-card-title">
                        <i class="bi bi-geo-alt"></i> Dirección
                    </h3>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Departamento</label>
                            <select name="departamento_id" class="form-select" id="perfilDepartamento"
                                    data-current-provincia="{{ $d->provincia ?? '' }}"
                                    data-current-distrito="{{ $d->distrito ?? '' }}">
                                <option value="">Seleccione</option>
                                @foreach($departamentos as $dep)
                                    <option value="{{ $dep->id }}"
                                        {{ ($d->departamento ?? '') == $dep->id ? 'selected' : '' }}>
                                        {{ $dep->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Provincia</label>
                            <select name="provincia_id" class="form-select" id="perfilProvincia">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Distrito</label>
                            <select name="distrito_id" class="form-select" id="perfilDistrito">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Calle / Avenida</label>
                            <input type="text" name="calle" class="form-control"
                                   value="{{ $d->calle ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Número</label>
                            <input type="text" name="numero" class="form-control"
                                   value="{{ $d->numero ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dpto / Piso / Otros</label>
                            <input type="text" name="dir_otros" class="form-control"
                                   value="{{ $d->dir_otros ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Código postal</label>
                            <input type="text" name="cod_postal" class="form-control"
                                   value="{{ $d->cod_postal ?? '' }}">
                        </div>
                    </div>
                </div>

                {{-- Seguridad --}}
                <div class="form-card">
                    <h3 class="form-card-title">
                        <i class="bi bi-shield-lock"></i> Cambiar contraseña
                        <small class="text-muted">(opcional)</small>
                    </h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nueva contraseña</label>
                            <input type="password" name="password" id="perfilPassword"
                                   class="form-control" placeholder="Mínimo 6 caracteres" minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmar contraseña</label>
                            <input type="password" name="password_confirmation"
                                   id="perfilPasswordConfirm" class="form-control"
                                   placeholder="Repite la contraseña" minlength="6">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline-secondary" id="btnCancelarPerfil">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarPerfil">
                        <i class="bi bi-check-circle"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>