<?php

use App\Livewire\Users\UserManagement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('non admin user cannot access user management page', function () {
    $user = User::factory()->create();
    $user->assignRole('Colaborador');

    $this->actingAs($user);

    $response = $this->get('/users');

    $response->assertStatus(403);
});

test('administrator can access user management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    $this->actingAs($admin);

    $response = $this->get('/users');

    $response->assertStatus(200);
});

test('administrator can create a new user with assigned role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->call('openCreateModal')
        ->set('name', 'Nuevo Jefe de Proyecto')
        ->set('email', 'pm@dima.cl')
        ->set('password', 'password123')
        ->set('role', 'Jefe de Proyecto')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'email' => 'pm@dima.cl',
        'name' => 'Nuevo Jefe de Proyecto',
        'is_active' => true,
    ]);

    $newUser = User::where('email', 'pm@dima.cl')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->hasRole('Jefe de Proyecto'))->toBeTrue();
});

test('user creation fails when email is duplicate', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    User::factory()->create(['email' => 'existente@dima.cl']);

    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->call('openCreateModal')
        ->set('name', 'Usuario Duplicado')
        ->set('email', 'existente@dima.cl')
        ->set('password', 'password123')
        ->set('role', 'Colaborador')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('administrator can deactivate user without deleting physically from database', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    $targetUser = User::factory()->create([
        'is_active' => true,
    ]);
    $targetUser->assignRole('Colaborador');

    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->call('toggleStatus', $targetUser->id);

    $targetUser->refresh();
    expect($targetUser->is_active)->toBeFalse();

    $this->assertDatabaseHas('users', [
        'id' => $targetUser->id,
        'is_active' => false,
    ]);
});
