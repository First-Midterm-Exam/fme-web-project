<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('practice_criteria', function (Blueprint $table): void {
            $table->integer('orden')->default(0)->after('description');
            $table->boolean('estado')->default(true)->after('required');
        });

        if (Schema::hasTable('criterios')) {
            $this->trasladarCriteriosHeredados();

            Schema::dropIfExists('criterios');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('criterios', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('practica_id');
            $table->text('descripcion');
            $table->integer('orden')->default(0);
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });

        Schema::table('practice_criteria', function (Blueprint $table): void {
            $table->dropColumn(['orden', 'estado']);
        });
    }

    private function trasladarCriteriosHeredados(): void
    {
        $heredados = DB::table('criterios')
            ->whereIn('practica_id', DB::table('practices')->select('id'))
            ->orderBy('id')
            ->get();

        foreach ($heredados as $indice => $criterio) {
            $codigo = DB::table('practices')->where('id', $criterio->practica_id)->value('code');

            DB::table('practice_criteria')->insert([
                'practice_id' => $criterio->practica_id,
                'code' => $codigo.'-H'.($indice + 1),
                'description' => $criterio->descripcion,
                'orden' => $criterio->orden,
                'required' => true,
                'estado' => $criterio->estado,
                'created_at' => $criterio->created_at,
                'updated_at' => $criterio->updated_at,
            ]);
        }
    }
};
