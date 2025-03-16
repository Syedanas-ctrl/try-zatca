<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZatcaController;
use App\Http\Controllers\TestController;

// Add these routes to your routes/web.php file

    Route::get('/', function () {
        return view('onboarding');
    });

    Route::get('/previous-invoice', [TestController::class, 'getPreviousInvoiceData'])->name('previous-invoice');
    Route::get('/csr', [TestController::class, 'getCsr'])->name('csr');
    Route::get('/csid', [TestController::class, 'getCSID'])->name('csid');
    Route::get('/invoice', [TestController::class, 'getInvoice'])->name('invoice');
    Route::get('/invoice-signed', [TestController::class, 'getInvoiceSigned'])->name('invoice-signed');
    Route::get('/compliance', [TestController::class, 'compliance'])->name('compliance');
    Route::get('/simple-invoice', [TestController::class, 'getSimpleInvoice'])->name('simple-invoice');
    Route::get('/simple-invoice-signed', [TestController::class, 'getSimpleInvoiceSigned'])->name('simple-invoice-signed');
    
    // New routes for invoice hash generation
    Route::get('/test-invoice-hash', [TestController::class, 'testInvoiceHash'])->name('test-invoice-hash');
    Route::get('/sign-invoice-and-get-hash', [TestController::class, 'signInvoiceAndGetHash'])->name('sign-invoice-and-get-hash');
