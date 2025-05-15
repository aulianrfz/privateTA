<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportExcelController;
use App\Http\Controllers\PenjadwalanController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SubKategoriController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\JuriController;
use App\Http\Controllers\VenueController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/debug/constraint', [PenjadwalanController::class, 'prosesJadwal']);

Route::get('/debug/pesertaKategori', [PenjadwalanController::class, 'processPesertaKategoriLomba']);

Route::get('/debug/variabelX', [PenjadwalanController::class, 'generateVariabelX']);

Route::get('/jadwal', [PenjadwalanController::class, 'index'])->name('jadwal.index');
Route::get('/jadwal/create', [PenjadwalanController::class, 'create'])->name('jadwal.create');
Route::post('/jadwal/create/step2', [PenjadwalanController::class, 'createStep2'])->name('jadwal.create.step2');
Route::post('/jadwal/store', [PenjadwalanController::class, 'store'])->name('jadwal.store');
Route::post('/jadwal/create-step3', [PenjadwalanController::class, 'createStep3'])->name('jadwal.create.step3');
Route::get('/jadwal/detail/{nama_jadwal}/{tahun}/{version}', [PenjadwalanController::class, 'detail'])->name('jadwal.detail');
Route::get('/jadwal/{nama_jadwal}/{tahun}/{version}/switch', [PenjadwalanController::class, 'switchJadwal'])->name('jadwal.switch');
Route::post('/jadwal/switch/proses', [PenjadwalanController::class, 'prosesSwitch'])->name('jadwal.switch.proses');
Route::resource('jadwal', PenjadwalanController::class);
Route::get('/jadwal/{id}/edit', [PenjadwalanController::class, 'edit'])->name('jadwal.edit');
Route::put('/jadwal/{id}', [PenjadwalanController::class, 'update'])->name('jadwal.update');
Route::get('/jadwal/{nama_jadwal}/{tahun}/{version}/create', [PenjadwalanController::class, 'createWithDetail'])->name('jadwal.create.withDetail');
Route::post('/jadwal/add', [PenjadwalanController::class, 'add'])->name('jadwal.add');


Route::get('/', function () {
    return redirect()->route('login');
});

// Guest only (belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated users
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return view(auth()->user()->role === 'admin' ? 'admin.dashboard' : 'landing');
    })->name('dashboard');

    Route::middleware(['role:user'])->group(function () {
        Route::get('/landingpage', [HomeController::class, 'index']);
        Route::get('/events/{id}', [HomeController::class, 'show'])->name('event.show');
        Route::get('/event/{eventId}', [HomeController::class, 'showEvent'])->name('event.list');
        Route::get('/event/list/{kategori_id}', [EventController::class, 'showCategory'])->name('event.showCategory');
        Route::get('/event/detail/{id}', [EventController::class, 'showDetail'])->name('event.detail');

        // Pendaftaran
        Route::get('/pendaftaran/{id_subkategori}', [PendaftaranController::class, 'showForm'])->name('pendaftaran.form');
        Route::post('/pendaftaran/store', [PendaftaranController::class, 'store'])->name('pendaftaran.store');

        // // pembayaran
        // Route::get('/my-event', [DashboardController::class, 'index'])->name('events.index');

    });

    // ADMIN ROUTES
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('subkategori', SubKategoriController::class);
        Route::resource('juri', JuriController::class);
    });
});







Route::get('/import-excel', [ImportExcelController::class, 'import_excel']);
Route::post('/import-excel', [ImportExcelController::class, 'import_excel_post'])->name('import_excel_post');

Route::get('/landingpage', [HomeController::class, 'index']);
Route::get('/events/{id}', [HomeController::class, 'show'])->name('event.show');
Route::get('/event/{eventId}', [HomeController::class, 'showEvent'])->name('event.list');
Route::get('/event/list/{kategori_id}', [EventController::class, 'showCategory'])->name('event.showCategory');
Route::get('/event/detail/{id}', [EventController::class, 'showDetail'])->name('event.detail');


// Route::get('/category/{categoryId}', [HomeController::class, 'categoryEvents'])->name('event.list.category');

// CRUD
Route::resource('subkategori', SubKategoriController::class);

Route::resource('kategori', KategoriController::class);

Route::resource('juri', JuriController::class);

Route::resource('venue', VenueController::class);


// Daftar lomba
// Route::get('/daftar/lomba{id_subkategori}', [PendaftaranController::class, 'showForm'])->name('pendaftaran.lomba');
Route::get('/pendaftaran/{id_subkategori}', [PendaftaranController::class, 'showForm'])->name('pendaftaran.form');
Route::post('/pendaftaran/store', [PendaftaranController::class, 'store'])->name('pendaftaran.store');


// dashboard myevent
Route::get('/my-event', [DashboardController::class, 'index'])->name('events.index');
// pembayaran
Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
Route::get('/pembayaran/{id}', [PembayaranController::class, 'detail'])->name('pembayaran.detail');

// nyoba halaman sukses
// Route::get('/sukses', [PendaftaranController::class, 'sukses']);

Route::post('/generate-schedule', [PenjadwalanController::class, 'generateSchedule']);
