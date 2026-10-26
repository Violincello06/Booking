<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->file(public_path('index.html'));
});

Route::get('/admin', function () {
    return response()->file(public_path('index.html'));
});

Route::get('/admin-panel', function () {
    return response()->file(public_path('index.html'));
});


