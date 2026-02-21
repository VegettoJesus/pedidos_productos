<style>
    .profile-card {
        background: linear-gradient(135deg, #f77819 0%, #f35b08 100%);
        border: none !important;
        border-radius: 20px !important;
        overflow: hidden;
    }
    
    .profile-card .card-header {
        background: rgba(0, 0, 0, 0.2) !important;
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        padding: 1.5rem !important;
    }
    
    .profile-card .card-header h5 {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
        letter-spacing: 0.5px;
        color: white;
    }
    
    .profile-card .card-body {
        background: white;
        padding: 2rem !important;
    }
    
    .profile-card .card-footer {
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 1.5rem !important;
    }
    
    .avatar-upload {
        position: relative;
        max-width: 200px;
        margin: 0 auto;
    }
    
    .avatar-upload .avatar-preview {
        width: 180px;
        height: 180px;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        margin: 0 auto;
    }
    
    .avatar-upload .avatar-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .avatar-upload .avatar-preview:hover img {
        transform: scale(1.05);
    }
    
    .avatar-upload .upload-btn {
        position: absolute;
        bottom: 10px;
        right: 10px;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f77819, #f35b08);
        border: 3px solid #fff;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(247, 120, 25, 0.4);
    }
    
    .avatar-upload .upload-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 8px 25px rgba(247, 120, 25, 0.6);
    }
    
    .avatar-upload .upload-btn i {
        font-size: 1.2rem;
    }
    
    .avatar-upload .upload-btn input {
        display: none;
    }
    
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin: 1.5rem 0 1rem 0;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f35b08;
    }
    
    .form-section-title i {
        color: #f77819;
        margin-right: 0.5rem;
    }
    
    .form-label {
        font-weight: 500;
        color: #495057;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.6rem 1rem;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #f77819;
        box-shadow: 0 0 0 0.2rem rgba(247, 120, 25, 0.25);
    }
    
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc3545;
        background-image: none;
    }
    
    .input-group .btn-outline-secondary {
        border: 2px solid #e9ecef;
        border-left: none;
        border-radius: 0 10px 10px 0;
    }
    
    .input-group .btn-outline-secondary:hover {
        background-color: #e9ecef;
        color: #495057;
    }
    
    .btn-modern {
        padding: 0.6rem 1.5rem;
        border-radius: 10px;
        font-weight: 500;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        border: none;
    }
    
    .btn-modern.btn-primary {
        background: linear-gradient(135deg, #47423f, #1b1a1a);
        color: white;
    }
    
    .btn-modern.btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(247, 120, 25, 0.4);
    }
    
    .btn-modern.btn-secondary {
        background: #e9ecef;
        color: #495057;
    }
    
    .btn-modern.btn-secondary:hover {
        background: #dee2e6;
        transform: translateY(-2px);
    }
    
    .password-strength {
        height: 5px;
        border-radius: 3px;
        margin-top: 0.5rem;
        transition: all 0.3s ease;
    }
    
    .password-strength.weak {
        width: 33%;
        background: #dc3545;
    }
    
    .password-strength.medium {
        width: 66%;
        background: #ffc107;
    }
    
    .password-strength.strong {
        width: 100%;
        background: #28a745;
    }
    
    /* Estilo para cuando los selects están deshabilitados */
    select:disabled {
        background-color: #e9ecef !important;
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Animación de carga */
    .loading-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid rgba(247, 120, 25, 0.3);
        border-radius: 50%;
        border-top-color: #f77819;
        animation: spin 1s ease-in-out infinite;
        margin-left: 0.5rem;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
        .profile-card .card-body {
            padding: 1.5rem !important;
        }
        
        .avatar-upload {
            margin-bottom: 2rem;
        }
        
        .form-section-title {
            margin-top: 1rem;
        }
    }
