<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\Frequency;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AccountService;
use App\Services\AverageBalanceService;
use App\Services\BalanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function __construct(
        public AccountService $accountService,
        public BalanceService $balanceService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $accounts = $this->accountService->getAccountsForUser(Auth::user());
        $accountTypes = AccountType::cases();

        return view('accounts.index', compact('accounts', 'accountTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accountTypes = AccountType::cases();
        $frequencies = Frequency::cases();

        return view('accounts.create', compact('accountTypes', 'frequencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $this->accountService->createAccount(Auth::user(), $request->validated());

        return redirect()->route('accounts.index')
            ->with('success', 'Account created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account): View|RedirectResponse
    {
        $averageBalance = null;
        $averageBalanceFrequency = null;
        if ($account->account_type === AccountType::Savings) {
            $averageBalance = App::make(AverageBalanceService::class)->calculate($account);
            $frequencyValue = $account->data['average_balance_frequency'] ?? null;
            $averageBalanceFrequency = $frequencyValue ? Frequency::tryFrom($frequencyValue) : Frequency::Monthly;
        }

        return view('accounts.show', compact('account', 'averageBalance', 'averageBalanceFrequency'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Account $account): View|RedirectResponse
    {
        $accountTypes = AccountType::cases();
        $frequencies = Frequency::cases();

        return view('accounts.edit', compact('account', 'accountTypes', 'frequencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $this->accountService->updateAccount($account, $request->validated());

        return redirect()->route('accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account): RedirectResponse
    {
        $this->accountService->deleteAccount($account);

        return redirect()->route('accounts.index')
            ->with('success', 'Account deleted successfully.');
    }
}
