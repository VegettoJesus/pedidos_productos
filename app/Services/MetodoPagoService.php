<?php

namespace App\Services;

use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\ProductoVariacion;
use Illuminate\Support\Collection;

class MetodoPagoService
{
    /**
     * Evalúa TODOS los métodos de pago activos contra el carrito y devuelve
     * una lista con metadata de disponibilidad.
     *
     * @param Collection $items  Colección de CarritoItem con producto/variacion cargados
     * @return array [
     *   [
     *     'metodo' => MetodoPago,
     *     'disponible' => bool,
     *     'mensaje' => string,  // si no disponible
     *     'cargo_adicional' => float,
     *     'impuesto_cargo' => float,
     *     'total_cargo' => float,
     *   ],
     * ]
     */
    public function evaluar(Collection $items, float $impuestos = 0, float $envio = 0): array
    {
        $metodos = MetodoPago::obtenerActivos();
        $subtotal = $items->sum(fn($i) => $i->cantidad * $i->precio_unitario);

        $resultado = [];

        foreach ($metodos as $metodo) {
            $evaluacion = $this->evaluarMetodo($metodo, $items, $subtotal, $impuestos, $envio);
            $resultado[] = array_merge(['metodo' => $metodo], $evaluacion);
        }

        return $resultado;
    }

    /**
     * Evalúa un método de pago individual.
     */
    public function evaluarMetodo(
        MetodoPago $metodo,
        Collection $items,
        float $subtotal,
        float $impuestos = 0,
        float $envio = 0
    ): array {
        $config = $metodo->configuracion ?? [];

        $noDisponibleMsg = $config['mensaje_no_disponible']
            ?? 'Este método de pago no está disponible para tu pedido.';

        // ============================================
        // 1. VALIDAR desactivar_si_importe_mayor
        // ============================================
        $desactivarSiMayor = $config['desactivar_si_importe_mayor'] ?? null;
        if ($desactivarSiMayor !== null && $desactivarSiMayor !== '' && $desactivarSiMayor > 0) {
            if ($subtotal >= (float) $desactivarSiMayor) {
                return [
                    'disponible' => false,
                    'mensaje' => $noDisponibleMsg,
                    'cargo_adicional' => 0,
                    'impuesto_cargo' => 0,
                    'total_cargo' => 0,
                ];
            }
        }

        // ============================================
        // 2. VALIDAR restricción de CATEGORÍAS
        // ============================================
        $modoCategoria = $config['restriccion_categoria_modo'] ?? 'ninguno';
        $categoriasDesactivar = $config['categorias_desactivar'] ?? [];

        if ($modoCategoria !== 'ninguno' && !empty($categoriasDesactivar)) {
            if ($this->filtroCategorias($items, $modoCategoria, $categoriasDesactivar)) {
                return [
                    'disponible' => false,
                    'mensaje' => $noDisponibleMsg,
                    'cargo_adicional' => 0,
                    'impuesto_cargo' => 0,
                    'total_cargo' => 0,
                ];
            }
        }

        // ============================================
        // 3. VALIDAR restricción de PRODUCTOS
        // ============================================
        $modoProducto = $config['restriccion_producto_modo'] ?? 'ninguno';
        $productosDesactivar = $config['productos_desactivar'] ?? [];

        if ($modoProducto !== 'ninguno' && !empty($productosDesactivar)) {
            if ($this->filtroProductos($items, $modoProducto, $productosDesactivar)) {
                return [
                    'disponible' => false,
                    'mensaje' => $noDisponibleMsg,
                    'cargo_adicional' => 0,
                    'impuesto_cargo' => 0,
                    'total_cargo' => 0,
                ];
            }
        }

        // ============================================
        // 4. VALIDAR importe_limite (solo QR)
        // ============================================
        if ($metodo->slug === 'qr') {
            $importeLimite = $config['importe_limite'] ?? null;
            if ($importeLimite !== null && $importeLimite !== '' && (float) $importeLimite > 0) {
                if ($subtotal > (float) $importeLimite) {
                    $mensajeLimite = $config['mensaje_limite']
                        ?? 'El importe supera el límite permitido para este método de pago.';
                    return [
                        'disponible' => false,
                        'mensaje' => $mensajeLimite,
                        'cargo_adicional' => 0,
                        'impuesto_cargo' => 0,
                        'total_cargo' => 0,
                    ];
                }
            }
        }

        // ============================================
        // 5. CALCULAR CARGO ADICIONAL (solo si está disponible)
        // ============================================
        $cargo = $this->calcularCargoAdicional($metodo, $subtotal, $impuestos, $envio);

        return [
            'disponible' => true,
            'mensaje' => null,
            'cargo_adicional' => $cargo['cargo_base'],
            'impuesto_cargo' => $cargo['impuesto'],
            'total_cargo' => $cargo['total'],
            'base_calculo' => $cargo['base_calculo'],   // 🔥 útil para debug
        ];
    }

