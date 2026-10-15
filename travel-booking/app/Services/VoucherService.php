<?php

namespace App\Services;

use App\Models\Voucher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VoucherService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Voucher::withCount('usages')->latest()->paginate($perPage);
    }

    public function find(int $id): Voucher
    {
        return Voucher::withCount('usages')->findOrFail($id);
    }

    public function create(array $data): Voucher
    {
        return Voucher::create($data);
    }

    public function update(Voucher $voucher, array $data): Voucher
    {
        $voucher->update($data);
        return $voucher->fresh();
    }

    public function delete(Voucher $voucher): bool
    {
        return $voucher->delete();
    }
}
