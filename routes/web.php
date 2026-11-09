<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DailyController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\UtangController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/* Public landing (§8.1): logged-in users go straight to /home. */
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->to(auth()->user()->business ? '/home' : '/onboarding');
    }

    return Inertia::render('Landing', [
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
    ]);
})->name('landing');

Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

Route::middleware(['auth', 'business'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Sales + POS (§8.4–8.5)
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::post('/sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');

    // Stock (§8.6)
    Route::get('/stock', [ProductController::class, 'index'])->name('stock.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::post('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/{product}/restock', [ProductController::class, 'restock'])->name('products.restock');
    Route::post('/photos', [ProductController::class, 'uploadPhoto'])->name('photos.store');

    // Utang (§8.7)
    Route::get('/utang', [UtangController::class, 'index'])->name('utang.index');
    Route::post('/utang', [UtangController::class, 'storeUtang'])->name('utang.store');
    Route::post('/utang/payment', [UtangController::class, 'storePayment'])->name('utang.payment');
    Route::post('/customers/{customer}/tag', [UtangController::class, 'setTag'])->name('customers.tag');
    Route::post('/reminders', [UtangController::class, 'storeReminder'])->name('reminders.store');

    // Gastos (§8.8)
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    // Insights
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/csv', [ReportsController::class, 'csv'])->name('reports.csv');
    Route::get('/daily', [DailyController::class, 'index'])->name('daily.index');

    // Branches (prem, §8.11)
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::post('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');

    // Assistant (prem, §8.12)
    Route::get('/assistant', [AssistantController::class, 'index'])->name('assistant.index');
    Route::post('/assistant/ask', [AssistantController::class, 'ask'])->name('assistant.ask');
    Route::post('/assistant/clear', [AssistantController::class, 'clear'])->name('assistant.clear');

    // Plans + settings
    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::post('/pricing', [PricingController::class, 'setPlan'])->name('pricing.plan');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/data/sample', [DataController::class, 'loadSample'])->name('data.sample');
    Route::post('/data/reset', [DataController::class, 'reset'])->name('data.reset');

    Route::get('/more', fn () => Inertia::render('More/Index'))->name('more');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/superadmin', [SuperadminController::class, 'index'])->name('superadmin.index');
});

Route::get('/photo/{path}', [PhotoController::class, 'view'])
    ->where('path', '.*')
    ->middleware('signed')
    ->name('photo.view');

require __DIR__.'/auth.php';
