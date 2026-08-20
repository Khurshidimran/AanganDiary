<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChannelRequest;
use App\Http\Requests\UpdateChannelRequest;
use App\Models\Channel;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChannelController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Channel::class);

        $channels = Channel::withCount('orders')->orderBy('name')->paginate(20);

        return view('channels.index', compact('channels'));
    }

    public function create(): View
    {
        $this->authorize('create', Channel::class);

        return view('channels.create');
    }

    public function store(StoreChannelRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['code'] = $this->uniqueCode($validated['name']);

        $channel = Channel::create($validated);

        $this->auditLog->log('created', 'channels', $channel, null, $channel->only(['name', 'status']));

        return redirect()->route('channels.index')->with('status', 'Channel created successfully.');
    }

    public function edit(Channel $channel): View
    {
        $this->authorize('update', $channel);

        return view('channels.edit', compact('channel'));
    }

    public function update(UpdateChannelRequest $request, Channel $channel): RedirectResponse
    {
        $validated = $request->validated();
        $before = $channel->only(['name', 'status']);

        // The code (used internally, e.g. to identify the Shopify channel)
        // stays fixed once set — renaming a channel shouldn't silently
        // change the key other code looks it up by.
        $channel->update($validated);

        $this->auditLog->log('updated', 'channels', $channel, $before, $channel->only(['name', 'status']));

        return redirect()->route('channels.index')->with('status', 'Channel updated successfully.');
    }

    public function destroy(Channel $channel): RedirectResponse
    {
        $this->authorize('delete', $channel);

        if ($channel->is_system) {
            return back()->with('error', "Cannot delete \"{$channel->name}\" — it's a system-protected channel.");
        }

        if ($channel->orders()->exists()) {
            return back()->with('error', "Cannot delete \"{$channel->name}\" — it has order history.");
        }

        $before = $channel->only(['name', 'status']);
        $channel->delete();

        $this->auditLog->log('deleted', 'channels', null, $before, null);

        return redirect()->route('channels.index')->with('status', 'Channel deleted successfully.');
    }

    private function uniqueCode(string $name): string
    {
        $code = Str::slug($name);
        $original = $code;
        $i = 1;

        while (Channel::where('code', $code)->exists()) {
            $code = "{$original}-{$i}";
            $i++;
        }

        return $code;
    }
}
