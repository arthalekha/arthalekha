<?php

namespace App\Http\Controllers;

use App\Enums\Frequency;
use App\Http\Requests\StoreRecurringTransferRequest;
use App\Http\Requests\UpdateRecurringTransferRequest;
use App\Models\RecurringTransfer;
use App\Services\AccountService;
use App\Services\RecurringTransferService;
use App\Services\TagService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecurringTransferController extends Controller
{
    public function __construct(
        public RecurringTransferService $recurringTransferService,
        public AccountService $accountService,
        public TagService $tagService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->input('filter.search'),
            'creditor_id' => $request->input('filter.creditor_id'),
            'debtor_id' => $request->input('filter.debtor_id'),
            'frequency' => $request->input('filter.frequency'),
        ];

        $recurringTransfers = $this->recurringTransferService->getRecurringTransfersForUser(Auth::user());
        $accounts = $this->accountService->getAllForUser(Auth::id());
        $frequencies = Frequency::cases();

        return view('recurring-transfers.index', compact('recurringTransfers', 'accounts', 'frequencies', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accounts = $this->accountService->getAllForUser(Auth::id());
        $frequencies = Frequency::cases();
        $tags = $this->tagService->getAll();

        return view('recurring-transfers.create', compact('accounts', 'frequencies', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRecurringTransferRequest $request): RedirectResponse
    {
        $this->recurringTransferService->createRecurringTransfer(Auth::user(), $request->validated());

        return redirect()->route('recurring-transfers.index')
            ->with('success', 'Recurring transfer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RecurringTransfer $recurringTransfer): View|RedirectResponse
    {
        if (! $this->recurringTransferService->userOwnsRecurringTransfer(Auth::user(), $recurringTransfer)) {
            abort(403);
        }

        $recurringTransfer->load(['creditor', 'debtor', 'tags']);

        return view('recurring-transfers.show', compact('recurringTransfer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RecurringTransfer $recurringTransfer): View|RedirectResponse
    {
        if (! $this->recurringTransferService->userOwnsRecurringTransfer(Auth::user(), $recurringTransfer)) {
            abort(403);
        }

        $accounts = $this->accountService->getAllForUser(Auth::id());
        $frequencies = Frequency::cases();
        $tags = $this->tagService->getAll();
        $recurringTransfer->load('tags');

        return view('recurring-transfers.edit', compact('recurringTransfer', 'accounts', 'frequencies', 'tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRecurringTransferRequest $request, RecurringTransfer $recurringTransfer): RedirectResponse
    {
        if (! $this->recurringTransferService->userOwnsRecurringTransfer(Auth::user(), $recurringTransfer)) {
            abort(403);
        }

        $this->recurringTransferService->updateRecurringTransfer($recurringTransfer, $request->validated());

        return redirect()->route('recurring-transfers.index')
            ->with('success', 'Recurring transfer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecurringTransfer $recurringTransfer): RedirectResponse
    {
        if (! $this->recurringTransferService->userOwnsRecurringTransfer(Auth::user(), $recurringTransfer)) {
            abort(403);
        }

        $this->recurringTransferService->deleteRecurringTransfer($recurringTransfer);

        return redirect()->route('recurring-transfers.index')
            ->with('success', 'Recurring transfer deleted successfully.');
    }
}
