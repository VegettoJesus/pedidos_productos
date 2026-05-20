<?php
// app/Traits/AuditableTrait.php
namespace App\Traits;

use App\Models\Auditoria;
use App\Models\Distrito;
use App\Models\Provincia;
use App\Models\Departamento;
use App\Models\Rol;


trait AuditableTrait
{
    /**
     * Construye la descripción de auditoría automáticamente
     * 
     * @param string $accion Acción realizada (Crear, Actualizar, Eliminar)
     * @param string $nombre Nombre del registro (ej: nombre de categoría, producto, etc.)
     * @param int $registroId ID del registro afectado
     * @param array|null $valoresAnteriores Valores antes del cambio (opcional)
     * @param array|null $valoresNuevos Valores después del cambio (opcional)
     * @param string|null $detalleExtra Información adicional (opcional)
     * @return string Descripción formateada
     */
    protected function construirDescripcionAuditoria($accion, $nombre, $registroId, $valoresAnteriores = null, $valoresNuevos = null, $detalleExtra = null)
    {
        $descripcion = "{$accion}: {$nombre} (ID: {$registroId})";
        
        // Si hay cambios para comparar en campos básicos
        if ($accion === 'Actualizar' && !is_null($valoresAnteriores) && !is_null($valoresNuevos)) {
            $cambios = $this->compararCambios($valoresAnteriores, $valoresNuevos);
            if (!empty($cambios)) {
                $descripcion .= " | Cambios en campos: " . implode('; ', $cambios);
            }
        }
        
        if (!empty($detalleExtra)) {
            $descripcion .= " | " . $detalleExtra;
        }
        
        if ($accion === 'Actualizar' && empty($cambios) && empty($detalleExtra)) {
            $descripcion .= " | Sin cambios detectados";
        }
        
        return $descripcion;
    }

    /**
     * Compara dos arrays y devuelve un array con los cambios
     */
    protected function compararCambios($valoresAnteriores, $valoresNuevos, $camposAIgnorar = ['updated_at', 'created_at'])
    {
        $cambios = [];
        
        // Campos que son numéricos (para comparación especial)
        $camposNumericos = [
            'precio_regular', 'precio_rebajado', 'stock', 'peso', 
            'longitud', 'anchura', 'altura', 'precio', 'costo',
            'stock_minimo', 'max_stock', 'precio_normal', 'price_normal', 
            'price_sale', 'precio', 'costo_unitario', 'valor'
        ];
        
        foreach ($valoresNuevos as $campo => $valorNuevo) {
            if (in_array($campo, $camposAIgnorar)) {
                continue;
            }
            
            $valorAnterior = $valoresAnteriores[$campo] ?? null;
            
            // 🔧 Si es un array anidado (como 'datos'), comparar sus campos internos
            if (is_array($valorNuevo) && isset($valorAnterior) && is_array($valorAnterior)) {
                $cambiosInternos = $this->compararArraysAnidados($valorAnterior, $valorNuevo, $campo);
                $cambios = array_merge($cambios, $cambiosInternos);
                continue;
            }
            
            // Si es array pero no hay anterior o no es array
            if (is_array($valorNuevo) || is_array($valorAnterior)) {
                continue;
            }
            
            // Determinar si es campo numérico
            $esNumerico = in_array($campo, $camposNumericos);
            
            // Verificar si los valores son equivalentes
            if ($this->valoresEquivalentes($valorAnterior, $valorNuevo, $esNumerico)) {
                continue;
            }
            
            $anteriorFormateado = $this->formatearValorAuditoria($valorAnterior);
            $nuevoFormateado = $this->formatearValorAuditoria($valorNuevo);
            $cambios[] = "{$campo}: '{$anteriorFormateado}' → '{$nuevoFormateado}'";
        }
        
        return $cambios;
    }

    /**
     * Compara dos arrays anidados y devuelve los cambios como texto plano
     */
    protected function compararArraysAnidados($arrayAnterior, $arrayNuevo, $nombreArray = 'datos')
    {
        $cambios = [];
        
        foreach ($arrayNuevo as $campo => $valorNuevo) {
            // Saltar campos de control
            if (in_array($campo, ['id', 'id_usuario', 'created_at', 'updated_at'])) {
                continue;
            }
            
            $valorAnterior = $arrayAnterior[$campo] ?? null;
            
            // Normalizar valores vacíos
            $valorNuevoNormalizado = ($valorNuevo === '' || $valorNuevo === null) ? null : $valorNuevo;
            $valorAnteriorNormalizado = ($valorAnterior === '' || $valorAnterior === null) ? null : $valorAnterior;
            
            // Comparar valores
            if ($valorNuevoNormalizado != $valorAnteriorNormalizado) {
                $anteriorDisplay = $valorAnteriorNormalizado ?? 'vacío';
                $nuevoDisplay = $valorNuevoNormalizado ?? 'vacío';
                
                // Traducir nombres de campos para mejor legibilidad
                $nombreCampo = $this->traducirCampoUsuarioDato($campo);
                $cambios[] = "{$nombreCampo}: '{$anteriorDisplay}' → '{$nuevoDisplay}'";
            }
        }
        
        return $cambios;
    }

