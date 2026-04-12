<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConcertSeat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'concert_id',
        'seat_id',
        'status',
    ];

    public function concert()
    {
        return $this->belongsTo(Concert::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }
}
