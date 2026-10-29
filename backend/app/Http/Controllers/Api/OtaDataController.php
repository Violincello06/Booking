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
        if (!isset($data['banners'])) {
            $data['banners'] = [];
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
            try {
                $eloquentUser = \App\Models\User::where('email', $email)->first();
                if ($eloquentUser && \Illuminate\Support\Facades\Hash::check($password, $eloquentUser->password)) {
                    $user = [
                        'id' => $eloquentUser->id,
                        'name' => $eloquentUser->name,
                        'email' => $eloquentUser->email,
                        'role' => $eloquentUser->role ?? 'admin',
                        'phone' => $eloquentUser->phone ?? ''
                    ];
                }
            } catch (\Throwable $e) {}
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

    public function register(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $phone = trim((string) $request->input('phone', ''));
        $role = trim((string) $request->input('role', 'admin'));

        if (!$name || !$email || !$password) {
            return response()->json(['success' => false, 'message' => 'Nama, email, dan kata sandi wajib diisi.'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Format email tidak valid.'], 400);
        }

        if (strlen($password) < 6) {
            return response()->json(['success' => false, 'message' => 'Kata sandi minimal 6 karakter.'], 400);
        }

        $db = $this->readDB();
        $users = $db['users'] ?? [];

        foreach ($users as $u) {
            if (strtolower(trim($u['email'] ?? '')) === $email) {
                return response()->json(['success' => false, 'message' => 'Email sudah terdaftar. Silakan gunakan email lain atau masuk.'], 422);
            }
        }

        try {
            if (\App\Models\User::where('email', $email)->exists()) {
                return response()->json(['success' => false, 'message' => 'Email sudah terdaftar di sistem.'], 422);
            }
        } catch (\Throwable $e) {}

        $ids = array_column($users, 'id');
        $newId = count($ids) > 0 ? max($ids) + 1 : 1;

        $newUser = [
            'id' => $newId,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => in_array($role, ['admin', 'staff']) ? $role : 'admin',
            'phone' => $phone ?: '+628123456789'
        ];

        $users[] = $newUser;
        $db['users'] = $users;
        $this->writeDB($db);

        try {
            \App\Models\User::create([
                'name' => $name,
                'email' => $email,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'role' => in_array($role, ['admin', 'staff']) ? $role : 'admin',
                'phone' => $phone ?: '+628123456789'
            ]);
        } catch (\Throwable $e) {}

        $token = base64_encode(json_encode([
            'id' => $newUser['id'],
            'name' => $newUser['name'],
            'email' => $newUser['email'],
            'role' => $newUser['role'],
            'expiresAt' => (time() + 365 * 24 * 3600) * 1000
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dibuat! Anda telah terautentikasi.',
            'data' => [
                'user' => [
                    'id' => $newUser['id'],
                    'name' => $newUser['name'],
                    'email' => $newUser['email'],
                    'role' => $newUser['role'],
                    'phone' => $newUser['phone']
                ],
                'token' => $token
            ]
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $newPassword = (string) ($request->input('password') ?? $request->input('new_password') ?? '');

        if (!$email || !$newPassword) {
            return response()->json(['success' => false, 'message' => 'Email dan kata sandi baru wajib diisi.'], 400);
        }

        if (strlen($newPassword) < 6) {
            return response()->json(['success' => false, 'message' => 'Kata sandi baru minimal 6 karakter.'], 400);
        }

        $db = $this->readDB();
        $users = $db['users'] ?? [];
        $foundIndex = -1;

        foreach ($users as $i => $u) {
            if (strtolower(trim($u['email'] ?? '')) === $email) {
                $foundIndex = $i;
                break;
            }
        }

        $userInDb = null;
        try {
            $userInDb = \App\Models\User::where('email', $email)->first();
            if ($userInDb) {
                $userInDb->password = \Illuminate\Support\Facades\Hash::make($newPassword);
                $userInDb->save();
            }
        } catch (\Throwable $e) {}

        if ($foundIndex !== -1) {
            $users[$foundIndex]['password'] = $newPassword;
            $db['users'] = $users;
            $this->writeDB($db);
            return response()->json([
                'success' => true,
                'message' => 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.'
            ]);
        }

        if ($userInDb) {
            return response()->json([
                'success' => true,
                'message' => 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.'
            ]);
        }

        if ($email === 'admin@violincello.com') {
            $users[] = [
                'id' => count($users) + 1,
                'name' => 'Administrator OTA',
                'email' => 'admin@violincello.com',
                'password' => $newPassword,
                'role' => 'admin',
                'phone' => '+6281234567890'
            ];
            $db['users'] = $users;
            $this->writeDB($db);
            return response()->json([
                'success' => true,
                'message' => 'Kata sandi admin berhasil diperbarui! Silakan masuk dengan kata sandi baru.'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Email tidak terdaftar di sistem.'], 404);
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
                'totalBanners' => count($db['banners'] ?? []),
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

    private function saveBase64Image(string $image): string
    {
        if (empty($image) || !str_starts_with($image, 'data:image/')) {
            return $image;
        }

        try {
            $uploadsDir = public_path('uploads');
            if (!File::isDirectory($uploadsDir)) {
                File::makeDirectory($uploadsDir, 0755, true, true);
            }

            if (preg_match('/^data:([A-Za-z-+\/]+);base64,(.+)$/', $image, $matches)) {
                $mime = $matches[1];
                $ext = explode('/', $mime)[1] ?? 'png';
                if ($ext === 'jpeg') $ext = 'jpg';
                $buffer = base64_decode($matches[2]);
            } else {
                $buffer = base64_decode($image);
                $ext = 'jpg';
            }

            $safeName = 'blog_' . time() . '_' . rand(100, 999) . '.' . $ext;
            File::put($uploadsDir . DIRECTORY_SEPARATOR . $safeName, $buffer);
            return url('uploads/' . $safeName);
        } catch (\Throwable $e) {
            return $image;
        }
    }

    public function storeBlog(Request $request)
    {
        try {
            $title = trim((string) $request->input('title', ''));
            if (!$title) {
                return response()->json(['success' => false, 'message' => 'Judul artikel wajib diisi.'], 422);
            }

            $content = trim((string) $request->input('content', ''));
            if (!$content) {
                return response()->json(['success' => false, 'message' => 'Konten artikel wajib diisi.'], 422);
            }

            $db = $this->readDB();
            $blogs = $db['blogs'] ?? [];
            $ids = array_column($blogs, 'id');
            $newId = count($ids) > 0 ? max($ids) + 1 : 1;

            $image = (string) $request->input('image', '');
            if ($image) {
                $image = $this->saveBase64Image($image);
            } else {
                $image = 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80';
            }

            $item = [
                'id' => $newId,
                'title' => $title,
                'summary' => (string) $request->input('summary', ''),
                'content' => $content,
                'category' => (string) $request->input('category', 'Panduan Wisata'),
                'author' => (string) $request->input('author', 'Tim Redaksi CelloTrav'),
                'readTime' => (string) $request->input('readTime', '4 menit baca'),
                'date' => (string) $request->input('date', date('d M Y')),
                'image' => $image
            ];

            array_unshift($blogs, $item);
            $db['blogs'] = $blogs;
            $this->writeDB($db);

            return response()->json(['success' => true, 'message' => 'Artikel blog berhasil disimpan.', 'data' => $item]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan artikel blog: ' . $e->getMessage()], 500);
        }
    }

    public function updateBlog(Request $request, $id)
    {
        try {
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

            $payload = $request->all();
            if (isset($payload['image']) && is_string($payload['image'])) {
                $payload['image'] = $this->saveBase64Image($payload['image']);
            }

            $blogs[$idx] = array_merge($blogs[$idx], $payload, ['id' => $id]);
            $db['blogs'] = $blogs;
            $this->writeDB($db);

            return response()->json(['success' => true, 'message' => 'Artikel blog berhasil diperbarui.', 'data' => $blogs[$idx]]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui artikel blog: ' . $e->getMessage()], 500);
        }
    }

    public function deleteBlog($id)
    {
        try {
            $id = (int) $id;
            $db = $this->readDB();
            $blogs = array_values(array_filter($db['blogs'] ?? [], fn($b) => (int) $b['id'] !== $id));
            $db['blogs'] = $blogs;
            $this->writeDB($db);

            return response()->json(['success' => true, 'message' => 'Artikel blog berhasil dihapus.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus artikel: ' . $e->getMessage()], 500);
        }
    }

    public function getBanners()
    {
        $db = $this->readDB();
        $banners = $db['banners'] ?? [];
        usort($banners, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        return response()->json(['success' => true, 'data' => $banners]);
    }

    public function storeBanner(Request $request)
    {
        $title = trim((string) $request->input('title', ''));
        $image = trim((string) $request->input('image', ''));

        if (!$title || !$image) {
            return response()->json(['success' => false, 'message' => 'Judul dan URL foto banner wajib diisi.'], 422);
        }

        $db = $this->readDB();
        $banners = $db['banners'] ?? [];
        $ids = array_column($banners, 'id');
        $newId = count($ids) > 0 ? max($ids) + 1 : 1;

        $item = [
            'id' => $newId,
            'title' => $title,
            'location' => trim((string) $request->input('location', 'Indonesia')),
            'badge' => trim((string) $request->input('badge', 'Destinasi Prioritas')),
            'description' => (string) $request->input('description', ''),
            'image' => $image,
            'ctaText' => (string) $request->input('ctaText', 'Eksplorasi Destinasi'),
            'ctaLink' => (string) $request->input('ctaLink', '#destinations'),
            'order' => (int) $request->input('order', count($banners) + 1)
        ];

        $banners[] = $item;
        $db['banners'] = $banners;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Banner slide berhasil ditambahkan.', 'data' => $item]);
    }

    public function updateBanner(Request $request, $id)
    {
        $id = (int) $id;
        $db = $this->readDB();
        $banners = $db['banners'] ?? [];

        $idx = -1;
        foreach ($banners as $i => $b) {
            if ((int) $b['id'] === $id) {
                $idx = $i;
                break;
            }
        }

        if ($idx === -1) {
            return response()->json(['success' => false, 'message' => 'Banner tidak ditemukan.'], 404);
        }

        $banners[$idx] = array_merge($banners[$idx], $request->all(), ['id' => $id]);
        $db['banners'] = $banners;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Banner slide berhasil diperbarui.', 'data' => $banners[$idx]]);
    }

    public function deleteBanner($id)
    {
        $id = (int) $id;
        $db = $this->readDB();
        $banners = array_values(array_filter($db['banners'] ?? [], fn($b) => (int) $b['id'] !== $id));
        $db['banners'] = $banners;
        $this->writeDB($db);

        return response()->json(['success' => true, 'message' => 'Banner slide berhasil dihapus.']);
    }

    public function upload(Request $request)
    {
        try {
            $uploadsDir = public_path('uploads');
            if (!File::isDirectory($uploadsDir)) {
                File::makeDirectory($uploadsDir, 0755, true, true);
            }

            // Cek file multipart form data
            if ($request->hasFile('file') || $request->hasFile('image')) {
                $file = $request->file('file') ?? $request->file('image');
                if ($file && $file->isValid()) {
                    $ext = $file->getClientOriginalExtension() ?: 'jpg';
                    $safeName = 'blog_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $file->move($uploadsDir, $safeName);
                    return response()->json([
                        'success' => true,
                        'message' => 'Foto berhasil diunggah ke server.',
                        'data' => [
                            'filename' => $safeName,
                            'url' => url('uploads/' . $safeName)
                        ]
                    ]);
                }
            }

            // Cek format base64
            $image = (string) $request->input('image', '');
            if (!$image) {
                return response()->json(['success' => false, 'message' => 'Data foto (file atau base64) wajib disertakan.'], 400);
            }

            $ext = 'png';
            if (preg_match('/^data:([A-Za-z-+\/]+);base64,(.+)$/', $image, $matches)) {
                $mime = $matches[1];
                $ext = explode('/', $mime)[1] ?? 'png';
                if ($ext === 'jpeg') $ext = 'jpg';
                $fileBuffer = base64_decode($matches[2]);
            } else {
                $fileBuffer = base64_decode($image);
                $ext = 'jpg';
            }

            $safeName = 'blog_' . time() . '_' . rand(100, 999) . '.' . $ext;
            File::put($uploadsDir . DIRECTORY_SEPARATOR . $safeName, $fileBuffer);

            return response()->json([
                'success' => true,
                'message' => 'Foto berhasil diunggah ke server.',
                'data' => [
                    'filename' => $safeName,
                    'url' => url('uploads/' . $safeName)
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengunggah foto: ' . $e->getMessage()], 500);
        }
    }
}
