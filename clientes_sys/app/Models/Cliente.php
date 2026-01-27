<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'documento_path' => 'array',
        'foto_path' => 'array',
        'correntista' => 'boolean',
        'simulacao' => 'boolean',
        'proposta_enviada' => 'boolean',
        'aprovada' => 'boolean',
    ];
}
