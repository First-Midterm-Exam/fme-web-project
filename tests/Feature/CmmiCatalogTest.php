<?php

use App\Models\Practice;
use App\Models\PracticeArea;
use App\Models\PracticeCriterion;
use Database\Seeders\CmmiCatalogSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(CmmiCatalogSeeder::class);
});

// =============================================================================
// 1. El seeder carga 8 Practice Areas
// =============================================================================
test('cmmi catalog seeder loads exactly 8 practice areas', function () {
    expect(PracticeArea::count())->toBe(8);

    $expectedCodes = ['PLAN', 'EST', 'RDM', 'TS', 'PR', 'VV', 'PQA', 'CM'];
    $loadedCodes = PracticeArea::pluck('code')->all();

    foreach ($expectedCodes as $code) {
        expect($loadedCodes)->toContain($code);
    }
});

// =============================================================================
// 2. El seeder carga 26 prácticas
// =============================================================================
test('cmmi catalog seeder loads exactly 26 practices across levels 1 to 3', function () {
    expect(Practice::count())->toBe(26);

    expect(Practice::where('level', 1)->count())->toBe(8)
        ->and(Practice::where('level', 2)->count())->toBe(10)
        ->and(Practice::where('level', 3)->count())->toBe(8);
});

// =============================================================================
// 3. Los criterios definidos quedan cargados
// =============================================================================
test('cmmi catalog seeder loads criteria for all practices', function () {
    $criteriaCount = PracticeCriterion::count();
    expect($criteriaCount)->toBe(52); // 26 practices * 2 criteria

    $practices = Practice::with('criteria')->get();
    foreach ($practices as $practice) {
        expect($practice->criteria)->not->toBeEmpty()
            ->and($practice->criteria->count())->toBeGreaterThanOrEqual(2);
    }
});

// =============================================================================
// 4. Cada práctica pertenece a una Practice Area
// =============================================================================
test('each practice belongs to a valid practice area', function () {
    $practices = Practice::with('practiceArea')->get();

    foreach ($practices as $practice) {
        expect($practice->practiceArea)->toBeInstanceOf(PracticeArea::class)
            ->and($practice->practice_area_id)->not->toBeNull();
    }
});

// =============================================================================
// 5. Cada criterio pertenece a una práctica
// =============================================================================
test('each criterion belongs to a valid practice', function () {
    $criteria = PracticeCriterion::with('practice')->get();

    foreach ($criteria as $criterion) {
        expect($criterion->practice)->toBeInstanceOf(Practice::class)
            ->and($criterion->practice_id)->not->toBeNull();
    }
});

// =============================================================================
// 6. Una práctica puede consultar sus criterios mediante criteria()
// =============================================================================
test('a practice can query its criteria through the criteria relationship', function () {
    $practice = Practice::where('code', 'PLAN 1.1')->firstOrFail();

    expect($practice->criteria)->toHaveCount(2)
        ->and($practice->criteria->first())->toBeInstanceOf(PracticeCriterion::class)
        ->and($practice->criteria->pluck('code')->all())->toBe(['PLAN 1.1-C1', 'PLAN 1.1-C2']);
});

// =============================================================================
// 7. El campo required se obtiene como booleano
// =============================================================================
test('required field casts to a boolean on practice criterion model', function () {
    $criterion = PracticeCriterion::firstOrFail();

    expect($criterion->required)->toBeBool()
        ->and($criterion->required)->toBeTrue();
});

// =============================================================================
// 8. Ejecutar CmmiCatalogSeeder dos veces no altera los conteos (idempotencia)
// =============================================================================
test('running cmmi catalog seeder multiple times is strictly idempotent', function () {
    $areasBefore = PracticeArea::count();
    $practicesBefore = Practice::count();
    $criteriaBefore = PracticeCriterion::count();

    expect($areasBefore)->toBe(8)
        ->and($practicesBefore)->toBe(26)
        ->and($criteriaBefore)->toBe(52);

    // Re-ejecutar el seeder
    $this->seed(CmmiCatalogSeeder::class);

    expect(PracticeArea::count())->toBe($areasBefore)
        ->and(Practice::count())->toBe($practicesBefore)
        ->and(PracticeCriterion::count())->toBe($criteriaBefore);
});

// =============================================================================
// 9. No pueden existir dos criterios con el mismo code dentro de la misma práctica
// =============================================================================
test('cannot create duplicate criterion code within the same practice due to unique constraint', function () {
    $practice = Practice::firstOrFail();
    $existingCriterion = $practice->criteria()->firstOrFail();

    expect(fn () => PracticeCriterion::create([
        'practice_id' => $practice->id,
        'code' => $existingCriterion->code,
        'description' => 'Descripción duplicada para provocar violación de unicidad.',
        'required' => true,
    ]))->toThrow(QueryException::class);
});

// =============================================================================
// 10. El mismo código de criterio puede repetirse en prácticas diferentes
// =============================================================================
test('the same criterion code can exist in different practices thanks to composite unique key', function () {
    $practice1 = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $practice2 = Practice::where('code', 'PLAN 2.1')->firstOrFail();

    $code = 'CRIT-COMMON-01';

    $criterion1 = PracticeCriterion::create([
        'practice_id' => $practice1->id,
        'code' => $code,
        'description' => 'Criterio común en práctica 1',
        'required' => true,
    ]);

    $criterion2 = PracticeCriterion::create([
        'practice_id' => $practice2->id,
        'code' => $code,
        'description' => 'Criterio con mismo código en práctica 2',
        'required' => false,
    ]);

    expect($criterion1->id)->not->toBe($criterion2->id)
        ->and($criterion1->code)->toBe($criterion2->code)
        ->and($criterion1->practice_id)->not->toBe($criterion2->practice_id);
});

// =============================================================================
// 11. Factories funcionan correctamente y crean relaciones válidas
// =============================================================================
test('catalog factories create valid instances and relations', function () {
    $area = PracticeArea::factory()->create();
    expect($area)->toBeInstanceOf(PracticeArea::class);

    $practice = Practice::factory()->create();
    expect($practice)->toBeInstanceOf(Practice::class)
        ->and($practice->practiceArea)->toBeInstanceOf(PracticeArea::class);

    $criterion = PracticeCriterion::factory()->create();
    expect($criterion)->toBeInstanceOf(PracticeCriterion::class)
        ->and($criterion->practice)->toBeInstanceOf(Practice::class)
        ->and($criterion->required)->toBeTrue();

    $optionalCriterion = PracticeCriterion::factory()->optional()->create();
    expect($optionalCriterion->required)->toBeFalse();
});