    /**
     * Traduce los nombres de campos de UsuarioDato a español legible
     */
    protected function traducirCampoUsuarioDato($campo)
    {
        $traducciones = [
            'tipoDoc' => 'Tipo documento',
            'numeroDoc' => 'Número documento',
            'calle' => 'Calle',
            'numero' => 'Número',
            'dir_otros' => 'Dirección adicional',
            'cod_postal' => 'Código postal',
            'celular' => 'Celular',
            'fecha_nacimiento' => 'Fecha nacimiento',
            'nacionalidad' => 'Nacionalidad',
            'distrito' => 'Distrito',
            'provincia' => 'Provincia',
            'departamento' => 'Departamento',
            'imagen' => 'Imagen'
        ];
        
        return $traducciones[$campo] ?? ucfirst($campo);
    }

     /**
     * Determina si dos valores son equivalentes (ignorando diferencias de tipo)
     */
    protected function valoresEquivalentes($valor1, $valor2, $esNumerico = false)
    {
        // Si ambos son null
        if (is_null($valor1) && is_null($valor2)) {
            return true;
        }
        
        // Para campos numéricos: null, 0, "0", "0.00" son equivalentes
        if ($esNumerico) {
            // Normalizar valor1 a número (0 si es null o vacío)
            $num1 = $this->normalizarANumero($valor1);
            // Normalizar valor2 a número (0 si es null o vacío)
            $num2 = $this->normalizarANumero($valor2);
            
            // Comparar como números con tolerancia para floats
            if (abs($num1 - $num2) < 0.00001) {
                return true;
            }
        }
        
        // Para strings, comparar después de normalizar
        $str1 = $this->normalizarAString($valor1);
        $str2 = $this->normalizarAString($valor2);
        
        return $str1 === $str2;
    }

    /**
     * Normaliza un valor a string para comparación
     */
    protected function normalizarAString($valor)
    {
        if (is_null($valor)) {
            return '';
        }
        
        if ($valor === 'NULL' || $valor === 'null') {
            return '';
        }
        
        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }
        
        $str = trim((string)$valor);
        
        // Para números, normalizar representación
        if (is_numeric($str)) {
            $num = floatval($str);
            // Si es entero, devolver sin decimales
            if ($num == (int)$num) {
                return (string)(int)$num;
            }
            // Para decimales, redondear a 2 decimales
            return number_format($num, 2, '.', '');
        }
        