</style>
<div class="container-fluid px-2 pb-4 px-md-4">
    <div class="card profile-card">
        <div class="card-header">
            <h5 class="text-white mb-0">
                <i class="bi bi-gear-fill me-2"></i> Configuración de Perfil
            </h5>
        </div>
        
        <div class="card-body">
            <form id="formPerfil" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <!-- Columna izquierda - Avatar -->
                    <div class="col-lg-4 col-md-12">
                        <div class="avatar-upload">
                            <div class="avatar-preview">
                                <img id="previewImagen" 
                                     src="{{ $usuario->datos && $usuario->datos->imagen ? asset('perfil_usuario/'.$usuario->datos->imagen) : asset('img/user.png') }}" 
                                     alt="Avatar">
                            </div>
                            <label for="imagen" class="upload-btn">
                                <i class="bi bi-camera-fill"></i>
                                <input type="file" id="imagen" name="imagen" accept="image/*">
                            </label>
                            <small class="d-block text-center text-muted mt-3">
                                <i class="bi bi-info-circle"></i> JPG o PNG, máximo 2MB
                            </small>
                        </div>
                    </div>

                    <!-- Columna derecha - Datos personales -->
                    <div class="col-lg-8 col-md-12">
                        <!-- Datos básicos -->
                        <div class="form-section-title">
                            <i class="bi bi-person-badge"></i> Información Personal
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombres" class="form-label">Nombres</label>
                                <input type="text" class="form-control" id="nombres" name="nombres" 
                                       value="{{ $usuario->nombres }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="apellidos" class="form-label">Apellidos</label>
                                <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                       value="{{ $usuario->apellidos }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="{{ $usuario->email }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">
                                    Nueva Contraseña <small class="text-muted">(opcional)</small>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="••••••••">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="passwordStrength"></div>
                            </div>
                        </div>

                        <!-- Documento y contacto -->
                        <div class="form-section-title">
                            <i class="bi bi-file-earmark-text"></i> Documento y Contacto
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="tipoDoc" class="form-label">Tipo Documento</label>
                                <select class="form-select" id="tipoDoc" name="tipoDoc" required>
                                    <option value="DNI" {{ $usuario->datos && $usuario->datos->tipoDoc == 'DNI' ? 'selected' : '' }}>DNI</option>
                                    <option value="CARNET DE EXTRANJERIA" {{ $usuario->datos && $usuario->datos->tipoDoc == 'CARNET DE EXTRANJERIA' ? 'selected' : '' }}>CARNET DE EXTRANJERIA</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="numeroDoc" class="form-label">Número Documento</label>
                                <input type="text" class="form-control" id="numeroDoc" name="numeroDoc" 
                                       value="{{ $usuario->datos->numeroDoc ?? '' }}" required>
                            </div>
                            <div class="col-md-4">
                                <label for="celular" class="form-label">Celular</label>
                                <input type="text" class="form-control" id="celular" name="celular" 
                                       value="{{ $usuario->datos->celular ?? '' }}" placeholder="999 999 999">
                            </div>
                            <div class="col-md-6">
                                <label for="fecha_nacimiento" class="form-label">Fecha Nacimiento</label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                                       value="{{ $usuario->datos->fecha_nacimiento ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label for="nacionalidad" class="form-label">Nacionalidad</label>
                                <select class="form-select" id="nacionalidad" name="nacionalidad">
                                    <option value="">Seleccione</option>
                                    <option value="Perú" {{ ($usuario->datos->nacionalidad ?? '') == 'Perú' ? 'selected' : '' }}>Perú</option>
                                    <option value="Argentina" {{ ($usuario->datos->nacionalidad ?? '') == 'Argentina' ? 'selected' : '' }}>Argentina</option>
                                    <option value="Bolivia" {{ ($usuario->datos->nacionalidad ?? '') == 'Bolivia' ? 'selected' : '' }}>Bolivia</option>
                                    <option value="Chile" {{ ($usuario->datos->nacionalidad ?? '') == 'Chile' ? 'selected' : '' }}>Chile</option>
                                    <option value="Colombia" {{ ($usuario->datos->nacionalidad ?? '') == 'Colombia' ? 'selected' : '' }}>Colombia</option>
                                    <option value="Ecuador" {{ ($usuario->datos->nacionalidad ?? '') == 'Ecuador' ? 'selected' : '' }}>Ecuador</option>
                                    <option value="Venezuela" {{ ($usuario->datos->nacionalidad ?? '') == 'Venezuela' ? 'selected' : '' }}>Venezuela</option>
                                </select>
                            </div>
                        </div>

                        <!-- Dirección -->
                        <div class="form-section-title">
                            <i class="bi bi-geo-alt"></i> Dirección
                        </div>

                        @php
                            $direccionCompleta = $usuario->datos->direccion ?? '';
                            $calle = '';
                            $numero = '';
                            $otros = '';
                            $codigoPostal = '';
                            
                            if ($direccionCompleta) {
                                $partes = explode(',', $direccionCompleta);
                                $ultimaParte = trim(end($partes));
                                if (preg_match('/(\d{5})$/', $ultimaParte, $matches)) {
                                    $codigoPostal = $matches[1];
                                    $partes[count($partes) - 1] = trim(str_replace($codigoPostal, '', $ultimaParte));
                                }
                                
                                if (isset($partes[0])) {
                                    $primeraParte = trim($partes[0]);
                                    $partesCalle = explode(' ', $primeraParte);
                                    $numero = array_pop($partesCalle);
                                    $calle = implode(' ', $partesCalle);
                                }
                                
                                if (isset($partes[1])) {
                                    $otros = trim($partes[1]);
                                }
                            }
                        @endphp

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="departamento_id" class="form-label">Departamento</label>
                                <select class="form-select" id="departamento_id" name="departamento_id" required>
                                    <option value="">Seleccione</option>
                                    @foreach($departamentos as $dep)
                                        <option value="{{ $dep->id }}" 
                                            {{ ($usuario->datos->departamento ?? '') == $dep->id ? 'selected' : '' }}>
                                            {{ $dep->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="provincia_id" class="form-label">Provincia</label>
                                <select class="form-select" id="provincia_id" name="provincia_id" required>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="distrito_id" class="form-label">Distrito</label>
                                <select class="form-select" id="distrito_id" name="distrito_id" required>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>

                            <div class="col-md-5">
                                <label for="direccion" class="form-label">Calle/Av.</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" 
                                       value="{{ $calle }}" placeholder="Av. Ejemplo" required>
                            </div>
                            <div class="col-md-2">
                                <label for="num_calle" class="form-label">N°</label>
                                <input type="text" class="form-control" id="num_calle" name="num_calle" 
                                       value="{{ $numero }}" placeholder="123" required>
                            </div>
                            <div class="col-md-3">
                                <label for="dir_otros" class="form-label">Piso/Dpto</label>
                                <input type="text" class="form-control" id="dir_otros" name="dir_otros" 
                                       value="{{ $otros }}" placeholder="Piso 1, Dpto 101">
                            </div>
                            <div class="col-md-2">
                                <label for="cod_postal" class="form-label">Código Postal</label>
                                <input type="text" class="form-control" id="cod_postal" name="cod_postal" 
                                       value="{{ $codigoPostal }}" placeholder="15074">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="card-footer">
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" form="formPerfil" class="btn-modern btn-primary">
                    <i class="bi bi-check2-circle me-2"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.datosUsuario = {
        distrito: {{ $usuario->datos && $usuario->datos->distrito ? $usuario->datos->distrito : 'null' }},
        provincia: {{ $usuario->datos && $usuario->datos->provincia ? $usuario->datos->provincia : 'null' }},
        departamento: {{ $usuario->datos && $usuario->datos->departamento ? $usuario->datos->departamento : 'null' }}
    };
</script>