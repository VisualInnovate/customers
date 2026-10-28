<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use App\Models\ActionLog;

class ActionLogObserver
{
    protected function logAction(Model $model, string $action)
    {
        $userId = auth()->check() ? auth()->id() : null;
        $ip = request()->ip();

        ActionLog::create([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'action' => $action,
            'user_id' => $userId,
            'ip_address' => $ip,
        ]);
    }

    public function created(Model $model)
    {
        $this->logAction($model, 'created');
    }

    public function updated(Model $model)
    {
        $this->logAction($model, 'updated');
    }

    public function deleted(Model $model)
    {
        $this->logAction($model, 'deleted');
    }
} 