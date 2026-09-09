<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar el procedimiento si ya existe
        DB::unprepared('DROP PROCEDURE IF EXISTS GetVariacionSeleccionada');
        
        // Crear el procedimiento almacenado
        DB::unprepared("
            CREATE PROCEDURE GetVariacionSeleccionada(
                IN p_producto_id INT,
                IN p_variacion_id INT
            )
            BEGIN
                SELECT 
                    -- Datos de la variación
                    pv.id AS variacion_id,
                    pv.sku AS variacion_sku,
                    pv.precio_regular,
                    pv.precio_rebajado,
                    
                    -- STOCK: hereda del padre si la variación no gestiona inventario
                    CASE 
                        WHEN pv.gestion_inventario = 1 THEN pv.stock
                        WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN p.stock
                        ELSE 0
                    END AS stock,
                    
                    -- GESTION_INVENTARIO: hereda del padre si la variación no gestiona
                    CASE 
                        WHEN pv.gestion_inventario = 1 THEN pv.gestion_inventario
                        WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN 1
                        ELSE 0
                    END AS gestion_inventario,
                    
                    -- ESTADO_INVENTARIO: hereda del padre si la variación no gestiona
                    CASE 
                        WHEN pv.gestion_inventario = 1 THEN pv.estado_inventario
                        WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN p.estado_inventario
                        ELSE 'agotado'
                    END AS estado_inventario,
                    
                    -- BACKORDERS: hereda del padre si la variación no gestiona
                    CASE 
                        WHEN pv.gestion_inventario = 1 THEN pv.backorders
                        WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN p.backorders
                        ELSE 0
                    END AS backorders,
                    
                    -- VENDIDO_INDIVIDUALMENTE: hereda del padre
                    CASE 
                        WHEN p.vendido_individualmente = 1 THEN 1
                        ELSE 0
                    END AS vendido_individualmente,
                    
                    pv.fecha_inicio_rebaja,
                    pv.fecha_fin_rebaja,
                    
                    -- PESO: hereda del padre si la variación no tiene
                    COALESCE(pv.peso, p.peso) AS peso,
                    
                    -- PESO_UNIDAD: hereda del padre si la variación no tiene
                    COALESCE(pv.peso_unidad, p.peso_unidad) AS peso_unidad,
                    
                    -- LONGITUD: hereda del padre si la variación no tiene
                    COALESCE(pv.longitud, p.longitud) AS longitud,
                    
                    -- ANCHURA: hereda del padre si la variación no tiene
                    COALESCE(pv.anchura, p.anchura) AS anchura,
                    
                    -- ALTURA: hereda del padre si la variación no tiene
                    COALESCE(pv.altura, p.altura) AS altura,
                    
                    pv.descripcion AS variacion_descripcion,
                    
                    -- Precio final
                    CASE 
                        WHEN pv.precio_rebajado IS NOT NULL 
                            AND pv.precio_rebajado > 0 
                            AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= CURDATE())
                        THEN pv.precio_rebajado
                        ELSE pv.precio_regular
                    END AS precio_final,
                    
                    -- Descuento
                    CASE 
                        WHEN pv.precio_rebajado IS NOT NULL 
                            AND pv.precio_rebajado > 0 
                            AND pv.precio_regular > 0
                            AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= CURDATE())
                        THEN ROUND(((pv.precio_regular - pv.precio_rebajado) / pv.precio_regular) * 100)
                        ELSE 0
                    END AS descuento_porcentaje,
                    
                    -- ESTADO ACTUAL
                    CASE 
                        WHEN (
                            CASE 
                                WHEN pv.gestion_inventario = 1 THEN pv.gestion_inventario
                                WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN 1
                                ELSE 0
                            END
                        ) = 1 
                        AND (
                            CASE 
                                WHEN pv.gestion_inventario = 1 THEN pv.stock
                                WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN p.stock
                                ELSE 0
                            END
                        ) > 0 THEN 'en_stock'
                        
                        WHEN (
                            CASE 
                                WHEN pv.gestion_inventario = 1 THEN pv.gestion_inventario
                                WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN 1
                                ELSE 0
                            END
                        ) = 1 
                        AND (
                            CASE 
                                WHEN pv.gestion_inventario = 1 THEN pv.stock
                                WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN p.stock
                                ELSE 0
                            END
                        ) <= 0 
                        AND (
                            CASE 
                                WHEN pv.gestion_inventario = 1 THEN pv.backorders
                                WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN p.backorders
                                ELSE 0
                            END
                        ) = 1 THEN 'por_pedido'
                        
                        WHEN (
                            CASE 
                                WHEN pv.gestion_inventario = 1 THEN pv.gestion_inventario
                                WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN 1
                                ELSE 0
                            END
                        ) = 1 
                        AND (
                            CASE 
                                WHEN pv.gestion_inventario = 1 THEN pv.stock
                                WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN p.stock
                                ELSE 0
                            END
                        ) <= 0 
                        AND (
                            CASE 
                                WHEN pv.gestion_inventario = 1 THEN pv.backorders
                                WHEN pv.gestion_inventario = 0 AND p.gestion_inventario = 1 THEN p.backorders
                                ELSE 0
                            END
                        ) = 0 THEN 'agotado'
                        
                        ELSE 'agotado'
                    END AS estado_actual,
                    
                    -- PRIMERO: Definir la variación origen (la que tiene imágenes)
                    (
                        SELECT 
                            CASE 
                                WHEN EXISTS (
                                    SELECT 1 FROM variacion_imagenes WHERE variacion_id = pv.id
                                ) THEN pv.id
                                ELSE (
                                    SELECT pv2.id
                                    FROM producto_variaciones pv2
                                    INNER JOIN variacion_atributo_terminos vat2 ON vat2.variacion_id = pv2.id
                                    INNER JOIN atributo_terminos at2 ON at2.id = vat2.atributo_termino_id
                                    WHERE pv2.producto_padre_id = pv.producto_padre_id
                                      AND pv2.id != pv.id
                                      AND pv2.activo = 1
                                      AND pv2.id IN (
                                          SELECT DISTINCT variacion_id FROM variacion_imagenes
                                      )
                                      AND vat2.atributo_termino_id IN (
                                          SELECT vat1.atributo_termino_id 
                                          FROM variacion_atributo_terminos vat1 
                                          WHERE vat1.variacion_id = pv.id
                                      )
                                    GROUP BY pv2.id
                                    ORDER BY 
                                        COUNT(DISTINCT vat2.atributo_termino_id) DESC,
                                        pv2.id
                                    LIMIT 1
                                )
                            END
                    ) AS variacion_origen_id,
                    
                    -- IMÁGENES: Usando la variación origen fija
                    COALESCE(
                        (SELECT imagen_path FROM variacion_imagenes WHERE variacion_id = pv.id ORDER BY id LIMIT 1),
                        (SELECT imagen_path FROM variacion_imagenes 
                         WHERE variacion_id = (
                             SELECT 
                                 CASE 
                                     WHEN EXISTS (SELECT 1 FROM variacion_imagenes WHERE variacion_id = pv.id) THEN pv.id
                                     ELSE (
                                         SELECT pv2.id
                                         FROM producto_variaciones pv2
                                         INNER JOIN variacion_atributo_terminos vat2 ON vat2.variacion_id = pv2.id
                                         WHERE pv2.producto_padre_id = pv.producto_padre_id
                                           AND pv2.id != pv.id
                                           AND pv2.activo = 1
                                           AND pv2.id IN (SELECT DISTINCT variacion_id FROM variacion_imagenes)
                                           AND vat2.atributo_termino_id IN (
                                               SELECT vat1.atributo_termino_id 
                                               FROM variacion_atributo_terminos vat1 
                                               WHERE vat1.variacion_id = pv.id
                                           )
                                         GROUP BY pv2.id
                                         ORDER BY COUNT(DISTINCT vat2.atributo_termino_id) DESC, pv2.id
                                         LIMIT 1
                                     )
                                 END
                         )
                         ORDER BY id LIMIT 1),
                        (SELECT imagen_miniatura FROM productos WHERE id = pv.producto_padre_id)
                    ) AS img1,
                    
                    COALESCE(
                        (SELECT imagen_path FROM variacion_imagenes WHERE variacion_id = pv.id ORDER BY id LIMIT 1 OFFSET 1),
                        (SELECT imagen_path FROM variacion_imagenes 
                         WHERE variacion_id = (
                             SELECT 
                                 CASE 
                                     WHEN EXISTS (SELECT 1 FROM variacion_imagenes WHERE variacion_id = pv.id) THEN pv.id
                                     ELSE (
                                         SELECT pv2.id
                                         FROM producto_variaciones pv2
                                         INNER JOIN variacion_atributo_terminos vat2 ON vat2.variacion_id = pv2.id
                                         WHERE pv2.producto_padre_id = pv.producto_padre_id
                                           AND pv2.id != pv.id
                                           AND pv2.activo = 1
                                           AND pv2.id IN (SELECT DISTINCT variacion_id FROM variacion_imagenes)
                                           AND vat2.atributo_termino_id IN (
                                               SELECT vat1.atributo_termino_id 
                                               FROM variacion_atributo_terminos vat1 
                                               WHERE vat1.variacion_id = pv.id
                                           )
                                         GROUP BY pv2.id
                                         ORDER BY COUNT(DISTINCT vat2.atributo_termino_id) DESC, pv2.id
                                         LIMIT 1
                                     )
                                 END
                         )
                         ORDER BY id LIMIT 1 OFFSET 1),
                        NULL
                    ) AS img2,
                    
                    COALESCE(
                        (SELECT imagen_path FROM variacion_imagenes WHERE variacion_id = pv.id ORDER BY id LIMIT 1 OFFSET 2),
                        (SELECT imagen_path FROM variacion_imagenes 
                         WHERE variacion_id = (
                             SELECT 
                                 CASE 
                                     WHEN EXISTS (SELECT 1 FROM variacion_imagenes WHERE variacion_id = pv.id) THEN pv.id
                                     ELSE (
                                         SELECT pv2.id
                                         FROM producto_variaciones pv2
                                         INNER JOIN variacion_atributo_terminos vat2 ON vat2.variacion_id = pv2.id
                                         WHERE pv2.producto_padre_id = pv.producto_padre_id
                                           AND pv2.id != pv.id
                                           AND pv2.activo = 1
                                           AND pv2.id IN (SELECT DISTINCT variacion_id FROM variacion_imagenes)
                                           AND vat2.atributo_termino_id IN (
                                               SELECT vat1.atributo_termino_id 
                                               FROM variacion_atributo_terminos vat1 
                                               WHERE vat1.variacion_id = pv.id
                                           )
                                         GROUP BY pv2.id
                                         ORDER BY COUNT(DISTINCT vat2.atributo_termino_id) DESC, pv2.id
                                         LIMIT 1
                                     )
                                 END
                         )
                         ORDER BY id LIMIT 1 OFFSET 2),
                        NULL
                    ) AS img3,
                    
                    COALESCE(
                        (SELECT imagen_path FROM variacion_imagenes WHERE variacion_id = pv.id ORDER BY id LIMIT 1 OFFSET 3),
                        (SELECT imagen_path FROM variacion_imagenes 
                         WHERE variacion_id = (
                             SELECT 
                                 CASE 
                                     WHEN EXISTS (SELECT 1 FROM variacion_imagenes WHERE variacion_id = pv.id) THEN pv.id
                                     ELSE (
                                         SELECT pv2.id
                                         FROM producto_variaciones pv2
                                         INNER JOIN variacion_atributo_terminos vat2 ON vat2.variacion_id = pv2.id
                                         WHERE pv2.producto_padre_id = pv.producto_padre_id
                                           AND pv2.id != pv.id
                                           AND pv2.activo = 1
                                           AND pv2.id IN (SELECT DISTINCT variacion_id FROM variacion_imagenes)
                                           AND vat2.atributo_termino_id IN (
                                               SELECT vat1.atributo_termino_id 
                                               FROM variacion_atributo_terminos vat1 
                                               WHERE vat1.variacion_id = pv.id
                                           )
                                         GROUP BY pv2.id
                                         ORDER BY COUNT(DISTINCT vat2.atributo_termino_id) DESC, pv2.id
                                         LIMIT 1
                                     )
                                 END
                         )
                         ORDER BY id LIMIT 1 OFFSET 3),
                        NULL
                    ) AS img4,
                    
                    COALESCE(
                        (SELECT imagen_path FROM variacion_imagenes WHERE variacion_id = pv.id ORDER BY id LIMIT 1 OFFSET 4),
                        (SELECT imagen_path FROM variacion_imagenes 
                         WHERE variacion_id = (
                             SELECT 
                                 CASE 
                                     WHEN EXISTS (SELECT 1 FROM variacion_imagenes WHERE variacion_id = pv.id) THEN pv.id
                                     ELSE (
                                         SELECT pv2.id
                                         FROM producto_variaciones pv2
                                         INNER JOIN variacion_atributo_terminos vat2 ON vat2.variacion_id = pv2.id
                                         WHERE pv2.producto_padre_id = pv.producto_padre_id
                                           AND pv2.id != pv.id
                                           AND pv2.activo = 1
                                           AND pv2.id IN (SELECT DISTINCT variacion_id FROM variacion_imagenes)
                                           AND vat2.atributo_termino_id IN (
                                               SELECT vat1.atributo_termino_id 
                                               FROM variacion_atributo_terminos vat1 
                                               WHERE vat1.variacion_id = pv.id
                                           )
                                         GROUP BY pv2.id
                                         ORDER BY COUNT(DISTINCT vat2.atributo_termino_id) DESC, pv2.id
                                         LIMIT 1
                                     )
                                 END
                         )
                         ORDER BY id LIMIT 1 OFFSET 4),
                        NULL
                    ) AS img5,
                    
                    COALESCE(
                        (SELECT imagen_path FROM variacion_imagenes WHERE variacion_id = pv.id ORDER BY id LIMIT 1 OFFSET 5),
                        (SELECT imagen_path FROM variacion_imagenes 
                         WHERE variacion_id = (
                             SELECT 
                                 CASE 
                                     WHEN EXISTS (SELECT 1 FROM variacion_imagenes WHERE variacion_id = pv.id) THEN pv.id
                                     ELSE (
                                         SELECT pv2.id
                                         FROM producto_variaciones pv2
                                         INNER JOIN variacion_atributo_terminos vat2 ON vat2.variacion_id = pv2.id
                                         WHERE pv2.producto_padre_id = pv.producto_padre_id
                                           AND pv2.id != pv.id
                                           AND pv2.activo = 1
                                           AND pv2.id IN (SELECT DISTINCT variacion_id FROM variacion_imagenes)
                                           AND vat2.atributo_termino_id IN (
                                               SELECT vat1.atributo_termino_id 
                                               FROM variacion_atributo_terminos vat1 
                                               WHERE vat1.variacion_id = pv.id
                                           )
                                         GROUP BY pv2.id
                                         ORDER BY COUNT(DISTINCT vat2.atributo_termino_id) DESC, pv2.id
                                         LIMIT 1
                                     )
                                 END
                         )
                         ORDER BY id LIMIT 1 OFFSET 5),
                        NULL
                    ) AS img6
                    
                FROM producto_variaciones pv
                
                -- JOIN con el producto padre para heredar inventario
                INNER JOIN productos p ON p.id = pv.producto_padre_id
                
                WHERE 
                    pv.producto_padre_id = p_producto_id
                    AND pv.id = p_variacion_id
                    AND pv.activo = 1;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS GetVariacionSeleccionada');
    }
};