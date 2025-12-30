<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountTransactionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\RecurringExpenseController;
use App\Http\Controllers\RecurringIncomeController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/home', HomeController::class)->name('home');

    Route::resource('accounts', AccountController::class);
    Route::get('accounts/{account}/transactions', AccountTransactionController::class)->name('accounts.transactions');
    Route::resource('expenses', ExpenseController::class);
    Route::resource('incomes', IncomeController::class);
    Route::resource('recurring-incomes', RecurringIncomeController::class);
    Route::resource('recurring-expenses', RecurringExpenseController::class);
    Route::resource('transfers', TransferController::class);
    Route::resource('tags', TagController::class);
});
