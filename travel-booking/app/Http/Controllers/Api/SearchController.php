<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchFlightRequest;
use App\Http\Requests\SearchHotelRequest;
use App\Http\Resources\FlightResource;
use App\Http\Resources\HotelResource;
use App\Services\FlightService;
use App\Services\HotelService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        protected HotelService $hotelService,
        protected FlightService $flightService
    ) {}

    public function hotels(SearchHotelRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->hotelService->search($request->validated(), $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Hasil pencarian hotel berhasil ditemukan',
            'data' => HotelResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters_applied' => array_filter($request->validated(), fn ($v) => $v !== null && $v !== ''),
        ], 200);
    }

    public function flights(SearchFlightRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->flightService->search($request->validated(), $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Hasil pencarian penerbangan berhasil ditemukan',
            'data' => FlightResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters_applied' => array_filter($request->validated(), fn ($v) => $v !== null && $v !== ''),
        ], 200);
    }
}
