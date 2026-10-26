<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Destination;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_hotels_by_keyword_and_city(): void
    {
        $destBali = Destination::factory()->create(['name' => 'Bali', 'city' => 'Denpasar']);
        $destJogja = Destination::factory()->create(['name' => 'Yogyakarta', 'city' => 'Yogyakarta']);

        $hotel1 = Hotel::factory()->create([
            'destination_id' => $destBali->id,
            'name' => 'Amnaya Resort Kuta',
            'address' => 'Jl. Kartika Plaza, Kuta',
        ]);
        $hotel2 = Hotel::factory()->create([
            'destination_id' => $destJogja->id,
            'name' => 'The Phoenix Hotel',
            'address' => 'Jl. Jenderal Sudirman No. 9',
        ]);

        // Search by keyword "Amnaya"
        $this->getJson('/api/v1/search/hotels?query=Amnaya')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Amnaya Resort Kuta');

        // Search by city "Yogyakarta"
        $this->getJson('/api/v1/search/hotels?city=Yogyakarta')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'The Phoenix Hotel');
    }

    public function test_can_filter_hotels_by_star_rating_and_price_range(): void
    {
        $destination = Destination::factory()->create();

        $budgetHotel = Hotel::factory()->create([
            'destination_id' => $destination->id,
            'name' => 'Budget Inn',
            'star_rating' => 2,
        ]);
        Room::factory()->create([
            'hotel_id' => $budgetHotel->id,
            'price_per_night' => 300000,
        ]);

        $luxuryHotel = Hotel::factory()->create([
            'destination_id' => $destination->id,
            'name' => 'Luxury Palace',
            'star_rating' => 5,
        ]);
        Room::factory()->create([
            'hotel_id' => $luxuryHotel->id,
            'price_per_night' => 2500000,
        ]);

        // Filter by 5 star rating
        $this->getJson('/api/v1/search/hotels?star_rating=5')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Luxury Palace');

        // Filter by price range <= 500.000
        $this->getJson('/api/v1/search/hotels?max_price=500000')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Budget Inn');
    }

    public function test_can_sort_and_paginate_hotels(): void
    {
        $destination = Destination::factory()->create();

        $hotelA = Hotel::factory()->create([
            'destination_id' => $destination->id,
            'name' => 'Alpha Hotel',
            'star_rating' => 3,
        ]);
        Room::factory()->create(['hotel_id' => $hotelA->id, 'price_per_night' => 800000]);

        $hotelB = Hotel::factory()->create([
            'destination_id' => $destination->id,
            'name' => 'Beta Hotel',
            'star_rating' => 4,
        ]);
        Room::factory()->create(['hotel_id' => $hotelB->id, 'price_per_night' => 400000]);

        // Sort by price ascending (cheapest first)
        $this->getJson('/api/v1/search/hotels?sort_by=price_asc')
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Beta Hotel')
            ->assertJsonPath('data.1.name', 'Alpha Hotel');

        // Sort by price descending (most expensive first)
        $this->getJson('/api/v1/search/hotels?sort_by=price_desc')
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Alpha Hotel')
            ->assertJsonPath('data.1.name', 'Beta Hotel');

        // Pagination test (per_page = 1)
        $this->getJson('/api/v1/search/hotels?per_page=1&page=1')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_can_search_flights_by_origin_and_destination_airports(): void
    {
        $airline = Airline::factory()->create(['name' => 'Garuda Indonesia', 'code' => 'GA']);
        $cgk = Airport::factory()->create(['code' => 'CGK', 'city' => 'Jakarta']);
        $dps = Airport::factory()->create(['code' => 'DPS', 'city' => 'Denpasar']);
        $sub = Airport::factory()->create(['code' => 'SUB', 'city' => 'Surabaya']);

        $flightCGKDPS = Flight::factory()->create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $cgk->id,
            'arrival_airport_id' => $dps->id,
            'flight_number' => 'GA-401',
            'departure_time' => '2026-10-25 08:00:00',
            'price' => 1500000,
        ]);

        $flightCGKSUB = Flight::factory()->create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $cgk->id,
            'arrival_airport_id' => $sub->id,
            'flight_number' => 'GA-302',
            'departure_time' => '2026-10-25 10:00:00',
            'price' => 900000,
        ]);

        // Search by airport codes CGK -> DPS
        $this->getJson('/api/v1/search/flights?departure_airport_code=CGK&arrival_airport_code=DPS')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.flight_number', 'GA-401');

        // Search by departure date 2026-10-25
        $this->getJson('/api/v1/search/flights?departure_date=2026-10-25')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_flights_by_price_airline_and_seats(): void
    {
        $airlineGA = Airline::factory()->create(['code' => 'GA']);
        $airlineLion = Airline::factory()->create(['code' => 'JT']);
        $cgk = Airport::factory()->create();
        $dps = Airport::factory()->create();

        $cheapFlight = Flight::factory()->create([
            'airline_id' => $airlineLion->id,
            'departure_airport_id' => $cgk->id,
            'arrival_airport_id' => $dps->id,
            'price' => 750000,
            'available_seats' => 50,
        ]);

        $expensiveFlight = Flight::factory()->create([
            'airline_id' => $airlineGA->id,
            'departure_airport_id' => $cgk->id,
            'arrival_airport_id' => $dps->id,
            'price' => 1800000,
            'available_seats' => 5,
        ]);

        // Filter by airline ID
        $this->getJson("/api/v1/search/flights?airline_id={$airlineGA->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.airline.code', 'GA');

        // Filter by max price 1.000.000
        $this->getJson('/api/v1/search/flights?max_price=1000000')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price', 750000);

        // Filter by minimum seats 10 (should exclude expensive flight with only 5 seats)
        $this->getJson('/api/v1/search/flights?min_seats=10')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cheapFlight->id);
    }

    public function test_can_sort_flights_by_price_and_duration(): void
    {
        $airline = Airline::factory()->create();
        $cgk = Airport::factory()->create();
        $dps = Airport::factory()->create();

        Flight::factory()->create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $cgk->id,
            'arrival_airport_id' => $dps->id,
            'price' => 1200000,
            'duration' => 110,
        ]);

        Flight::factory()->create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $cgk->id,
            'arrival_airport_id' => $dps->id,
            'price' => 850000,
            'duration' => 140,
        ]);

        // Sort by price ascending
        $resPriceAsc = $this->getJson('/api/v1/search/flights?sort_by=price_asc')
            ->assertStatus(200);
        $this->assertEquals(850000, $resPriceAsc->json('data.0.price'));
        $this->assertEquals(1200000, $resPriceAsc->json('data.1.price'));

        // Sort by duration ascending
        $resDurationAsc = $this->getJson('/api/v1/search/flights?sort_by=duration_asc')
            ->assertStatus(200);
        $this->assertEquals(110, $resDurationAsc->json('data.0.duration'));
        $this->assertEquals(140, $resDurationAsc->json('data.1.duration'));
    }

    public function test_search_validates_invalid_inputs(): void
    {
        // Airport origin and destination cannot be identical
        $airport = Airport::factory()->create(['code' => 'CGK']);

        $this->getJson("/api/v1/search/flights?departure_airport_id={$airport->id}&arrival_airport_id={$airport->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['arrival_airport_id']);

        // Invalid star rating (out of range 1-5)
        $this->getJson('/api/v1/search/hotels?star_rating=10')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['star_rating']);
    }
}
