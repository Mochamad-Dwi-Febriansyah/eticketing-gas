<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\GasStocksController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {  
    Route::prefix('auth')->group(function () {  
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/login', [AuthController::class, 'login']);
    });
    Route::middleware(['checkToken'])->group(function () {
        Route::prefix('auth')->group(function () {  
            Route::post('/logout', [AuthController::class, 'logout']);
        });
        Route::middleware(['role:super_admin'])->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::post('/users', [UserController::class, 'store']);
            Route::put('/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
            Route::post('/users/restore/{id}', [UserController::class, 'restore']);

            Route::get('/branches', [BranchController::class, 'index']);
            Route::get('/branches/{id}', [BranchController::class, 'show']);
            Route::post('/branches', [BranchController::class, 'store']);
            Route::put('/branches/{id}', [BranchController::class, 'update']);
            Route::delete('/branches/{id}', [BranchController::class, 'destroy']);
            Route::post('/branches/restore/{id}', [BranchController::class, 'restore']);

            Route::get('/gas-stocks', [GasStocksController::class, 'index']); 
            Route::get('/gas-stocks/{id}', [GasStocksController::class, 'show']);
            Route::post('/gas-stocks', [GasStocksController::class, 'store']);
            Route::put('/gas-stocks/{id}', [GasStocksController::class, 'update']);
            Route::delete('/gas-stocks/{id}', [GasStocksController::class, 'destroy']);
            Route::post('/gas-stocks/restore/{id}', [GasStocksController::class, 'restore']);

            Route::get('/orders', [OrdersController::class, 'index']);
            Route::get('/orders/{id}', [OrdersController::class, 'show']);
            Route::post('/orders', [OrdersController::class, 'store']);
            Route::put('/orders/{id}', [OrdersController::class, 'update']);
            Route::delete('/orders/{id}', [OrdersController::class, 'destroy']);
            Route::post('/orders/restore/{id}', [OrdersController::class, 'restore']);

            Route::get('/transactions', [TransactionsController::class, 'index']);
            Route::get('/transactions/{id}', [TransactionsController::class, 'show']);
            Route::post('/transactions', [TransactionsController::class, 'store']);
            Route::put('/transactions/{id}', [TransactionsController::class, 'update']);
            Route::delete('/transactions/{id}', [TransactionsController::class, 'destroy']);
            Route::post('/transactions/restore/{id}', [TransactionsController::class, 'restore']);
        });

        Route::middleware(['role:admin_cabang'])->group(function () {
            Route::get('/users-by-branch', [UserController::class, 'indexByBranch']); // 🔹 Get users in branch

            Route::get('/gas-stocks-by-branch', [GasStocksController::class, 'indexByBranch']);
            Route::post('/gas-stocks-by-branch', [GasStocksController::class, 'storeByBranch']);
            Route::put('/gas-stocks-by-branch/{id}', [GasStocksController::class, 'updateByBranch']);
            Route::delete('/gas-stocks-by-branch/{id}', [GasStocksController::class, 'destroyByBranch']);

            Route::get('/orders-by-branch', [OrdersController::class, 'indexByBranch']);
            Route::put('/orders-by-branch/{id}/status', [OrdersController::class, 'updateStatus']);

            Route::get('/transactions-by-branch', [TransactionsController::class, 'indexByBranch']);
        });

        Route::middleware(['role:user'])->group(function () {
            Route::post('/orders-by-user', [OrdersController::class, 'storeByUser']); 
            Route::get('/orders-by-user', [OrdersController::class, 'indexByUser']); 
            Route::get('/orders-by-user/{id}', [OrdersController::class, 'showByUser']); 
        
            // 🔹 Melihat transaksi pembayaran
            Route::get('/transactions-by-user', [TransactionsController::class, 'indexByUser']);
            Route::get('/transactions-by-user/{id}', [TransactionsController::class, 'showByUser']);
        
            // 🔹 Melakukan pembayaran (contoh: input bukti transfer)
            Route::post('/transactions-by-user/{id}/pay', [TransactionsController::class, 'pay']);
        });
    });
});