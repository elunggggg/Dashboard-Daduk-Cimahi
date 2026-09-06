<?php

use App\Http\Controllers\Api\DashboardDataController;
use App\Http\Controllers\Api\KategoriPublikController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardDataController::class)->name('api.dashboard');
Route::get('/dashboard-publik/kategori', KategoriPublikController::class)->name('api.dashboard-publik.kategori');

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
