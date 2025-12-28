<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeRequest;
use App\Http\Requests\UpdateIncomeRequest;
use App\Models\Account;
use App\Models\Income;
use App\Models\Person;
use App\Services\IncomeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class IncomeController extends Controller
{
    public function __construct(public IncomeService $incomeService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $incomes = $this->incomeService->getIncomesForUser(Auth::user());

        return view('incomes.index', compact('incomes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accounts = Account::where('user_id', Auth::id())->get();
        $people = Person::all();

        return view('incomes.create', compact('accounts', 'people'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIncomeRequest $request): RedirectResponse
    {
        $this->incomeService->createIncome(Auth::user(), $request->validated());

        return redirect()->route('incomes.index')
            ->with('success', 'Income created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Income $income): View|RedirectResponse
    {
        if (! $this->incomeService->userOwnsIncome(Auth::user(), $income)) {
            abort(403);
        }

        $income->load(['account', 'person']);

        return view('incomes.show', compact('income'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Income $income): View|RedirectResponse
    {
        if (! $this->incomeService->userOwnsIncome(Auth::user(), $income)) {
            abort(403);
        }

        $accounts = Account::where('user_id', Auth::id())->get();
        $people = Person::all();

        return view('incomes.edit', compact('income', 'accounts', 'people'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIncomeRequest $request, Income $income): RedirectResponse
    {
        if (! $this->incomeService->userOwnsIncome(Auth::user(), $income)) {
            abort(403);
        }

        $this->incomeService->updateIncome($income, $request->validated());

        return redirect()->route('incomes.index')
            ->with('success', 'Income updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Income $income): RedirectResponse
    {
        if (! $this->incomeService->userOwnsIncome(Auth::user(), $income)) {
            abort(403);
        }

        $this->incomeService->deleteIncome($income);

        return redirect()->route('incomes.index')
            ->with('success', 'Income deleted successfully.');
    }
}
