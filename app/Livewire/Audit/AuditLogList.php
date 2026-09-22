<?php

namespace App\Livewire\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogList extends Component
{
    use AuthorizesRequests, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $userFilter = '';

    public string $entityFilter = '';

    public string $actionFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $search = '';

    public ?int $expandedId = null;

    public function mount(): void
    {
        $this->authorize('ver-bitacora');
    }

    public function updating(string $property): void
    {
        if ($property !== 'expandedId') {
            $this->resetPage();
        }
    }

    public function toggle(int $auditLogId): void
    {
        $this->expandedId = $this->expandedId === $auditLogId ? null : $auditLogId;
    }

    public function resetFilters(): void
    {
        $this->reset(['userFilter', 'entityFilter', 'actionFilter', 'dateFrom', 'dateTo', 'search', 'expandedId']);
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('ver-bitacora');

        $logs = AuditLog::query()
            ->with('user')
            ->when($this->userFilter === 'sistema', fn ($query) => $query->whereNull('user_id'))
            ->when(is_numeric($this->userFilter), fn ($query) => $query->where('user_id', (int) $this->userFilter))
            ->when($this->entityFilter !== '', fn ($query) => $query->where('auditable_type', $this->entityFilter))
            ->when($this->actionFilter !== '', fn ($query) => $query->where('action', $this->actionFilter))
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->search !== '', fn ($query) => $query->where('entity', 'like', '%'.$this->search.'%'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.audit.audit-log-list', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'entities' => AuditLog::AUDITED_MODELS,
            'actions' => AuditLog::actions(),
        ])->layout('layouts.app', ['header' => 'Bitácora de Auditoría']);
    }
}
