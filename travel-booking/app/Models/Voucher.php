<?php

namespace App\Models;

use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'discount_type',
        'discount_value',
        'minimum_transaction',
        'maximum_discount',
        'quota',
        'used_quota',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_transaction' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'quota' => 'integer',
            'used_quota' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    /**
     * Usages of this voucher.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }
}
