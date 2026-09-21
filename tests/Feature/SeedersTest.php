<?php

use App\Models\Appraisal;
use App\Models\AppraisalScope;
use App\Models\Practice;
use App\Models\PracticeCriterion;
use App\Models\Project;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('the full database seeder runs without errors', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Rol::count())->toBe(4)
        ->and(User::count())->toBeGreaterThanOrEqual(4)
        ->and(Practice::count())->toBeGreaterThan(0)
        ->and(PracticeCriterion::count())->toBeGreaterThan(0)
        ->and(Project::count())->toBeGreaterThan(0)
        ->and(Appraisal::count())->toBeGreaterThan(0);
});

test('the seeded appraisal scope resolves the practice area of every practice', function () {
    $this->seed(DatabaseSeeder::class);

    $scopes = AppraisalScope::with('practice')->get();

    expect($scopes)->not->toBeEmpty();

    foreach ($scopes as $scope) {
        expect($scope->practice_area_id)->toBe($scope->practice->practice_area_id)
            ->and($scope->incluida)->toBeTrue();
    }
});

test('every seeded user has exactly one role', function () {
    $this->seed(DatabaseSeeder::class);

    foreach (User::with('roles')->get() as $user) {
        expect($user->roles)->toHaveCount(1);
    }
});
