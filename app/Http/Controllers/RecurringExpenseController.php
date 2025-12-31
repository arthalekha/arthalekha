<?php

namespace App\Http\Controllers;

use App\Enums\Frequency;
use App\Http\Requests\StoreRecurringExpenseRequest;
use App\Http\Requests\UpdateRecurringExpenseRequest;
use App\Models\Person;
use App\Models\RecurringExpense;
use App\Models\Tag;
use App\Services\AccountService;
use App\Services\RecurringExpenseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecurringExpenseController extends Controller
{
    public function __construct(
        public RecurringExpenseService $recurringExpenseService,
        public AccountService $accountService,
    ) {}

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

        $recurringExpenses = $this->recurringExpenseService->getRecurringExpensesForUser(Auth::user());
        $accounts = $this->accountService->getAllForUser(Auth::id());
        $people = Person::all();
        $frequencies = Frequency::cases();

        return view('recurring-expenses.index', compact('recurringExpenses', 'accounts', 'people', 'frequencies', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accounts = $this->accountService->getAllForUser(Auth::id());
        $people = Person::all();
        $frequencies = Frequency::cases();
        $tags = Tag::all();

        return view('recurring-expenses.create', compact('accounts', 'people', 'frequencies', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRecurringExpenseRequest $request): RedirectResponse
    {
        $this->recurringExpenseService->createRecurringExpense(Auth::user(), $request->validated());

        return redirect()->route('recurring-expenses.index')
            ->with('success', 'Recurring expense created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RecurringExpense $recurringExpense): View|RedirectResponse
    {
        if (! $this->recurringExpenseService->userOwnsRecurringExpense(Auth::user(), $recurringExpense)) {
            abort(403);
        }

        $recurringExpense->load(['account', 'person', 'tags']);

        return view('recurring-expenses.show', compact('recurringExpense'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RecurringExpense $recurringExpense): View|RedirectResponse
    {
        if (! $this->recurringExpenseService->userOwnsRecurringExpense(Auth::user(), $recurringExpense)) {
            abort(403);
        }

        $accounts = $this->accountService->getAllForUser(Auth::id());
        $people = Person::all();
        $frequencies = Frequency::cases();
        $tags = Tag::all();
        $recurringExpense->load('tags');

        return view('recurring-expenses.edit', compact('recurringExpense', 'accounts', 'people', 'frequencies', 'tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRecurringExpenseRequest $request, RecurringExpense $recurringExpense): RedirectResponse
    {
        if (! $this->recurringExpenseService->userOwnsRecurringExpense(Auth::user(), $recurringExpense)) {
            abort(403);
        }

        $this->recurringExpenseService->updateRecurringExpense($recurringExpense, $request->validated());

        return redirect()->route('recurring-expenses.index')
            ->with('success', 'Recurring expense updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecurringExpense $recurringExpense): RedirectResponse
    {
        if (! $this->recurringExpenseService->userOwnsRecurringExpense(Auth::user(), $recurringExpense)) {
            abort(403);
        }

        $this->recurringExpenseService->deleteRecurringExpense($recurringExpense);

        return redirect()->route('recurring-expenses.index')
            ->with('success', 'Recurring expense deleted successfully.');
    }
}
