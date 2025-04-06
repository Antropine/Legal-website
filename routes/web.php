<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConsultationController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return view('home');
})->name('home');

Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');


Route::get('/calendar', [ConsultationController::class, 'showCalendar'])->name('calendar.show');
Route::post('/consultation', [ConsultationController::class, 'store'])->name('consultation.store');
Route::get('/', [ConsultationController::class, 'index'])->name('home');