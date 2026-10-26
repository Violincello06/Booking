<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Violincello Travel & Reservation - REST API Backend</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .api-hero {
            padding: 4rem 1.5rem 2rem;
            text-align: center;
        }
        .endpoint-card {
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .http-method {
            padding: 0.35rem 0.75rem;
            border-radius: var(--radius-sm);
            font-weight: 800;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }
        .method-get { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); }
        .method-post { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4); }
        .method-put { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }
        .method-delete { background: rgba(244, 63, 94, 0.2); color: #f87171; border: 1px solid rgba(244, 63, 94, 0.4); }
        .endpoint-path {
            font-family: monospace;
            font-size: 0.95rem;
            color: #f1f5f9;
        }
    </style>
</head>
<body>
    <div class="container api-hero">
        <span class="badge badge-primary animate-float">🚀 Laravel 13 REST API Core</span>
        <h1 style="font-size: 3rem; margin-top: 1rem; margin-bottom: 0.5rem;">
            Violincello <span class="text-gradient">Travel API</span>
        </h1>
        <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto 2rem;">
            Online Travel Agency & Reservation Backend Engine terintegrasi dengan Sanctum Bearer Token, Role-Based Access, dan Query Search Builder.
        </p>

        <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 3rem;">
            <a href="/api/v1/health" target="_blank" class="btn btn-primary">
                <span>💚</span> Cek Health Status
            </a>
            <a href="/api/v1/search/hotels" target="_blank" class="btn btn-glass">
                <span>🏨</span> API Search Hotels
            </a>
            <a href="/api/v1/search/flights" target="_blank" class="btn btn-glass">
                <span>✈️</span> API Search Flights
            </a>
        </div>

        <div class="glass-panel" style="padding: 2.5rem; text-align: left; max-width: 900px; margin: 0 auto;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.4rem;">Daftar Endpoint Utama (v1)</h3>
                <span class="badge badge-success">39 Tests Passing</span>
            </div>

            <!-- Endpoints List -->
            <div class="glass-card endpoint-card">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span class="http-method method-get">GET</span>
                    <span class="endpoint-path">/api/v1/health</span>
                </div>
                <span style="color: var(--text-muted); font-size: 0.85rem;">System & Database Health Check</span>
            </div>

            <div class="glass-card endpoint-card">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span class="http-method method-get">GET</span>
                    <span class="endpoint-path">/api/v1/search/hotels</span>
                </div>
                <span style="color: var(--text-muted); font-size: 0.85rem;">Pencarian & Filter Hotel, Bintang, Harga</span>
            </div>

            <div class="glass-card endpoint-card">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span class="http-method method-get">GET</span>
                    <span class="endpoint-path">/api/v1/search/flights</span>
                </div>
                <span style="color: var(--text-muted); font-size: 0.85rem;">Pencarian Jadwal Penerbangan (CGK ➔ DPS)</span>
            </div>

            <div class="glass-card endpoint-card">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span class="http-method method-post">POST</span>
                    <span class="endpoint-path">/api/v1/register</span>
                </div>
                <span style="color: var(--text-muted); font-size: 0.85rem;">Registrasi Pengguna Baru</span>
            </div>

            <div class="glass-card endpoint-card">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span class="http-method method-post">POST</span>
                    <span class="endpoint-path">/api/v1/login</span>
                </div>
                <span style="color: var(--text-muted); font-size: 0.85rem;">Autentikasi & Issuance Bearer Token</span>
            </div>

            <div class="glass-card endpoint-card">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span class="http-method method-get">GET</span>
                    <span class="endpoint-path">/api/v1/admin/dashboard</span>
                </div>
                <span class="badge badge-warning" style="font-size: 0.75rem;">Auth: Sanctum (Admin Only)</span>
            </div>
        </div>
    </div>
</body>
</html>
