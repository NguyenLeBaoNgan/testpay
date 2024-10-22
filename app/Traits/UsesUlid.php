<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait UsesUlid
{

    public $incrementing = false;

   
    protected $keyType = 'string';

    protected static function bootUsesUlid()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::ulid();
            }
        });
    }
}
