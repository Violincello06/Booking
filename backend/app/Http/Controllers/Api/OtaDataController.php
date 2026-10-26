<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class OtaDataController extends Controller
{
    private function getDbPath(): string
    {
        return base_path('data/db.json');
    }

    private function readDB(): array
    {
        $path = $this->getDbPath();
        if (!File::exists($path)) {
            return [
                'users' => [],
                'destinations' => [],
                'hotels' => [],
                'flights' => [],
                'blogs' => []
            ];
        }

        $content = File::get($path);
        $data = json_decode($content, true);
        if (!is_array($data)) {
            $data = [];
        }
        if (!isset($data['blogs'])) {
            $data['blogs'] = [];
        }
        return $data;
    }

    private function writeDB(array $data): bool
    {
        $path = $this->getDbPath();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return File::put($path, $json) !== false;
    }

    public function login(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');

        if (!$email || !$password) {
            return response()->json(['success' => false, 'message' => 'Email dan password wajib diisi.'], 400);
        }

        $db = $this->readDB();
        $user = null;
        foreach ($db['users'] as $u) {
            if (strtolower(trim($u['email'])) === $email && (string) $u['password'] === $password) {
                $user = $u;
                break;
            }
        }

        if (!$user) {
            // Master fallback login for admin
            if ($email === 'admin@violincello.com' && $password === 'admin123') {
                $user = [
                    'id' => 1,
                    'name' => 'Administrator OTA',
                    'email' => 'admin@violincello.com',
                    'role' => 'admin',
                    'phone' => '+6281234567890'
                ];
            } else {
                return response()->json(['success' => false, 'message' => 'Kombinasi email atau kata sandi tidak cocok.'], 401);
            }
        }

        $token = base64_encode(json_encode([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'expiresAt' => (time() + 365 * 24 * 3600) * 1000
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Autentikasi berhasil.',
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'phone' => $user['phone'] ?? ''
                ],
                'token' => $token
            ]
        ]);
    }

    public function dashboard()
    {
        $db = $this->readDB();
        return response()->json([
            'success' => true,
            'data' => [
                'totalDestinations' => count($db['destinations'] ?? []),
                'totalHotels' => count($db['hotels'] ?? []),
                'totalFlights' => count($db['flights'] ?? []),
                'totalBlogs' => count($db['blogs'] ?? []),
                'totalUsers' => count($db['users'] ?? []),
            ]
        ]);
    }

    public function getDestinations()
    {
        $db = $this->readDB();
        return response()->json(['success' => true, 'data' => $db['destinations'] ?? []]);
    }

    public function storeDestination(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        $city = trim((string) $request->input('city', ''));

        if (!$name || !$city) {
            return response()->json(['success' => false, 'message' => 'Nama destinasi dan kota wajib diisi.'], 422);
        }

        $db = $this->readDB();
        $destinations = $db['destinations'] ?? [];
        $ids = array_column($destinations, 'id');
        $newId = count($ids) > 0 ? max($ids) + 1 : 1;

        $item = [
            'id' => $newId,
            'name' => $name,
            'city' => $city,
            'country' => $request->input('country', 'Indonesia'),
            'image' => $request->input('image', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80'),
            'description' => (string) $request->input('description', ''),
            'rating' => (float) $request->input('rating', 4.8),
            'badge' => (string) $request->input('badge', 'Wisata Unggulan')
        ];

        array_unshift($destinations, $item);
        $db['destinations'] = $destinations;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Destinasi wisata berhasil disimpan.', 'data' => $item]);
    }

    public function updateDestination(Request $request, $id)
    {
        $id = (int) $id;
        $db = $this->readDB();
        $destinations = $db['destinations'] ?? [];

        $idx = -1;
        foreach ($destinations as $i => $d) {
            if ((int) $d['id'] === $id) {
                $idx = $i;
                break;
            }
        }

        if ($idx === -1) {
            return response()->json(['success' => false, 'message' => 'Destinasi tidak ditemukan.'], 404);
        }

        $destinations[$idx] = array_merge($destinations[$idx], $request->all(), ['id' => $id]);
        $db['destinations'] = $destinations;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Data destinasi berhasil diperbarui.', 'data' => $destinations[$idx]]);
    }

    public function deleteDestination($id)
    {
        $id = (int) $id;
        $db = $this->readDB();
        $destinations = array_values(array_filter($db['destinations'] ?? [], fn($d) => (int) $d['id'] !== $id));
        $db['destinations'] = $destinations;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Destinasi berhasil dihapus.']);
    }

    public function getHotels()
    {
        $db = $this->readDB();
        return response()->json(['success' => true, 'data' => $db['hotels'] ?? []]);
    }

    public function storeHotel(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        $city = trim((string) $request->input('city', ''));

        if (!$name || !$city) {
            return response()->json(['success' => false, 'message' => 'Nama dan kota hotel wajib diisi.'], 422);
        }

        $db = $this->readDB();
        $hotels = $db['hotels'] ?? [];
        $ids = array_column($hotels, 'id');
        $newId = count($ids) > 0 ? max($ids) + 1 : 1;

        $item = [
            'id' => $newId,
            'name' => $name,
            'city' => $city,
            'stars' => (int) $request->input('stars', 5),
            'price' => (float) $request->input('price', 1000000),
            'availableRooms' => (int) $request->input('availableRooms', 10),
            'image' => $request->input('image', 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80')
        ];

        array_unshift($hotels, $item);
        $db['hotels'] = $hotels;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Data hotel berhasil disimpan.', 'data' => $item]);
    }

    public function updateHotel(Request $request, $id)
    {
        $id = (int) $id;
        $db = $this->readDB();
        $hotels = $db['hotels'] ?? [];

        $idx = -1;
        foreach ($hotels as $i => $h) {
            if ((int) $h['id'] === $id) {
                $idx = $i;
                break;
            }
        }

        if ($idx === -1) {
            return response()->json(['success' => false, 'message' => 'Hotel tidak ditemukan.'], 404);
        }

        $hotels[$idx] = array_merge($hotels[$idx], $request->all(), ['id' => $id]);
        $db['hotels'] = $hotels;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Data hotel berhasil diperbarui.', 'data' => $hotels[$idx]]);
    }

    public function deleteHotel($id)
    {
        $id = (int) $id;
        $db = $this->readDB();
        $hotels = array_values(array_filter($db['hotels'] ?? [], fn($h) => (int) $h['id'] !== $id));
        $db['hotels'] = $hotels;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Hotel berhasil dihapus.']);
    }

    public function getFlights()
    {
        $db = $this->readDB();
        return response()->json(['success' => true, 'data' => $db['flights'] ?? []]);
    }

    public function storeFlight(Request $request)
    {
        $airline = trim((string) $request->input('airline', ''));
        $flightNumber = trim((string) $request->input('flightNumber', ''));

        if (!$airline || !$flightNumber) {
            return response()->json(['success' => false, 'message' => 'Maskapai dan nomor penerbangan wajib diisi.'], 422);
        }

        $db = $this->readDB();
        $flights = $db['flights'] ?? [];
        $ids = array_column($flights, 'id');
        $newId = count($ids) > 0 ? max($ids) + 1 : 1;

        $item = [
            'id' => $newId,
            'airline' => $airline,
            'flightNumber' => $flightNumber,
            'originCode' => (string) $request->input('originCode', 'CGK'),
            'originCity' => (string) $request->input('originCity', 'Jakarta'),
            'destCode' => (string) $request->input('destCode', 'DPS'),
            'destCity' => (string) $request->input('destCity', 'Bali'),
            'depTime' => (string) $request->input('depTime', '08:00'),
            'arrTime' => (string) $request->input('arrTime', '10:50'),
            'duration' => (string) $request->input('duration', '1j 50m'),
            'price' => (float) $request->input('price', 850000),
            'seats' => (int) $request->input('seats', 45)
        ];

        array_unshift($flights, $item);
        $db['flights'] = $flights;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Jadwal penerbangan berhasil disimpan.', 'data' => $item]);
    }

    public function updateFlight(Request $request, $id)
    {
        $id = (int) $id;
        $db = $this->readDB();
        $flights = $db['flights'] ?? [];

        $idx = -1;
        foreach ($flights as $i => $f) {
            if ((int) $f['id'] === $id) {
                $idx = $i;
                break;
            }
        }

        if ($idx === -1) {
            return response()->json(['success' => false, 'message' => 'Penerbangan tidak ditemukan.'], 404);
        }

        $flights[$idx] = array_merge($flights[$idx], $request->all(), ['id' => $id]);
        $db['flights'] = $flights;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Data penerbangan berhasil diperbarui.', 'data' => $flights[$idx]]);
    }

    public function deleteFlight($id)
    {
        $id = (int) $id;
        $db = $this->readDB();
        $flights = array_values(array_filter($db['flights'] ?? [], fn($f) => (int) $f['id'] !== $id));
        $db['flights'] = $flights;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Penerbangan berhasil dihapus.']);
    }

    public function getBlogs()
    {
        $db = $this->readDB();
        return response()->json(['success' => true, 'data' => $db['blogs'] ?? []]);
    }

    public function storeBlog(Request $request)
    {
        $title = trim((string) $request->input('title', ''));
        if (!$title) {
            return response()->json(['success' => false, 'message' => 'Judul artikel wajib diisi.'], 422);
        }

        $db = $this->readDB();
        $blogs = $db['blogs'] ?? [];
        $ids = array_column($blogs, 'id');
        $newId = count($ids) > 0 ? max($ids) + 1 : 1;

        $item = [
            'id' => $newId,
            'title' => $title,
            'summary' => (string) $request->input('summary', ''),
            'content' => (string) $request->input('content', ''),
            'category' => (string) $request->input('category', 'Panduan Wisata'),
            'author' => (string) $request->input('author', 'Tim Redaksi'),
            'readTime' => (string) $request->input('readTime', '4 menit baca'),
            'date' => (string) $request->input('date', date('d M Y')),
            'image' => (string) $request->input('image', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80')
        ];

        array_unshift($blogs, $item);
        $db['blogs'] = $blogs;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Artikel blog berhasil disimpan.', 'data' => $item]);
    }

    public function updateBlog(Request $request, $id)
    {
        $id = (int) $id;
        $db = $this->readDB();
        $blogs = $db['blogs'] ?? [];

        $idx = -1;
        foreach ($blogs as $i => $b) {
            if ((int) $b['id'] === $id) {
                $idx = $i;
                break;
            }
        }

        if ($idx === -1) {
            return response()->json(['success' => false, 'message' => 'Artikel blog tidak ditemukan.'], 404);
        }

        $blogs[$idx] = array_merge($blogs[$idx], $request->all(), ['id' => $id]);
        $db['blogs'] = $blogs;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Artikel blog berhasil diperbarui.', 'data' => $blogs[$idx]]);
    }

    public function deleteBlog($id)
    {
        $id = (int) $id;
        $db = $this->readDB();
        $blogs = array_values(array_filter($db['blogs'] ?? [], fn($b) => (int) $b['id'] !== $id));
        $db['blogs'] = $blogs;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Artikel blog berhasil dihapus.']);
    }

    public function upload(Request $request)
    {
        $uploadsDir = public_path('uploads');
        if (!File::isDirectory($uploadsDir)) {
            File::makeDirectory($uploadsDir, 0755, true, true);
        }

        $image = (string) $request->input('image', '');
        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Data foto (base64) wajib disertakan.'], 400);
        }

        try {
            $ext = 'png';
            if (preg_match('/^data:([A-Za-z-+\/]+);base64,(.+)$/', $image, $matches)) {
                $mime = $matches[1];
                $ext = explode('/', $mime)[1] ?? 'png';
                if ($ext === 'jpeg') $ext = 'jpg';
                $fileBuffer = base64_decode($matches[2]);
            } else {
                $fileBuffer = base64_decode($image);
            }

            $safeName = 'blog_' . time() . '_' . rand(100, 999) . '.' . $ext;
            File::put($uploadsDir . DIRECTORY_SEPARATOR . $safeName, $fileBuffer);

            $publicUrl = url('uploads/' . $safeName);
            return response()->json([
                'success' => true,
                'message' => 'Foto berhasil diunggah ke server.',
                'data' => [
                    'filename' => $safeName,
                    'url' => $publicUrl
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengunggah foto: ' . $e->getMessage()], 500);
        }
    }
}