        return $str;
    }

    /**
     * Normaliza un valor para comparación (especialmente para números)
     */
    protected function normalizarValor($valor)
    {
        // Si es null, tratar como 0 para campos numéricos? Mejor mantener null
        if (is_null($valor)) {
            return 'NULL';
        }
        
        // Si es string numérico vacío
        if ($valor === '' || $valor === '') {
            return 'NULL';
        }
        
        // Si es numérico (int o float)
        if (is_numeric($valor)) {
            // Convertir a float para normalizar (0 y 0.00 serán iguales)
            $numero = floatval($valor);
            
            // Para enteros sin decimales, mostrar como int
            if ($numero == (int)$numero) {
                return (string)(int)$numero;
            }
            
            // Para decimales, mantener con 2 decimales
            return number_format($numero, 2, '.', '');
        }
        
        // Para strings, trim y convertir a string
        return (string)$valor;
    }

    /**
     * Formatea un valor para mostrarlo en auditoría
     */
    protected function formatearValorAuditoria($valor)
    {
        if (is_null($valor)) {
            return 'NULL';
        }
        
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }
        
        if (is_array($valor) || is_object($valor)) {
            return json_encode($valor);
        }
        
        if (strlen((string)$valor) > 100) {
            return substr((string)$valor, 0, 100) . '...';
        }
        
        return (string)$valor;
    }

    /**
     * Registra una auditoría en la base de datos
     * 
     * @param string $accion Acción realizada (Crear, Actualizar, Eliminar)
     * @param string $tabla Tabla afectada
     * @param int $registroId ID del registro afectado
     * @param string|null $nombre Nombre del registro (opcional)
     * @param array|null $valoresAnteriores Valores antes del cambio (opcional)
     * @param array|null $valoresNuevos Valores después del cambio (opcional)
     * @param string|null $detalleExtra Información adicional (opcional)
     */
    protected function registrarAuditoria($accion, $tabla, $registroId, $nombre = null, $valoresAnteriores = null, $valoresNuevos = null, $detalleExtra = null)
    {
        $nombreDisplay = $nombre ?? "Registro #{$registroId}";
        
        $descripcion = $this->construirDescripcionAuditoria(
            $accion, 
            $nombreDisplay, 
            $registroId, 
            $valoresAnteriores, 
            $valoresNuevos, 
            $detalleExtra
        );
        
        Auditoria::create([
            'user_id'       => auth()->id(),
            'accion'        => $accion,
            'tabla_afectada'=> $tabla,
            'registro_id'   => $registroId,
            'descripcion'   => $descripcion,
            'ip'            => request()->ip(),
            'navegador'     => request()->header('User-Agent')
        ]);
    }

    /**
     * Auditoría específica para actualizaciones
     * 
     * @param Model $modelo El modelo Eloquent
     * @param array $nuevosDatos Los nuevos datos que se van a aplicar
     * @param string|null $detalleExtra Información adicional
     */
    protected function auditarActualizacion($modelo, $nuevosDatos, $detalleExtra = null)
    {
        $tabla = $modelo->getTable();
        $id = $modelo->id;
        $nombre = $this->obtenerNombreModelo($modelo);
        
        // Obtener valores anteriores DIRECTAMENTE de la base de datos
        $valoresAnteriores = [];
        foreach (array_keys($nuevosDatos) as $campo) {
            // Consultar directamente el valor actual en la BD
            $valorActual = $modelo->fresh()->$campo ?? null;
            if ($valorActual !== null) {
                $valoresAnteriores[$campo] = $valorActual;
            }
        }
        
        // Construir descripción
        $cambios = $this->compararCambios($valoresAnteriores, $nuevosDatos);
        
        $descripcion = "Actualizar: {$nombre} (ID: {$id})";
        if (!empty($cambios)) {
            $descripcion .= " | Cambios: " . implode('; ', $cambios);
        } else {
            $descripcion .= " | Sin cambios detectados";
        }
        
        if ($detalleExtra) {
            $descripcion .= " | {$detalleExtra}";
        }
        
        // Registrar auditoría
        Auditoria::create([
            'user_id' => auth()->id(),
            'accion' => 'Actualizar',
            'tabla_afectada' => $tabla,
            'registro_id' => $id,
            'descripcion' => $descripcion,
            'ip' => request()->ip(),
            'navegador' => request()->header('User-Agent')
        ]);
    }

    /**
     * Obtiene un nombre descriptivo del modelo
     */
    protected function obtenerNombreModelo($modelo)
    {
        // Intentar con diferentes campos comunes
        if (isset($modelo->nombre)) {
            return $modelo->nombre;
        }
        if (isset($modelo->name)) {
            return $modelo->name;
        }
        if (isset($modelo->titulo)) {
            return $modelo->titulo;
        }
        if (isset($modelo->email)) {
            return $modelo->email;
        }
        
        // Si no hay campo de nombre, usar clase + ID
        return class_basename($modelo) . " #{$modelo->id}";
    }

    /**
     * Traduce IDs a nombres legibles usando un mapper personalizado
     * 
     * @param array $data Los datos a traducir
     * @param array $mapping Configuración de traducción [campo => modelo]
     * @return array Datos traducidos
     */
    protected function traducirIdsANombres($data, $mapping = [])
    {
        $mappingPorDefecto = [
            'distrito' => Distrito::class,
            'provincia' => Provincia::class,
            'departamento' => Departamento::class,
            'id_rol' => Rol::class,
        ];
        
        // Fusionar con el mapping personalizado
        $mapping = array_merge($mappingPorDefecto, $mapping);
        
        $traducido = [];
        
        foreach ($data as $key => $value) {
            if (isset($mapping[$key]) && !empty($value) && is_numeric($value)) {
                $modelo = $mapping[$key];
                if (class_exists($modelo)) {
                    $registro = $modelo::find($value);
                    $traducido[$key] = $registro ? $registro->nombre ?? $registro->name ?? $value : $value;
                } else {
                    $traducido[$key] = $value;
                }
            } else {
                $traducido[$key] = $value;
            }
        }
        
        return $traducido;
    }

    /**
     * Traduce específicamente ubicaciones (método conveniencia)
     */
    protected function traducirUbicaciones($data)
    {
        return $this->traducirIdsANombres($data, [
            'distrito' => Distrito::class,
            'provincia' => Provincia::class,
            'departamento' => Departamento::class,
        ]);
    }
}