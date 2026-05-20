<?php
// app/Helpers/ConfiguracionHelper.php

namespace App\Helpers;

use App\Models\ConfiguracionSistema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use App\Services\MenuService;
use App\Models\FooterColumn;
use App\Models\FooterLink;
use App\Models\FooterContact;
use App\Models\FooterSocial;

class ConfiguracionHelper
{
    /**
     * Tiempo de caché en segundos (24 horas)
     */
    const CACHE_TIME = 86400;

    /**
     * Obtener configuración con caché
     */
    private static function getConfig()
    {
        return Cache::remember('configuracion_sistema', self::CACHE_TIME, function () {
            return ConfiguracionSistema::first();
        });
    }

    /**
     * Obtener el favicon de la configuración o el default
     */
    public static function getFavicon()
    {
        $configuracion = self::getConfig();
        
        if ($configuracion && $configuracion->icono_site) {
            $iconoSite = $configuracion->icono_site;
            
            // Si es una URL completa
            if (filter_var($iconoSite, FILTER_VALIDATE_URL)) {
                return $iconoSite;
            }
            
            // Verificar si el archivo existe
            if (file_exists(public_path($iconoSite))) {
                return asset($iconoSite);
            }
        }
        
        return asset('img/img-empresa-default.png');
    }
    
    /**
     * Obtener el título de la página (usando abreviatura si existe)
     */
    public static function getPageTitle($seccion = null)
    {
        $configuracion = self::getConfig();
        
        if ($configuracion) {
            $baseTitle = $configuracion->abreviatura_titulo ?? $configuracion->titulo_site ?? 'EMP';
        } else {
            $baseTitle = 'EMP';
        }
        
        if ($seccion) {
            return "{$seccion} | {$baseTitle}";
        }
        
        return $baseTitle;
    }
    
    /**
     * Obtener el nombre completo de la empresa
     */
    public static function getCompanyName()
    {
        $configuracion = self::getConfig();
        return $configuracion->titulo_site ?? 'Mi Empresa';
    }
    
    /**
     * Obtener la abreviatura
     */
    public static function getAbbreviation()
    {
        $configuracion = self::getConfig();
        return $configuracion->abreviatura_titulo ?? 'EMP';
    }

    /**
     * Obtener descripción corta
     */
    public static function getDescription()
    {
        $configuracion = self::getConfig();
        return $configuracion->descripcion_corta ?? 'Sistema de gestión empresarial';
    }

    /**
     * Obtener email del administrador
     */
    public static function getAdminEmail()
    {
        $configuracion = self::getConfig();
        return $configuracion->email_admin ?? config('mail.from.address');
    }

    /**
     * Obtener footer text
     */
    public static function getFooterText()
    {
        $configuracion = self::getConfig();
        return $configuracion->footer_text ?? 'Todos los derechos reservados.';
    }

