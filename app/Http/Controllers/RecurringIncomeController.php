<?php

namespace App\Http\Controllers;

use App\Enums\Frequency;
use App\Http\Requests\StoreRecurringIncomeRequest;
use App\Http\Requests\UpdateRecurringIncomeRequest;
use App\Models\RecurringIncome;
use App\Services\AccountService;
use App\Services\PersonService;
use App\Services\RecurringIncomeService;
use App\Services\TagService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecurringIncomeController extends Controller
{
    public function __construct(
        public RecurringIncomeService $recurringIncomeService,
        public AccountService $accountService,
        public PersonService $personService,
        public TagService $tagService,
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

        $recurringIncomes = $this->recurringIncomeService->getRecurringIncomesForUser(Auth::user());
        $accounts = $this->accountService->getAllForUser(Auth::id());
        $people = $this->personService->getAll();
        $frequencies = Frequency::cases();

        return view('recurring-incomes.index', compact('recurringIncomes', 'accounts', 'people', 'frequencies', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accounts = $this->accountService->getAllForUser(Auth::id());
        $people = $this->personService->getAll();
        $frequencies = Frequency::cases();
        $tags = $this->tagService->getAll();

        return view('recurring-incomes.create', compact('accounts', 'people', 'frequencies', 'tags'));
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
        $recurringIncome->load(['account', 'person', 'tags']);

        return view('recurring-incomes.show', compact('recurringIncome'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RecurringIncome $recurringIncome): View|RedirectResponse
    {
        $accounts = $this->accountService->getAllForUser(Auth::id());
        $people = $this->personService->getAll();
        $frequencies = Frequency::cases();
        $tags = $this->tagService->getAll();
        $recurringIncome->load('tags');

        return view('recurring-incomes.edit', compact('recurringIncome', 'accounts', 'people', 'frequencies', 'tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRecurringIncomeRequest $request, RecurringIncome $recurringIncome): RedirectResponse
    {
        $this->recurringIncomeService->updateRecurringIncome($recurringIncome, $request->validated());

        return redirect()->route('recurring-incomes.index')
            ->with('success', 'Recurring income updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecurringIncome $recurringIncome): RedirectResponse
    {
        $this->recurringIncomeService->deleteRecurringIncome($recurringIncome);

        return redirect()->route('recurring-incomes.index')
            ->with('success', 'Recurring income deleted successfully.');
    }
}
