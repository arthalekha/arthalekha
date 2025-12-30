<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Person;
use App\Models\Tag;
use App\Services\ExpenseService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function __construct(public ExpenseService $expenseService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = [
            'from_date' => $request->input('filter.from_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'to_date' => $request->input('filter.to_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
            'search' => $request->input('filter.search'),
            'account_id' => $request->input('filter.account_id'),
            'person_id' => $request->input('filter.person_id'),
            'tag_id' => $request->input('filter.tag_id'),
        ];

        // Merge default filters into request for Spatie Query Builder
        $request->mergeIfMissing([
            'filter' => [
                'from_date' => $filters['from_date'],
                'to_date' => $filters['to_date'],
            ],
        ]);

        $expenses = $this->expenseService->getExpensesForUser(Auth::user());
        $accounts = Account::where('user_id', Auth::id())->get();
        $people = Person::all();
        $tags = Tag::all();

        return view('expenses.index', compact('expenses', 'accounts', 'people', 'tags', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accounts = Account::where('user_id', Auth::id())->get();
        $people = Person::all();
        $tags = Tag::all();

        return view('expenses.create', compact('accounts', 'people', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->expenseService->createExpense(Auth::user(), $request->validated());

        return redirect()->route('expenses.index')
            ->with('success', 'Expense created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense): View|RedirectResponse
    {
        if (! $this->expenseService->userOwnsExpense(Auth::user(), $expense)) {
            abort(403);
        }

        $expense->load(['account', 'person']);

        return view('expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense): View|RedirectResponse
    {
        if (! $this->expenseService->userOwnsExpense(Auth::user(), $expense)) {
            abort(403);
        }

        $accounts = Account::where('user_id', Auth::id())->get();
        $people = Person::all();
        $tags = Tag::all();
        $expense->load('tags');

        return view('expenses.edit', compact('expense', 'accounts', 'people', 'tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        if (! $this->expenseService->userOwnsExpense(Auth::user(), $expense)) {
            abort(403);
        }

        $this->expenseService->updateExpense($expense, $request->validated());

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        if (! $this->expenseService->userOwnsExpense(Auth::user(), $expense)) {
            abort(403);
        }

        $this->expenseService->deleteExpense($expense);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }
}
