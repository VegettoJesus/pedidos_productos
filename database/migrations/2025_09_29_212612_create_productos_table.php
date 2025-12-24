<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla principal de productos
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('users')->onDelete('cascade');
            // Información básica
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            
            // Tipo y categorización
            $table->enum('tipo_producto', ['simple', 'variable', 'agrupado']);
            $table->enum('estado', ['borrador', 'publicado', 'oculto', 'archivado'])->default('borrador');
            $table->foreignId('id_subCategorias')->constrained('subcategorias')->onDelete('cascade');
            
            // Precios
            $table->decimal('precio_regular', 10, 2)->default(0);
            $table->decimal('precio_rebajado', 10, 2)->nullable();
            $table->date('fecha_inicio_rebaja')->nullable();
            $table->date('fecha_fin_rebaja')->nullable();
            
            // Inventario
            $table->boolean('gestion_inventario')->default(false);
            $table->enum('estado_inventario', ['existe', 'agotado', 'reservar'])->default('existe');
            $table->integer('stock')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->integer('max_stock')->nullable();
            $table->boolean('vendido_individualmente')->default(false);
            $table->boolean('backorders')->default(false); // permitir pedidos sin stock
            
            // SKU y referencia
            $table->string('sku')->unique()->nullable();
            $table->string('marca', 255)->nullable();
            
            // Dimensiones y peso
            $table->decimal('peso', 8, 2)->nullable();
            $table->string('peso_unidad')->nullable();
            $table->decimal('longitud', 8, 2)->nullable();
            $table->decimal('anchura', 8, 2)->nullable();
            $table->decimal('altura', 8, 2)->nullable();
            
            // Valoraciones
            $table->boolean('permite_valoraciones')->default(true);
            
            // Notas internas
            $table->text('nota_interna')->nullable();
            
            // Imagen principal
            $table->string('imagen_miniatura', 255)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('tipo_producto');
            $table->index('estado');
            $table->index('sku');
            $table->index('precio_regular');
            $table->index(['estado', 'tipo_producto']);
            $table->index(['id_subCategorias', 'estado']);
        });

        // Tabla de imágenes de productos
        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->string('imagen_path', 255);
            $table->string('alt_text', 255)->nullable();
            $table->timestamps();
            
            $table->index(['producto_id']);
        });

        // Tabla de etiquetas
        Schema::create('etiquetas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('slug')->unique();
            $table->string('color', 7)->nullable()->default('#3498db');
            $table->timestamps();
        });

        // Tabla pivote producto_etiqueta
        Schema::create('producto_etiqueta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('etiqueta_id')->constrained('etiquetas')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['producto_id', 'etiqueta_id'], 'prod_etq_unique');;
        });

        // Tabla de atributos
        Schema::create('atributos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Tabla de términos de atributos (materiales, colores, tallas, etc.)
        Schema::create('atributo_terminos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atributo_id')->constrained('atributos')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->string('slug');
            $table->text('descripcion')->nullable();
            $table->timestamps();
            
            $table->unique(['atributo_id', 'slug']);
        });

        // Tabla pivote producto_atributo
        Schema::create('producto_atributo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('atributo_id')->constrained('atributos')->onDelete('cascade');
            
            // AÑADIR: Configuración específica por producto
            $table->boolean('visible')->default(true);
            $table->boolean('variacion')->default(false);
            
            $table->timestamps();
            
            $table->unique(['producto_id', 'atributo_id'], 'prod_attr_unique');
        });

        Schema::create('producto_atributo_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_atributo_id')->constrained('producto_atributo')->onDelete('cascade');
            $table->foreignId('termino_id')->constrained('atributo_terminos')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['producto_atributo_id', 'termino_id'], 'prod_attr_val_unique');
        });

        // Tabla para productos variables (variaciones)
        Schema::create('producto_variaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_padre_id')->constrained('productos')->onDelete('cascade');
            
            // AÑADIR estos campos importantes:
            $table->string('nombre', 255)->nullable(); // No siempre necesario
            $table->text('descripcion')->nullable(); // Descripción específica
            $table->boolean('gestion_inventario')->default(true);
            $table->enum('estado_inventario', ['existe', 'agotado', 'reservar'])->default('existe');
            $table->boolean('backorders')->default(false);
            
            // Fechas para rebajas programadas
            $table->date('fecha_inicio_rebaja')->nullable();
            $table->date('fecha_fin_rebaja')->nullable();
            
            // Unidad de peso consistente
            $table->string('peso_unidad')->nullable();
            
            $table->string('sku')->unique()->nullable();
            $table->decimal('precio_regular', 10, 2)->default(0);
            $table->decimal('precio_rebajado', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->decimal('peso', 8, 2)->nullable();
            $table->decimal('longitud', 8, 2)->nullable();
            $table->decimal('anchura', 8, 2)->nullable();
            $table->decimal('altura', 8, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index('producto_padre_id');
            $table->index('sku');
        });

        // Tabla para valores de atributos en variaciones
        Schema::create('variacion_atributo_terminos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variacion_id')->constrained('producto_variaciones')->onDelete('cascade');
            $table->foreignId('atributo_termino_id')->constrained('atributo_terminos')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['variacion_id', 'atributo_termino_id'], 'var_attr_unique');
        });

        
        Schema::create('variacion_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variacion_id')->constrained('producto_variaciones')->onDelete('cascade');
            $table->string('imagen_path', 255);
            $table->timestamps();
            
            $table->index(['variacion_id']);
        });

        // Tabla para productos agrupados
        Schema::create('producto_agrupado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_padre_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('producto_hijo_id')->constrained('productos')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['producto_padre_id', 'producto_hijo_id'], 'agrupado_unique');
        });

        // Tabla para productos relacionados
        Schema::create('productos_relacionados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('producto_relacionado_id')->constrained('productos')->onDelete('cascade');
            $table->enum('tipo', ['upsell', 'crosssell'])->default('upsell');
            $table->timestamps();

            $table->unique(['producto_id', 'producto_relacionado_id'], 'relacion_unique');
        });

        Schema::create('producto_valoraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // o clientes si tienes otra tabla
            $table->tinyInteger('puntuacion'); // de 1 a 5, por ejemplo
            $table->text('comentario')->nullable();
            $table->boolean('aprobado')->default(false); // por si deseas moderarlas
            $table->timestamps();

            $table->unique(['producto_id', 'user_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('productos_relacionados');
        Schema::dropIfExists('producto_agrupado');
        Schema::dropIfExists('variacion_atributo_terminos');
        Schema::dropIfExists('producto_variaciones');
        Schema::dropIfExists('producto_atributo');
        Schema::dropIfExists('atributo_terminos');
        Schema::dropIfExists('atributos');
        Schema::dropIfExists('producto_etiqueta');
        Schema::dropIfExists('etiquetas');
        Schema::dropIfExists('producto_imagenes');
        Schema::dropIfExists('variacion_imagenes');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('producto_valoraciones');
    }
};