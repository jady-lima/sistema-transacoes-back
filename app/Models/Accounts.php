<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Client;
use App\Models\Transactions;

class Accounts extends Model
{
    protected $fillable = [
        'client_id',
        'number',
        'agency',
        'balance',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transactions::class);
    }
}
