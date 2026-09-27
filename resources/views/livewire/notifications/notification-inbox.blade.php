<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
        <h2 class="text-2xl font-bold text-gray-900">Notifications</h2>
        <p class="text-sm text-gray-500">School messages and updates available to your portal.</p>
        </div><button wire:click="markAllRead" class="rounded-lg border border-green-300 px-3 py-2 text-sm font-semibold text-green-700">Mark all as read</button>
    </div>

    <section class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase">Message</th>
                    <th class="px-4 py-3 text-left text-xs uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($notifications as $notification)
                    <tr class="{{ $notification->reads->isEmpty() ? 'bg-blue-50/50' : '' }}">
                        <td class="px-4 py-4"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold">{{ $notification->title }}</p><p class="mt-1 whitespace-pre-wrap text-sm text-gray-600">{{ $notification->message }}</p></div>@if($notification->reads->isEmpty())<button wire:click="markRead({{ $notification->id }})" class="shrink-0 rounded border border-blue-300 px-2 py-1 text-xs font-semibold text-blue-700">Mark read</button>@else<span class="shrink-0 text-xs font-medium text-gray-400">Read</span>@endif</div></td>
                        <td class="px-4 py-4 text-sm capitalize">{{ str_replace('_', ' ', $notification->type) }}</td>
                        <td class="px-4 py-4 text-sm text-gray-500">{{ $notification->created_at?->format('d M Y, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="p-8 text-center text-sm text-gray-500">No notifications available.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t bg-gray-50 px-4 py-3">{{ $notifications->links() }}</div>
    </section>
</div>
