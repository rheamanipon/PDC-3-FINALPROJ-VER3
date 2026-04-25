<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concert_seats', function (Blueprint $table) {
            $table->foreignId('concert_ticket_type_id')->nullable()->after('concert_id')->constrained('concert_ticket_types')->nullOnDelete();
        });

        $sectionMap = [
            'VIP Seated' => 'VIP Seated',
            'LBB' => 'Lower Box B (LBB)',
            'UBB' => 'Upper Box B (UBB)',
            'LBA' => 'Lower Box A (LBA)',
            'UBA' => 'Upper Box A (UBA)',
            'Gen Ad' => 'General Admission (Gen Ad)',
            'GEN AD' => 'General Admission (Gen Ad)',
        ];

        $concerts = DB::table('concerts')->get();

        foreach ($concerts as $concert) {
            $ticketTypes = DB::table('concert_ticket_types')
                ->join('ticket_types', 'concert_ticket_types.ticket_type_id', '=', 'ticket_types.id')
                ->where('concert_ticket_types.concert_id', $concert->id)
                ->select('concert_ticket_types.id as concert_ticket_type_id', 'ticket_types.name', 'concert_ticket_types.quantity')
                ->get();

            foreach ($ticketTypes as $ticketType) {
                $seatSection = $sectionMap[$ticketType->name] ?? null;
                if (!$seatSection) {
                    continue;
                }

                $seatIds = DB::table('concert_seats')
                    ->join('seats', 'concert_seats.seat_id', '=', 'seats.id')
                    ->where('concert_seats.concert_id', $concert->id)
                    ->where('seats.section', $seatSection)
                    ->orderBy('seats.seat_number')
                    ->limit($ticketType->quantity)
                    ->pluck('concert_seats.seat_id');

                if ($seatIds->isEmpty()) {
                    continue;
                }

                DB::table('concert_seats')
                    ->where('concert_id', $concert->id)
                    ->whereIn('seat_id', $seatIds)
                    ->update(['concert_ticket_type_id' => $ticketType->concert_ticket_type_id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('concert_seats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('concert_ticket_type_id');
        });
    }
};
