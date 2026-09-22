<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditableObserver
{
    public function __construct(private readonly AuditLogger $logger) {}

    public function created(Model $model): void
    {
        $this->logger->record(AuditLog::ACTION_CREATED, $model, null, $this->auditable($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $newValues = $this->auditable($model->getChanges());

        if ($newValues === []) {
            return;
        }

        $oldValues = [];

        foreach (array_keys($newValues) as $attribute) {
            $oldValues[$attribute] = $model->getRawOriginal($attribute);
        }

        $this->logger->record(AuditLog::ACTION_UPDATED, $model, $oldValues, $newValues);
    }

    public function deleted(Model $model): void
    {
        $this->logger->record(AuditLog::ACTION_DELETED, $model, $this->auditable($model->getAttributes()), null);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function auditable(array $attributes): array
    {
        return Arr::except($attributes, AuditLog::IGNORED_ATTRIBUTES);
    }
}
