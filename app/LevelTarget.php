<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LevelTarget extends Model
{
    // jika Anda tidak punya folder Models, pindahkan namespace ke App\
    protected $table = 'level_targets';

    protected $fillable = [
        'level',
        'amount',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    /**
     * Scope: hanya yang berlaku pada tanggal tertentu (default today)
     */
    public function scopeActiveOn($query, $date = null)
    {
        $date = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        return $query->where(function($q) use ($date) {
            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date);
        })->where(function($q) use ($date) {
            $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
        })->orderByDesc('valid_from');
    }

    /**
     * Ambil LevelTarget yang berlaku untuk sebuah level pada tanggal tertentu.
     * Mengembalikan Model atau null.
     *
     * Contoh: LevelTarget::forLevel('Junior');
     */
    public static function forLevel(string $level, $date = null)
    {
        $date = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        return static::where('level', $level)
            ->where(function($q) use ($date) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date);
            })
            ->where(function($q) use ($date) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
            })
            ->orderByDesc('valid_from')
            ->first();
    }

    /**
     * Ambil nilai amount default untuk level (float atau null)
     */
    public static function amountForLevel(string $level, $date = null): ?float
    {
        $row = static::forLevel($level, $date);
        return $row ? (float) $row->amount : null;
    }
}
