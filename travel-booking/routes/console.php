<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bookings:cancel-expired', function (\App\Services\BookingService $bookingService) {
    $this->info('Memeriksa dan membatalkan pemesanan kedaluwarsa...');
    $count = $bookingService->cancelExpiredBookings();
    $this->info("Berhasil membatalkan {$count} pemesanan yang telah melewati batas waktu pembayaran.");
})->purpose('Cancel expired pending bookings and release inventory');

Schedule::command('bookings:cancel-expired')->everyTenMinutes()->withoutOverlapping();
