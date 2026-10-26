<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin', function () {
    return response()->file(public_path('admin.html'));
});

Route::get('/admin-panel', function () {
    return response()->file(public_path('admin.html'));
});

