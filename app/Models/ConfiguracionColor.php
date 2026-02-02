<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionColor extends Model
{
    use HasFactory;

    protected $table = 'configuracion_colores';
    
    protected $fillable = [
        'tema_id',
        'variable_nombre',
        'variable_valor',
        'grupo',
        'descripcion',
        'orden'
    ];

    public function tema()
    {
        return $this->belongsTo(TemaColor::class, 'tema_id');
    }

    // Método para generar CSS dinámico
    public static function generarCssTemaActivo()
    {
        $temaActivo = TemaColor::obtenerActivo();
        
        if (!$temaActivo) {
            return '';
        }

        $variables = self::where('tema_id', $temaActivo->id)
            ->orderBy('grupo')
            ->orderBy('orden')
            ->get();

        $css = ":root {\n";
        
        foreach ($variables as $variable) {
            $css .= "    {$variable->variable_nombre}: {$variable->variable_valor};\n";
        }
        
        $css .= "}\n";

        // Agregar reglas CSS adicionales basadas en las variables
        $css .= self::generarReglasCssAdicionales($variables);
        
        return $css;
    }

    private static function generarReglasCssAdicionales($variables)
    {
        $css = "\n/* ===== REGLAS CSS AUTOMÁTICAS ===== */\n";
        
        // Reglas para botones Bootstrap
        $css .= "
/* Botones Bootstrap */
.btn-primary {
    background-color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    color: var(--color-text-primary) !important;
}

.btn-success {
    background-color: var(--color-success) !important;
    border-color: var(--color-success) !important;
    color: var(--color-text-success) !important;
}

.btn-danger {
    background-color: var(--color-danger) !important;
    border-color: var(--color-danger) !important;
    color: var(--color-text-danger) !important;
}

.btn-warning {
    background-color: var(--color-warning) !important;
    border-color: var(--color-warning) !important;
    color: var(--color-text-warning) !important;
}

.btn-info {
    background-color: var(--color-info) !important;
    border-color: var(--color-info) !important;
    color: var(--color-text-info) !important;
}
        ";

        // Reglas para tarjetas
        $css .= "
/* Tarjetas */
.card-header {
    background-color: var(--color-header) !important;
    color: var(--color-header-text) !important;
}

.card-body {
    background-color: var(--color-bg-card) !important;
}
        ";

        // Reglas para tablas
        $css .= "
/* Tablas */
.table thead {
    background-color: var(--color-table-thead) !important;
    color: var(--color-table-text-thead) !important;
}

.table tbody tr {
    background-color: var(--color-table) !important;
    color: var(--color-table-text) !important;
}

.table tbody tr:hover {
    background-color: var(--color-table-hover) !important;
    color: var(--color-table-hover-text) !important;
}
        ";

        // Reglas para sidebar
        $css .= "
/* Sidebar */
.sidebar {
    background: var(--sidebar-bg) !important;
}

.sidebar .menu-link {
    color: var(--color-text-secondary-sidebar) !important;
}

.sidebar .menu-item:hover .menu-link {
    background-color: var(--color-sidebar-hover) !important;
    color: var(--color-sidebar-text-hover) !important;
}
        ";

        // Reglas generales
        $css .= "
/* Elementos generales */
body {
    background-color: var(--color-bg) !important;
}

input, select, textarea {
    background-color: var(--color-datatable-search-card) !important;
    color: var(--color-datatable-search-card-text) !important;
    border-color: var(--color-border-card) !important;
}

label {
    color: var(--color-text-label) !important;
}

h1, h2, h3, h4, h5, h6, p {
    color: var(--color-text) !important;
}
        ";

        return $css;
    }
}