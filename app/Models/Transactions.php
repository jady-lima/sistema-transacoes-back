<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Accounts;

class Transactions extends Model
{
    protected $fillable = [
        'account_id',
        'type',
        'amount',
    ];

    public function account()
    {
        return $this->belongsTo(Accounts::class);
    }
}