    /**
     * 🔥 Regla de CATEGORÍAS
     * - 'al_menos_uno': si AL MENOS UNA categoría del carrito está en categorias_desactivar → true
     * - 'todos': si TODAS las categorías del carrito están en categorias_desactivar → true
     */
    private function filtroCategorias(Collection $items, string $modo, array $categoriasDesactivar): bool
    {
        $categoriasEnCarrito = [];

        foreach ($items as $item) {
            $producto = $item->producto;
            if (!$producto) continue;

            $subcategoria = $producto->subcategoria;
            if (!$subcategoria) continue;

            $categoriaId = $subcategoria->id_categoria;
            $categoriasEnCarrito[$categoriaId] = true;
        }

        $categoriasEnCarrito = array_keys($categoriasEnCarrito);
        if (empty($categoriasEnCarrito)) return false;

        $categoriasDesactivar = array_map('intval', $categoriasDesactivar);

        if ($modo === 'al_menos_uno') {
            // Si al menos UNA categoría del carrito está en la lista negra
            foreach ($categoriasEnCarrito as $catId) {
                if (in_array((int) $catId, $categoriasDesactivar, true)) {
                    return true;
                }
            }
            return false;
        }

        if ($modo === 'todos') {
            // Si TODAS las categorías del carrito están en la lista negra
            foreach ($categoriasEnCarrito as $catId) {
                if (!in_array((int) $catId, $categoriasDesactivar, true)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * 🔥 Regla de PRODUCTOS
     * - 'al_menos_uno': si al menos UN producto del carrito está en productos_desactivar → true
     * - 'todos': si TODOS los productos del carrito están en productos_desactivar → true
     */
    private function filtroProductos(Collection $items, string $modo, array $productosDesactivar): bool
    {
        $productosEnCarrito = [];
        foreach ($items as $item) {
            $productosEnCarrito[] = (int) $item->producto_id;
        }
        $productosEnCarrito = array_unique($productosEnCarrito);
        if (empty($productosEnCarrito)) return false;

        $productosDesactivar = array_map('intval', $productosDesactivar);

        if ($modo === 'al_menos_uno') {
            foreach ($productosEnCarrito as $prodId) {
                if (in_array($prodId, $productosDesactivar, true)) {
                    return true;
                }
            }
            return false;
        }

        if ($modo === 'todos') {
            foreach ($productosEnCarrito as $prodId) {
                if (!in_array($prodId, $productosDesactivar, true)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * 🔥 Cálculo del cargo adicional
     * - tipo 'fijo': cargo = valor
     * - tipo 'porcentaje': cargo = subtotal * valor / 100
     * - Si 'impuesto_activo', se agrega impuesto_porcentaje sobre el cargo.
     */
    
    public function calcularCargoAdicional(
        MetodoPago $metodo,
        float $subtotal,
        float $impuestos = 0,
        float $envio = 0
    ): array {

        $config = $metodo->configuracion ?? [];
        $cargo = $config['cargo_adicional'] ?? [];

        $tipo = $cargo['tipo'] ?? 'porcentaje';
        $valor = (float) ($cargo['valor'] ?? 0);

        $incluirImpuestos = (bool) (
            $cargo['incluir_en_total']['impuestos'] ?? false
        );

        $incluirEnvio = (bool) (
            $cargo['incluir_en_total']['envio'] ?? false
        );

        // Base para calcular el cargo
        $baseCargo = $subtotal;

        if ($incluirImpuestos) {
            $baseCargo += $impuestos;
        }

        if ($incluirEnvio) {
            $baseCargo += $envio;
        }

        // Calcular cargo
        $cargoBase = 0;

        if ($valor > 0) {

            if ($tipo === 'fijo') {
                $cargoBase = $valor;
            } else {
                $cargoBase = $baseCargo * ($valor / 100);
            }
        }

        // Redondear cargo
        $cargoBase = round($cargoBase, 2);

        // Impuesto del cargo
        $impuesto = 0;

        if (
            ($cargo['impuesto_activo'] ?? false) &&
            $cargoBase > 0
        ) {
            $tasa = (float) (
                $cargo['impuesto_porcentaje'] ?? 0
            );

            $impuesto = round(
                $cargoBase * ($tasa / 100),
                2
            );
        }

        return [
            'base_calculo' => round($baseCargo, 2),
            'cargo_base' => $cargoBase,
            'impuesto' => $impuesto,
            'total' => round($cargoBase + $impuesto, 2),
        ];
    }
}