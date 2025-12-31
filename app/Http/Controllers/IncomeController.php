<?php

namespace App\Http\Controllers;

use App\Exports\IncomesExport;
use App\Http\Requests\StoreIncomeRequest;
use App\Http\Requests\UpdateIncomeRequest;
use App\Models\Income;
use App\Services\AccountService;
use App\Services\IncomeService;
use App\Services\PersonService;
use App\Services\TagService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IncomeController extends Controller
{
    public function __construct(
        public IncomeService $incomeService,
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

        $incomes = $this->incomeService->getIncomesForUser(Auth::user());
        $accounts = $this->accountService->getAllForUser(Auth::id());
        $people = $this->personService->getAll();
        $tags = $this->tagService->getAll();

        return view('incomes.index', compact('incomes', 'accounts', 'people', 'tags', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accounts = $this->accountService->getAllForUser(Auth::id());
        $people = $this->personService->getAll();
        $tags = $this->tagService->getAll();

        return view('incomes.create', compact('accounts', 'people', 'tags'));
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

        $accounts = $this->accountService->getAllForUser(Auth::id());
        $people = $this->personService->getAll();
        $tags = $this->tagService->getAll();
        $income->load('tags');

        return view('incomes.edit', compact('income', 'accounts', 'people', 'tags'));
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

    /**
     * Export incomes to CSV.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $request->mergeIfMissing([
            'filter' => [
                'from_date' => $request->input('filter.from_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
                'to_date' => $request->input('filter.to_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
            ],
        ]);

        $query = $this->incomeService->getIncomesQueryForExport(Auth::user());

        return (new IncomesExport($query))->download('incomes.csv', Excel::CSV);
    }
}
