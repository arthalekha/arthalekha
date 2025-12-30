<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\UpdateTransferRequest;
use App\Models\Account;
use App\Models\Tag;
use App\Models\Transfer;
use App\Services\TransferService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferController extends Controller
{
    public function __construct(public TransferService $transferService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = [
            'from_date' => $request->input('filter.from_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'to_date' => $request->input('filter.to_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
            'search' => $request->input('filter.search'),
            'debtor_id' => $request->input('filter.debtor_id'),
            'creditor_id' => $request->input('filter.creditor_id'),
            'tag_id' => $request->input('filter.tag_id'),
        ];

        // Merge default filters into request for Spatie Query Builder
        $request->mergeIfMissing([
            'filter' => [
                'from_date' => $filters['from_date'],
                'to_date' => $filters['to_date'],
            ],
        ]);

        $transfers = $this->transferService->getTransfersForUser(Auth::user());
        $accounts = Account::where('user_id', Auth::id())->get();
        $tags = Tag::all();

        return view('transfers.index', compact('transfers', 'accounts', 'tags', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accounts = Account::where('user_id', Auth::id())->get();
        $tags = Tag::all();

        return view('transfers.create', compact('accounts', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransferRequest $request): RedirectResponse
    {
        $this->transferService->createTransfer(Auth::user(), $request->validated());

        return redirect()->route('transfers.index')
            ->with('success', 'Transfer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Transfer $transfer): View|RedirectResponse
    {
        if (! $this->transferService->userOwnsTransfer(Auth::user(), $transfer)) {
            abort(403);
        }

        $transfer->load(['creditor', 'debtor']);

        return view('transfers.show', compact('transfer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transfer $transfer): View|RedirectResponse
    {
        if (! $this->transferService->userOwnsTransfer(Auth::user(), $transfer)) {
            abort(403);
        }

        $accounts = Account::where('user_id', Auth::id())->get();
        $tags = Tag::all();
        $transfer->load('tags');

        return view('transfers.edit', compact('transfer', 'accounts', 'tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransferRequest $request, Transfer $transfer): RedirectResponse
    {
        if (! $this->transferService->userOwnsTransfer(Auth::user(), $transfer)) {
            abort(403);
        }

        $this->transferService->updateTransfer($transfer, $request->validated());

        return redirect()->route('transfers.index')
            ->with('success', 'Transfer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transfer $transfer): RedirectResponse
    {
        if (! $this->transferService->userOwnsTransfer(Auth::user(), $transfer)) {
            abort(403);
        }

        $this->transferService->deleteTransfer($transfer);

        return redirect()->route('transfers.index')
            ->with('success', 'Transfer deleted successfully.');
    }
}
