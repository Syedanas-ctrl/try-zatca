<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZatcaController;
use App\Http\Controllers\TestController;

// Add these routes to your routes/web.php file

    Route::get('/', function () {
        return view('onboarding');
    });
    Route::get('/csr', [TestController::class, 'getCsr'])->name('csr');
    Route::get('/csid', [TestController::class, 'getCSID'])->name('csid');
    Route::get('/sinvoice', [TestController::class, 'getInvoice'])->name('invoice');
    Route::get('/invoice-signed', [TestController::class, 'getInvoiceSigned'])->name('invoice-signed');
    Route::get('/compliance', [TestController::class, 'compliance'])->name('compliance');
    Route::get('/report', [TestController::class, 'report'])->name('report');
    Route::get('/prod-csid', [TestController::class, 'getProdCSID'])->name('prod-csid');
    Route::get('/renew-prod-csid', [TestController::class, 'renewProdCSID'])->name('renew-prod-csid');
    // New routes for invoice hash generation
    Route::get('/test-invoice-hash', [TestController::class, 'testInvoiceHash'])->name('test-invoice-hash');
    Route::get('/sign-invoice-and-get-hash', [TestController::class, 'signInvoiceAndGetHash'])->name('sign-invoice-and-get-hash');
