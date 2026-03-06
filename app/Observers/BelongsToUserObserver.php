<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class BelongsToUserObserver
{
    public function creating(Model $model): void
    {
        if (! $model->user_id) {
            $model->user_id = auth()->id();
        }
    }
}
