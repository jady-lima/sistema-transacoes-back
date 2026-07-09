<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\User;
use App\Models\Accounts;

class Client extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'cpf',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accounts()
    {
        return $this->hasOne(Accounts::class);
    }
}
