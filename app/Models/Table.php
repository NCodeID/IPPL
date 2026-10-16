<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['number', 'is_active'])]
class Table extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