    /**
     * Obtener todas las columnas de footer activas, ordenadas
     */
    public static function getFooterColumns()
    {
        return Cache::remember('footer_columns_active', self::CACHE_TIME, function () {
            return FooterColumn::where('active', true)
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Obtener enlaces de una columna (solo los activos)
     */
    public static function getFooterLinks($columnId)
    {
        return Cache::remember("footer_links_{$columnId}", self::CACHE_TIME, function () use ($columnId) {
            return FooterLink::where('column_id', $columnId)
                ->where('active', true)
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Obtener el texto a mostrar para un enlace (usa text si existe, si no la URL)
     */
    public static function getLinkDisplayText($link)
    {
        if (!empty($link->text)) {
            return e($link->text);
        }
        if (!empty($link->url)) {
            return e($link->url);
        }
        return 'Enlace'; // Fallback (no debería ocurrir porque validas al menos uno)
    }

    /**
     * Renderizar un enlace del footer (con o sin URL)
     */
    public static function renderFooterLink($link)
    {
        $iconHtml = self::renderFooterIcon($link->icon);
        $displayText = self::getLinkDisplayText($link);

        if (empty($link->url)) {
            // Sin URL: solo texto plano
            return '<span class="footer-link-text">' . $iconHtml . $displayText . '</span>';
        }

        $url = self::normalizeUrl($link->url);
        return '<a href="' . $url . '" class="footer-link" target="_blank" rel="noopener noreferrer">' . $iconHtml . $displayText . '</a>';
    }

    /**
     * Obtener contacto de una columna mixta
     */
    public static function getFooterContact($columnId)
    {
        return Cache::remember("footer_contact_{$columnId}", self::CACHE_TIME, function () use ($columnId) {
            return FooterContact::where('column_id', $columnId)->first();
        });
    }

    /**
     * Obtener redes sociales de una columna mixta (solo activas)
     */
    public static function getFooterSocialNetworks($columnId)
    {
        return Cache::remember("footer_social_{$columnId}", self::CACHE_TIME, function () use ($columnId) {
            return FooterSocial::where('column_id', $columnId)
                ->where('active', true)
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Genera el HTML del icono (clase CSS o imagen)
     */
    public static function renderFooterIcon($icon)
    {
        if (empty($icon)) {
            return '';
        }
        
        if (str_starts_with($icon, '/') || preg_match('/\.(ico|png|jpg|jpeg|gif|svg|webp)$/i', $icon)) {
            $filename = pathinfo($icon, PATHINFO_FILENAME);
            $largeSuffixes = ['_link', '_email', '_phone', '_address'];
            $useLarge = false;
            
            foreach ($largeSuffixes as $suffix) {
                if (str_ends_with($filename, $suffix)) {
                    $useLarge = true;
                    break;
                }
            }
            
            $sizeStyle = $useLarge
                ? 'width: 1.7rem; height: 1.7rem; object-fit: contain;'
                : 'width: 20px; height: 20px; object-fit: contain;';
            
            return '<img src="' . asset($icon) . '" alt="icono" style="' . $sizeStyle . '">';
        }
        
        return '<i class="' . e($icon) . '"></i>';
    }

    public static function normalizeUrl($url)
    {
        if (empty($url)) {
            return '';
        }
        
        $trimmed = trim($url);
        
        // Protocolos ya válidos o casos especiales
        if (preg_match('/^(https?:\/\/|#|\/|mailto:|tel:)/i', $trimmed)) {
            return $trimmed;
        }
        
        // Si no tiene protocolo, agregamos https:// por defecto
        return 'https://' . $trimmed;
    }

    public static function clearFooterCache()
    {
        Cache::forget('footer_columns_active');
        // Podrías eliminar todas las keys que empiecen por 'footer_links_' o 'footer_contact_'
        // Para simplificar, borra todas las relacionadas con footer
        $keys = ['footer_columns_active'];
        foreach (FooterColumn::pluck('id') as $id) {
            $keys[] = "footer_links_{$id}";
            $keys[] = "footer_contact_{$id}";
            $keys[] = "footer_social_{$id}";
        }
        Cache::deleteMultiple($keys);
    }

    /**
     * Obtener la información de la empresa (único registro)
     */
    public static function getEmpresaInfo()
    {
        return Cache::remember('empresa_informacion', self::CACHE_TIME, function () {
            return \App\Models\EmpresaInformacion::first();
        });
    }

    /**
     * Obtener teléfono de la empresa (prioriza celular si existe)
     */
    public static function getPhone()
    {
        $empresa = self::getEmpresaInfo();
        if ($empresa) {
            return $empresa->celular ?: $empresa->telefono;
        }
        return null;
    }

    /**
     * Obtener email de la empresa (por defecto el admin email)
     */
    public static function getEmail()
    {
        $empresa = self::getEmpresaInfo();
        if ($empresa && $empresa->email) {
            return $empresa->email;
        }
        return self::getAdminEmail(); // fallback al email de configuración
    }

    /**
     * Obtener dirección completa formateada
     */
    public static function getFullAddress()
    {
        $empresa = self::getEmpresaInfo();
        return $empresa ? $empresa->direccion_completa : null;
    }

    /**
     * Limpiar caché de empresa también en clearCache()
     */
    public static function clearCache()
    {
        Cache::forget('configuracion_sistema');
        Cache::forget('configuracion_sistema_array');
        Cache::forget('empresa_informacion');
        MenuService::limpiarCache();
    }
}