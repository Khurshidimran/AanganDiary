<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidJournalEntryStateException;
use App\Exceptions\UnbalancedJournalEntryException;
use App\Http\Requests\StoreJournalEntryRequest;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\JournalEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    public function __construct(private readonly JournalEntryService $journal)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', JournalEntry::class);

        $entries = JournalEntry::with('lines')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->date_to))
            ->latest('entry_date')
            ->latest('entry_number')
            ->paginate(25)
            ->withQueryString();

        return view('journal-entries.index', compact('entries'));
    }

    public function create(): View
    {
        $this->authorize('create', JournalEntry::class);

        return view('journal-entries.create', [
            'accounts' => Account::where('status', Account::STATUS_ACTIVE)->orderBy('code')->get(),
        ]);
    }

    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $entry = $this->journal->post(
                lines: $validated['lines'],
                type: JournalEntry::TYPE_JOURNAL,
                entryDate: $validated['entry_date'],
                narration: $validated['narration'],
            );
        } catch (UnbalancedJournalEntryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('journal-entries.show', $entry)->with('status', 'Journal entry posted successfully.');
    }

    public function show(JournalEntry $journalEntry): View
    {
        $this->authorize('view', $journalEntry);

        $journalEntry->load(['lines.account', 'createdBy', 'voidedEntry', 'reversal']);

        return view('journal-entries.show', compact('journalEntry'));
    }

    public function void(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('void', $journalEntry);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            $this->journal->void($journalEntry, $validated['reason']);
        } catch (InvalidJournalEntryStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('journal-entries.show', $journalEntry)->with('status', 'Entry voided — a reversing entry has been posted.');
    }
}
