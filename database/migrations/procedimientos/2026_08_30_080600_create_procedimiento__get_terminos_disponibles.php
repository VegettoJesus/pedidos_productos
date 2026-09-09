<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS GetTerminosDisponibles');

        DB::unprepared("
            CREATE PROCEDURE GetTerminosDisponibles(
            IN p_producto_id INT,
            IN p_terminos_csv VARCHAR(500)
        )
        BEGIN
            -- Variables
            DECLARE v_count INT DEFAULT 0;
            DECLARE v_pos INT DEFAULT 1;
            DECLARE v_termino_id INT DEFAULT 0;
            DECLARE v_csv_length INT DEFAULT 0;
            
            -- =====================================================
            -- 1. CREAR TABLA TEMPORAL DE SELECCIONES
            -- =====================================================
            DROP TEMPORARY TABLE IF EXISTS tmp_selecciones;
            
            CREATE TEMPORARY TABLE tmp_selecciones (
                atributo_id INT NOT NULL,
                termino_id INT NOT NULL,
                PRIMARY KEY (atributo_id, termino_id)
            );
            
            -- Insertar selecciones desde CSV (dinámico, sin límite de 20)
            IF p_terminos_csv IS NOT NULL AND p_terminos_csv != '' THEN
                SET v_csv_length = LENGTH(p_terminos_csv) - LENGTH(REPLACE(p_terminos_csv, ',', '')) + 1;
                
                -- Limitar a 100 términos por seguridad
                IF v_csv_length > 100 THEN
                    SET v_csv_length = 100;
                END IF;
                
                INSERT INTO tmp_selecciones (atributo_id, termino_id)
                SELECT DISTINCT 
                    at.atributo_id,
                    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(p_terminos_csv, ',', numbers.n), ',', -1) AS UNSIGNED) AS termino_id
                FROM (
                    SELECT 
                        (t1.n * 10 + t2.n + 1) AS n
                    FROM 
                        (SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
                        (SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2
                    WHERE (t1.n * 10 + t2.n + 1) <= v_csv_length
                ) numbers
                INNER JOIN atributo_terminos at
                    ON at.id = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(p_terminos_csv, ',', numbers.n), ',', -1) AS UNSIGNED)
                WHERE numbers.n <= v_csv_length;
            END IF;
            
            -- =====================================================
            -- 2. CREAR TABLA DE VARIACIONES COMPATIBLES
            -- =====================================================
            DROP TEMPORARY TABLE IF EXISTS tmp_variaciones_compatibles;
            
            CREATE TEMPORARY TABLE tmp_variaciones_compatibles (
                variacion_id INT PRIMARY KEY
            );
            
            -- Caso 1: No hay selecciones (todas las variaciones activas son compatibles)
            IF (SELECT COUNT(*) FROM tmp_selecciones) = 0 THEN
                INSERT INTO tmp_variaciones_compatibles (variacion_id)
                SELECT
                    pv.id
                FROM producto_variaciones pv
                INNER JOIN productos p ON p.id = pv.producto_padre_id
                WHERE pv.producto_padre_id = p_producto_id
                AND pv.activo = 1
                -- Verificar inventario
                AND (
                    (pv.gestion_inventario = 1 AND (pv.stock > 0 OR (pv.stock <= 0 AND pv.backorders = 1)))
                    OR
                    (pv.gestion_inventario = 0 AND p.gestion_inventario = 1 AND (p.stock > 0 OR (p.stock <= 0 AND p.backorders = 1)))
                    OR
                    (pv.gestion_inventario = 0 AND p.gestion_inventario = 0)
                );
            ELSE
                -- Caso 2: Hay selecciones, verificar compatibilidad
                INSERT INTO tmp_variaciones_compatibles (variacion_id)
                SELECT
                    pv.id
                FROM producto_variaciones pv
                INNER JOIN productos p ON p.id = pv.producto_padre_id
                WHERE pv.producto_padre_id = p_producto_id
                AND pv.activo = 1
                -- Verificar inventario
                AND (
                    (pv.gestion_inventario = 1 AND (pv.stock > 0 OR (pv.stock <= 0 AND pv.backorders = 1)))
                    OR
                    (pv.gestion_inventario = 0 AND p.gestion_inventario = 1 AND (p.stock > 0 OR (p.stock <= 0 AND p.backorders = 1)))
                    OR
                    (pv.gestion_inventario = 0 AND p.gestion_inventario = 0)
                )
                -- ✅ Verificar que TODAS las selecciones sean compatibles
                AND NOT EXISTS (
                    SELECT 1
                    FROM tmp_selecciones s
                    WHERE NOT EXISTS (
                        -- La variación tiene el término seleccionado
                        SELECT 1
                        FROM variacion_atributo_terminos vat_match
                        WHERE vat_match.variacion_id = pv.id
                            AND vat_match.atributo_termino_id = s.termino_id
                    )
                    AND EXISTS (
                        -- La variación tiene ALGÚN término de ese atributo
                        SELECT 1
                        FROM variacion_atributo_terminos vat_attr
                        INNER JOIN atributo_terminos at_attr
                            ON at_attr.id = vat_attr.atributo_termino_id
                        WHERE vat_attr.variacion_id = pv.id
                            AND at_attr.atributo_id = s.atributo_id
                    )
                );
            END IF;
            
            -- =====================================================
            -- 3. OBTENER LOS TÉRMINOS DISPONIBLES
            -- =====================================================
            SELECT 
                resultado.termino_id,
                resultado.termino_nombre,
                resultado.atributo_id,
                resultado.atributo_nombre,
                MAX(resultado.tiene_stock) AS tiene_stock
            FROM (
                -- CASO A: Términos que las variaciones tienen directamente
                SELECT
                    at.id AS termino_id,
                    at.nombre AS termino_nombre,
                    at.atributo_id,
                    a.nombre AS atributo_nombre,
                    CASE 
                        WHEN pv.gestion_inventario = 1 AND pv.stock > 0 THEN 1
                        WHEN pv.gestion_inventario = 1 AND pv.stock <= 0 AND pv.backorders = 1 THEN 1
                        WHEN pv.gestion_inventario = 0 
                            AND (p.estado_inventario IN ('existe', 'reservar') 
                                OR (p.gestion_inventario = 0 AND p.estado_inventario IN ('existe', 'reservar'))) 
                        THEN 1
                        ELSE 0
                    END AS tiene_stock
                FROM tmp_variaciones_compatibles vc
                INNER JOIN variacion_atributo_terminos vat
                    ON vat.variacion_id = vc.variacion_id
                INNER JOIN atributo_terminos at
                    ON at.id = vat.atributo_termino_id
                INNER JOIN atributos a
                    ON a.id = at.atributo_id
                INNER JOIN producto_variaciones pv
                    ON pv.id = vc.variacion_id
                INNER JOIN productos p
                    ON p.id = pv.producto_padre_id
                
                UNION
                
                -- CASO B: La variación NO tiene el atributo (CUALQUIERA)
                SELECT
                    at_producto.id AS termino_id,
                    at_producto.nombre AS termino_nombre,
                    at_producto.atributo_id,
                    a.nombre AS atributo_nombre,
                    1 AS tiene_stock
                FROM tmp_variaciones_compatibles vc
                INNER JOIN producto_atributo pa
                    ON pa.producto_id = p_producto_id
                    AND pa.variacion = 1  
                    AND pa.visible = 1    
                INNER JOIN producto_atributo_valores pav
                    ON pav.producto_atributo_id = pa.id
                INNER JOIN atributo_terminos at_producto
                    ON at_producto.id = pav.termino_id
                INNER JOIN atributos a
                    ON a.id = at_producto.atributo_id
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM variacion_atributo_terminos vat_existente
                    INNER JOIN atributo_terminos at_existente
                        ON at_existente.id = vat_existente.atributo_termino_id
                    WHERE vat_existente.variacion_id = vc.variacion_id
                    AND at_existente.atributo_id = pa.atributo_id
                )
            ) AS resultado
            GROUP BY 
                resultado.termino_id,
                resultado.termino_nombre,
                resultado.atributo_id,
                resultado.atributo_nombre
            ORDER BY 
                resultado.atributo_id, 
                resultado.termino_id;
            
            -- =====================================================
            -- 4. LIMPIAR TABLAS TEMPORALES
            -- =====================================================
            DROP TEMPORARY TABLE IF EXISTS tmp_selecciones;
            DROP TEMPORARY TABLE IF EXISTS tmp_variaciones_compatibles;
            
        END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS GetTerminosDisponibles');
    }
};