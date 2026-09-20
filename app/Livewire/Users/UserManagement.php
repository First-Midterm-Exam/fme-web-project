<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'Contributor';

    public bool $is_active = true;

    public bool $showModal = false;

    public bool $isEditing = false;

    public string $search = '';

    /**
     * @var array<string, array{except: string}>
     */
    protected $queryString = ['search' => ['except' => '']];

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', User::class);

        $this->resetInputFields();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $firstRole = $user->roles->first();
        $this->role = $firstRole instanceof Role ? $firstRole->name : 'Contributor';
        $this->is_active = $user->is_active;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetInputFields();
        $this->resetValidation();
    }

    public function save(): void
    {
        if ($this->isEditing && $this->userId) {
            $user = User::findOrFail($this->userId);
            $this->authorize('update', $user);

            $validated = $this->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'password' => 'nullable|string|min:8',
                'role' => 'required|string|exists:roles,name',
                'is_active' => 'boolean',
            ], $this->messages());

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->is_active = (bool) $validated['is_active'];

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
            $user->syncRoles([$validated['role']]);

            session()->flash('message', 'Usuario actualizado exitosamente.');
        } else {
            $this->authorize('create', User::class);

            $validated = $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|string|exists:roles,name',
                'is_active' => 'boolean',
            ], $this->messages());

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => (bool) $validated['is_active'],
            ]);

            $user->assignRole($validated['role']);

            session()->flash('message', 'Usuario creado exitosamente con el rol asignado.');
        }

        $this->closeModal();
    }

    public function toggleStatus(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'activado' : 'dado de baja';
        session()->flash('message', "El usuario {$user->name} ha sido {$statusText}.");
    }

    public function render(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        $roles = Role::pluck('name');

        return view('livewire.users.user-management', [
            'users' => $users,
            'roles' => $roles,
        ])->layout('layouts.app');
    }

    private function resetInputFields(): void
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'Contributor';
        $this->is_active = true;
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.unique' => 'El correo electrónico ya se encuentra registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'role.required' => 'Debe seleccionar un rol.',
            'role.exists' => 'El rol seleccionado no es válido.',
        ];
    }
}
