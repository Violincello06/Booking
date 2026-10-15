<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\Passenger;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Room;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with complete travel booking dummy data.
     */
    public function run(): void
    {
        // -----------------------------------------------------------------
        // 1. SEED USERS (2 Admin, 3 Staff, 10 Users)
        // -----------------------------------------------------------------
        $admin1 = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@travelbooking.com',
            'phone' => '081100000001',
            'role' => 'admin',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $admin2 = User::create([
            'name' => 'Operational Admin',
            'email' => 'admin2@travelbooking.com',
            'phone' => '081100000002',
            'role' => 'admin',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $staffUsers = collect();
        foreach (range(1, 3) as $i) {
            $staffUsers->push(User::create([
                'name' => "Staff Travel {$i}",
                'email' => "staff{$i}@travelbooking.com",
                'phone' => "08120000000{$i}",
                'role' => 'staff',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
            ]));
        }

        $regularUsers = collect([
            User::create([
                'name' => 'Demo User',
                'email' => 'user@travelbooking.com',
                'phone' => '081300000001',
                'role' => 'user',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
            ]),
        ]);

        foreach (range(2, 10) as $i) {
            $regularUsers->push(User::factory()->create([
                'email' => "user{$i}@travelbooking.com",
                'role' => 'user',
            ]));
        }

        // -----------------------------------------------------------------
        // 2. SEED DESTINATIONS (10 Destinations)
        // -----------------------------------------------------------------
        $destinationsData = [
            ['name' => 'Pulau Dewata Bali', 'city' => 'Denpasar', 'province' => 'Bali', 'country' => 'Indonesia', 'description' => 'Destinasi wisata pantai dan budaya paling ikonik di Indonesia.'],
            ['name' => 'Daerah Istimewa Yogyakarta', 'city' => 'Yogyakarta', 'province' => 'DI Yogyakarta', 'country' => 'Indonesia', 'description' => 'Kota kebudayaan dengan candi megah dan kuliner istimewa.'],
            ['name' => 'Labuan Bajo', 'city' => 'Manggarai Barat', 'province' => 'Nusa Tenggara Timur', 'country' => 'Indonesia', 'description' => 'Pintu gerbang ke Taman Nasional Komodo dan keindahan pulau tropis.'],
            ['name' => 'Kepulauan Raja Ampat', 'city' => 'Waisai', 'province' => 'Papua Barat Daya', 'country' => 'Indonesia', 'description' => 'Surga bawah laut dunia dengan keanekaragaman terumbu karang tertinggi.'],
            ['name' => 'Kota Kembang Bandung', 'city' => 'Bandung', 'province' => 'Jawa Barat', 'country' => 'Indonesia', 'description' => 'Kota sejuk dengan pesona wisata belanja, kuliner, dan pegunungan.'],
            ['name' => 'Pulau Lombok', 'city' => 'Mataram', 'province' => 'Nusa Tenggara Barat', 'country' => 'Indonesia', 'description' => 'Eksotisme Gili Trawangan, Pantai Senggigi, dan Gunung Rinjani.'],
            ['name' => 'Metropolitan Jakarta', 'city' => 'Jakarta Pusat', 'province' => 'DKI Jakarta', 'country' => 'Indonesia', 'description' => 'Pusat bisnis modern, sejarah Kota Tua, dan hiburan metropolitan.'],
            ['name' => 'Kota Pahlawan Surabaya', 'city' => 'Surabaya', 'province' => 'Jawa Timur', 'country' => 'Indonesia', 'description' => 'Kota metropolis kedua Indonesia dengan sejarah perjuangan dan kuliner khas.'],
            ['name' => 'Danau Toba & Medan', 'city' => 'Medan', 'province' => 'Sumatera Utara', 'country' => 'Indonesia', 'description' => 'Pintu masuk menuju Danau Toba, danau vulkanik terbesar di dunia.'],
            ['name' => 'Makassar Anging Mammiri', 'city' => 'Makassar', 'province' => 'Sulawesi Selatan', 'country' => 'Indonesia', 'description' => 'Pusat maritim Indonesia timur dengan pesona Pantai Losari dan kuliner coto.'],
        ];

        $destinations = collect();
        foreach ($destinationsData as $dest) {
            $destinations->push(Destination::create(array_merge($dest, [
                'image' => 'https://images.unsplash.com/photo-1537996194471-e657df975ab4',
                'status' => 'active',
            ])));
        }

        // -----------------------------------------------------------------
        // 3. SEED HOTELS & ROOMS (20 Hotels, 40 Rooms)
        // -----------------------------------------------------------------
        $hotels = collect();
        $hotelNames = [
            'Ayana Resort & Spa', 'The Mulia Nusa Dua', 'The Phoenix Hotel', 'Tentrem Heritage Hotel',
            'Plataran Komodo Resort', 'Ayana Komodo Waecicu', 'Misool Eco Resort', 'Meridian Adventure Marina',
            'Padma Hotel Ciumbuleuit', 'The Papandayan Hotel', 'Katamarana Resort Lombok', 'The Oberoi Beach Resort',
            'Hotel Indonesia Kempinski', 'The Ritz-Carlton Mega Kuningan', 'Majapahit Heritage Hotel', 'Vasa Hotel',
            'Grand Mercure Maha Cipta', 'JW Marriott Hotel', 'The Rinra Waterfront Hotel', 'Claro Convention Hotel',
        ];

        $hotelIndex = 0;
        foreach ($destinations as $destination) {
            // 2 hotels per destination = 20 hotels
            for ($h = 0; $h < 2; $h++) {
                $hotel = Hotel::create([
                    'destination_id' => $destination->id,
                    'name' => $hotelNames[$hotelIndex++] ?? "Grand {$destination->city} Hotel " . ($h + 1),
                    'description' => "Hotel bintang lima premium di {$destination->city} dengan fasilitas kelas dunia.",
                    'address' => "Jl. Pariwisata Utama No. " . rand(1, 99) . ", {$destination->city}",
                    'latitude' => -6.200000 + ($hotelIndex * 0.05),
                    'longitude' => 106.816666 + ($hotelIndex * 0.05),
                    'star_rating' => rand(4, 5),
                    'contact_phone' => '021' . rand(1000000, 9999999),
                    'status' => 'active',
                ]);
                $hotels->push($hotel);

                // 2 rooms per hotel = 40 rooms
                Room::create([
                    'hotel_id' => $hotel->id,
                    'room_type' => 'Deluxe King Room',
                    'description' => 'Kamar luas dengan king size bed, view pemandangan kota/alam, dan sarapan buffet.',
                    'capacity' => 2,
                    'price_per_night' => 850000,
                    'total_rooms' => 15,
                    'available_rooms' => 15,
                    'facilities' => ['Free High-Speed WiFi', 'Breakfast Included', 'Air Conditioning', 'Flat Screen TV', 'Safe Deposit Box'],
                    'image' => 'https://images.unsplash.com/photo-1591088398332-8a7791972843',
                    'status' => 'available',
                ]);

                Room::create([
                    'hotel_id' => $hotel->id,
                    'room_type' => 'Executive Suite with Balcony',
                    'description' => 'Suite mewah dengan balkon pribadi, ruang tamu terpisah, bathtub jacuzzi, dan lounge access.',
                    'capacity' => 3,
                    'price_per_night' => 1750000,
                    'total_rooms' => 8,
                    'available_rooms' => 8,
                    'facilities' => ['Free High-Speed WiFi', 'Club Lounge Access', 'Jacuzzi Bathtub', 'Coffee Machine', 'Mini Bar'],
                    'image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b',
                    'status' => 'available',
                ]);
            }
        }

        // -----------------------------------------------------------------
        // 4. SEED AIRLINES (5 Airlines)
        // -----------------------------------------------------------------
        $airlinesData = [
            ['name' => 'Garuda Indonesia', 'code' => 'GA', 'logo' => 'https://logo.clearbit.com/garuda-indonesia.com'],
            ['name' => 'Lion Air', 'code' => 'JT', 'logo' => 'https://logo.clearbit.com/lionair.co.id'],
            ['name' => 'Citilink Indonesia', 'code' => 'QG', 'logo' => 'https://logo.clearbit.com/citilink.co.id'],
            ['name' => 'Batik Air', 'code' => 'ID', 'logo' => 'https://logo.clearbit.com/batikair.com'],
            ['name' => 'AirAsia Indonesia', 'code' => 'QZ', 'logo' => 'https://logo.clearbit.com/airasia.com'],
        ];

        $airlines = collect();
        foreach ($airlinesData as $airline) {
            $airlines->push(Airline::create(array_merge($airline, ['status' => 'active'])));
        }

        // -----------------------------------------------------------------
        // 5. SEED AIRPORTS (10 Airports)
        // -----------------------------------------------------------------
        $airportsData = [
            ['name' => 'Soekarno-Hatta International Airport', 'code' => 'CGK', 'city' => 'Jakarta', 'country' => 'Indonesia'],
            ['name' => 'I Gusti Ngurah Rai International Airport', 'code' => 'DPS', 'city' => 'Denpasar Bali', 'country' => 'Indonesia'],
            ['name' => 'Juanda International Airport', 'code' => 'SUB', 'city' => 'Surabaya', 'country' => 'Indonesia'],
            ['name' => 'Yogyakarta International Airport', 'code' => 'YIA', 'city' => 'Yogyakarta', 'country' => 'Indonesia'],
            ['name' => 'Kualanamu International Airport', 'code' => 'KNO', 'city' => 'Medan', 'country' => 'Indonesia'],
            ['name' => 'Sultan Hasanuddin International Airport', 'code' => 'UPG', 'city' => 'Makassar', 'country' => 'Indonesia'],
            ['name' => 'Komodo Airport', 'code' => 'LBJ', 'city' => 'Labuan Bajo', 'country' => 'Indonesia'],
            ['name' => 'Lombok International Airport', 'code' => 'LOP', 'city' => 'Lombok', 'country' => 'Indonesia'],
            ['name' => 'Husein Sastranegara Airport', 'code' => 'BDO', 'city' => 'Bandung', 'country' => 'Indonesia'],
            ['name' => 'Domine Eduard Osok Airport', 'code' => 'SOQ', 'city' => 'Sorong (Raja Ampat)', 'country' => 'Indonesia'],
        ];

        $airports = collect();
        foreach ($airportsData as $airport) {
            $airports->push(Airport::create($airport));
        }

        // -----------------------------------------------------------------
        // 6. SEED FLIGHTS (30 Flights)
        // -----------------------------------------------------------------
        $flights = collect();
        $flightNumber = 101;
        for ($i = 0; $i < 30; $i++) {
            $airline = $airlines->random();
            $depAirport = $airports->random();
            $arrAirport = $airports->where('id', '!=', $depAirport->id)->random();

            $depTime = now()->addDays(rand(1, 14))->setHour(rand(6, 21))->setMinute(rand(0, 59));
            $duration = rand(60, 210); // 1 jam - 3.5 jam
            $arrTime = (clone $depTime)->addMinutes($duration);

            $flights->push(Flight::create([
                'airline_id' => $airline->id,
                'departure_airport_id' => $depAirport->id,
                'arrival_airport_id' => $arrAirport->id,
                'flight_number' => "{$airline->code}-{$flightNumber}",
                'departure_time' => $depTime,
                'arrival_time' => $arrTime,
                'duration' => $duration,
                'price' => rand(75, 250) * 10000, // 750.000 - 2.500.000
                'total_seats' => 160,
                'available_seats' => 160,
                'status' => 'scheduled',
            ]));
            $flightNumber++;
        }

        // -----------------------------------------------------------------
        // 7. SEED VOUCHERS (10 Vouchers)
        // -----------------------------------------------------------------
        $vouchersData = [
            ['code' => 'WELCOME2026', 'name' => 'Diskon Pengguna Baru 20%', 'discount_type' => 'percentage', 'discount_value' => 20, 'minimum_transaction' => 300000, 'maximum_discount' => 200000],
            ['code' => 'FLYNOW50', 'name' => 'Potongan Tiket Pesawat Rp 50.000', 'discount_type' => 'fixed', 'discount_value' => 50000, 'minimum_transaction' => 500000, 'maximum_discount' => null],
            ['code' => 'HOTELHEMAT', 'name' => 'Diskon Hotel Liburan 15%', 'discount_type' => 'percentage', 'discount_value' => 15, 'minimum_transaction' => 600000, 'maximum_discount' => 300000],
            ['code' => 'STAYCATION100', 'name' => 'Potongan Staycation Rp 100.000', 'discount_type' => 'fixed', 'discount_value' => 100000, 'minimum_transaction' => 1000000, 'maximum_discount' => null],
            ['code' => 'EXPLOREBALI', 'name' => 'Diskon Wisata Bali 10%', 'discount_type' => 'percentage', 'discount_value' => 10, 'minimum_transaction' => 400000, 'maximum_discount' => 150000],
            ['code' => 'WEEKENDVIBES', 'name' => 'Potongan Akhir Pekan Rp 75.000', 'discount_type' => 'fixed', 'discount_value' => 75000, 'minimum_transaction' => 750000, 'maximum_discount' => null],
            ['code' => 'FAMILYTRIP', 'name' => 'Diskon Booking Keluarga 25%', 'discount_type' => 'percentage', 'discount_value' => 25, 'minimum_transaction' => 1500000, 'maximum_discount' => 500000],
            ['code' => 'SUPERFLASH', 'name' => 'Flash Sale Diskon Rp 150.000', 'discount_type' => 'fixed', 'discount_value' => 150000, 'minimum_transaction' => 1200000, 'maximum_discount' => null],
            ['code' => 'AIRPORTDEAL', 'name' => 'Diskon Penerbangan Domestik 12%', 'discount_type' => 'percentage', 'discount_value' => 12, 'minimum_transaction' => 800000, 'maximum_discount' => 250000],
            ['code' => 'VIPMEMBERS', 'name' => 'Voucher Khusus Member VIP 30%', 'discount_type' => 'percentage', 'discount_value' => 30, 'minimum_transaction' => 2000000, 'maximum_discount' => 1000000],
        ];

        foreach ($vouchersData as $v) {
            Voucher::create(array_merge($v, [
                'quota' => 200,
                'used_quota' => 0,
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(60),
                'status' => 'active',
            ]));
        }

        // -----------------------------------------------------------------
        // 8. SAMPLE BOOKINGS & PAYMENTS
        // -----------------------------------------------------------------
        $sampleUser = $regularUsers->first();
        $sampleRoom = Room::first();

        // Sample Hotel Booking
        $hotelBookingRecord = Booking::create([
            'user_id' => $sampleUser->id,
            'booking_code' => 'HTL-' . now()->format('Ymd') . '-SAMPLE',
            'booking_type' => 'hotel',
            'booking_date' => now(),
            'total_amount' => 1700000,
            'status' => 'confirmed',
            'expired_at' => now()->addHours(2),
        ]);

        HotelBooking::create([
            'booking_id' => $hotelBookingRecord->id,
            'room_id' => $sampleRoom->id,
            'check_in' => now()->addDays(3)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'guest_count' => 2,
            'room_count' => 1,
            'price' => 850000,
            'subtotal' => 1700000,
        ]);

        Passenger::create([
            'booking_id' => $hotelBookingRecord->id,
            'full_name' => 'John Doe',
            'identity_number' => '3171000000000001',
            'date_of_birth' => '1995-05-15',
            'gender' => 'male',
            'nationality' => 'Indonesia',
        ]);

        Payment::create([
            'booking_id' => $hotelBookingRecord->id,
            'payment_code' => 'PAY-HTL-SAMPLE',
            'payment_method' => 'bank_transfer',
            'amount' => 1700000,
            'paid_at' => now(),
            'status' => 'paid',
            'transaction_reference' => 'TX-MOCK-SAMPLE001',
        ]);

        // Sample Flight Booking
        $sampleFlight = $flights->first();
        $flightBookingRecord = Booking::create([
            'user_id' => $sampleUser->id,
            'booking_code' => 'FLT-' . now()->format('Ymd') . '-SAMPLE',
            'booking_type' => 'flight',
            'booking_date' => now(),
            'total_amount' => $sampleFlight->price * 2,
            'status' => 'confirmed',
            'expired_at' => now()->addHours(2),
        ]);

        FlightBooking::create([
            'booking_id' => $flightBookingRecord->id,
            'flight_id' => $sampleFlight->id,
            'passenger_count' => 2,
            'price' => $sampleFlight->price,
            'subtotal' => $sampleFlight->price * 2,
        ]);

        Passenger::create([
            'booking_id' => $flightBookingRecord->id,
            'full_name' => 'John Doe',
            'identity_number' => '3171000000000001',
            'date_of_birth' => '1995-05-15',
            'gender' => 'male',
            'nationality' => 'Indonesia',
        ]);

        Passenger::create([
            'booking_id' => $flightBookingRecord->id,
            'full_name' => 'Jane Doe',
            'identity_number' => '3171000000000002',
            'date_of_birth' => '1997-08-20',
            'gender' => 'female',
            'nationality' => 'Indonesia',
        ]);

        Payment::create([
            'booking_id' => $flightBookingRecord->id,
            'payment_code' => 'PAY-FLT-SAMPLE',
            'payment_method' => 'credit_card',
            'amount' => $sampleFlight->price * 2,
            'paid_at' => now(),
            'status' => 'paid',
            'transaction_reference' => 'TX-MOCK-SAMPLE002',
        ]);

        // -----------------------------------------------------------------
        // 9. SEED REVIEWS (20 Reviews)
        // -----------------------------------------------------------------
        $reviewComments = [
            'Pengalaman menginap yang luar biasa! Pelayanan staf sangat ramah dan kamar sangat bersih.',
            'Lokasi sangat strategis dekat dengan berbagai objek wisata. Sarapannya sangat lezat.',
            'Fasilitas kolam renang dan gym sangat terawat. Pasti akan kembali menginap di sini.',
            'Kamar yang nyaman dengan tempat tidur empuk, pemandangan luar biasa dari balkon.',
            'Proses check-in dan check-out sangat cepat. Sangat direkomendasikan untuk liburan keluarga.',
        ];

        for ($r = 0; $r < 20; $r++) {
            Review::create([
                'user_id' => $regularUsers->random()->id,
                'hotel_id' => $hotels->random()->id,
                'booking_id' => $hotelBookingRecord->id,
                'rating' => rand(4, 5),
                'comment' => $reviewComments[$r % count($reviewComments)],
                'status' => 'published',
            ]);
        }
    }
}
