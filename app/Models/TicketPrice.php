<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketPrice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'concert_id',
        'section',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function concert()
    {
        return $this->belongsTo(Concert::class);
    }
}
