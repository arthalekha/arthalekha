<?php

namespace App\Http\Controllers;

use App\Enums\Frequency;
use App\Http\Requests\StoreRecurringIncomeRequest;
use App\Http\Requests\UpdateRecurringIncomeRequest;
use App\Models\Account;
use App\Models\Person;
use App\Models\RecurringIncome;
use App\Services\RecurringIncomeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecurringIncomeController extends Controller
{
    public function __construct(public RecurringIncomeService $recurringIncomeService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->input('filter.search'),
            'account_id' => $request->input('filter.account_id'),
            'person_id' => $request->input('filter.person_id'),
            'frequency' => $request->input('filter.frequency'),
        ];

        $recurringIncomes = $this->recurringIncomeService->getRecurringIncomesForUser(Auth::user());
        $accounts = Account::where('user_id', Auth::id())->get();
        $people = Person::all();
        $frequencies = Frequency::cases();

        return view('recurring-incomes.index', compact('recurringIncomes', 'accounts', 'people', 'frequencies', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accounts = Account::where('user_id', Auth::id())->get();
        $people = Person::all();
        $frequencies = Frequency::cases();

        return view('recurring-incomes.create', compact('accounts', 'people', 'frequencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRecurringIncomeRequest $request): RedirectResponse
    {
        $this->recurringIncomeService->createRecurringIncome(Auth::user(), $request->validated());

        return redirect()->route('recurring-incomes.index')
            ->with('success', 'Recurring income created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RecurringIncome $recurringIncome): View|RedirectResponse
    {
        if (! $this->recurringIncomeService->userOwnsRecurringIncome(Auth::user(), $recurringIncome)) {
            abort(403);
        }

        $recurringIncome->load(['account', 'person']);

        return view('recurring-incomes.show', compact('recurringIncome'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RecurringIncome $recurringIncome): View|RedirectResponse
    {
        if (! $this->recurringIncomeService->userOwnsRecurringIncome(Auth::user(), $recurringIncome)) {
            abort(403);
        }

        $accounts = Account::where('user_id', Auth::id())->get();
        $people = Person::all();
        $frequencies = Frequency::cases();

        return view('recurring-incomes.edit', compact('recurringIncome', 'accounts', 'people', 'frequencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRecurringIncomeRequest $request, RecurringIncome $recurringIncome): RedirectResponse
    {
        if (! $this->recurringIncomeService->userOwnsRecurringIncome(Auth::user(), $recurringIncome)) {
            abort(403);
        }

        $this->recurringIncomeService->updateRecurringIncome($recurringIncome, $request->validated());

        return redirect()->route('recurring-incomes.index')
            ->with('success', 'Recurring income updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecurringIncome $recurringIncome): RedirectResponse
    {
        if (! $this->recurringIncomeService->userOwnsRecurringIncome(Auth::user(), $recurringIncome)) {
            abort(403);
        }

        $this->recurringIncomeService->deleteRecurringIncome($recurringIncome);

        return redirect()->route('recurring-incomes.index')
            ->with('success', 'Recurring income deleted successfully.');
    }
}
