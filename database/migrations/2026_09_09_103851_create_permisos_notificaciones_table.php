<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos_notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('tipo_notificacion_id')->constrained('tipos_notificacion')->onDelete('cascade');
            $table->boolean('puede_ver')->default(false);
            $table->timestamps();

            $table->unique(['rol_id', 'tipo_notificacion_id']);
            $table->index(['rol_id', 'puede_ver']);
        });

        $this->insertarPermisosDefault();
    }

    private function insertarPermisosDefault()
    {
        // Obtener IDs de roles
        $rolAdmin = DB::table('roles')->where('name', 'admin')->value('id');
        $rolClient = DB::table('roles')->where('name', 'client')->value('id');
        $rolUser = DB::table('roles')->where('name', 'user')->value('id');
        $rolMarketing = DB::table('roles')->where('name', 'marketing')->value('id');
        $rolDeveloper = DB::table('roles')->where('name', 'developer')->value('id');

        // Obtener IDs de tipos de notificación
        $tipos = DB::table('tipos_notificacion')->pluck('id', 'slug');

        // Definir permisos por rol
        $permisos = [];

        // ADMIN: Ve TODO
        foreach ($tipos as $slug => $id) {
            $permisos[] = [
                'rol_id' => $rolAdmin,
                'tipo_notificacion_id' => $id,
                'puede_ver' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // CLIENT: Solo ve compras, promociones y usuario
        $tiposClient = ['compras', 'promociones', 'usuario'];
        foreach ($tipos as $slug => $id) {
            $permisos[] = [
                'rol_id' => $rolClient,
                'tipo_notificacion_id' => $id,
                'puede_ver' => in_array($slug, $tiposClient),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // USER: Similar a client pero con menos acceso
        $tiposUser = ['compras', 'usuario'];
        foreach ($tipos as $slug => $id) {
            $permisos[] = [
                'rol_id' => $rolUser,
                'tipo_notificacion_id' => $id,
                'puede_ver' => in_array($slug, $tiposUser),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // MARKETING: Ve promociones y sistema
        $tiposMarketing = ['promociones', 'sistema','usuario'];
        foreach ($tipos as $slug => $id) {
            $permisos[] = [
                'rol_id' => $rolMarketing,
                'tipo_notificacion_id' => $id,
                'puede_ver' => in_array($slug, $tiposMarketing),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // DEVELOPER: Ve sistema, seguridad y errores
        $tiposDeveloper = ['sistema', 'seguridad', 'error-sistema','usuario'];
        foreach ($tipos as $slug => $id) {
            $permisos[] = [
                'rol_id' => $rolDeveloper,
                'tipo_notificacion_id' => $id,
                'puede_ver' => in_array($slug, $tiposDeveloper),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        DB::table('permisos_notificaciones')->insert($permisos);
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos_notificaciones');
    }
};