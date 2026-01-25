<?php

use App\Http\Controllers\AccountBalanceController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountProjectedBalanceController;
use App\Http\Controllers\AccountTransactionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FamilyModeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\InviteUserController;
use App\Http\Controllers\ProjectedDashboardController;
use App\Http\Controllers\RecurringExpenseController;
use App\Http\Controllers\RecurringIncomeController;
use App\Http\Controllers\RecurringTransferController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/home', HomeController::class)->name('home');
    Route::get('/projected-dashboard', ProjectedDashboardController::class)->name('projected-dashboard');

    Route::resource('accounts', AccountController::class);
    Route::get('accounts/{account}/transactions', AccountTransactionController::class)->name('accounts.transactions');
    Route::get('accounts/{account}/balances', AccountBalanceController::class)->name('accounts.balances');
    Route::get('accounts/{account}/projected-balance', AccountProjectedBalanceController::class)->name('accounts.projected-balance');
    Route::resource('expenses', ExpenseController::class);
    Route::post('expenses/export/csv', [ExpenseController::class, 'export'])->name('expenses.export');
    Route::resource('incomes', IncomeController::class);
    Route::post('incomes/export/csv', [IncomeController::class, 'export'])->name('incomes.export');
    Route::resource('recurring-incomes', RecurringIncomeController::class);
    Route::resource('recurring-expenses', RecurringExpenseController::class);
    Route::resource('recurring-transfers', RecurringTransferController::class);
    Route::resource('transfers', TransferController::class);
    Route::post('transfers/export/csv', [TransferController::class, 'export'])->name('transfers.export');
    Route::resource('tags', TagController::class);

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/invite', [InviteUserController::class, 'create'])->name('users.invite');
    Route::post('users/invite', [InviteUserController::class, 'store'])->name('users.invite.store');

    Route::post('mode/toggle', FamilyModeController::class)->name('mode.toggle');

    Route::group([
        'as' => 'family.',
        'prefix' => 'family',
    ], function () {
        Route::get('home', HomeController::class)->name('home');
        Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('incomes', [IncomeController::class, 'index'])->name('incomes.index');
        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
        Route::get('recurring-incomes', [RecurringIncomeController::class, 'index'])->name('recurring-incomes.index');
        Route::get('recurring-expenses', [RecurringExpenseController::class, 'index'])->name('recurring-expenses.index');
        Route::get('recurring-transfers', [RecurringTransferController::class, 'index'])->name('recurring-transfers.index');
        Route::get('projected-dashboard', ProjectedDashboardController::class)->name('projected-dashboard');
    });
});
