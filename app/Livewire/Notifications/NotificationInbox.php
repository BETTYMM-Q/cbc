<?php

namespace App\Livewire\Notifications;

use App\Services\ModuleNotificationService;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationInbox extends Component
{
    use WithPagination;

    public function markRead(int $notificationId, ModuleNotificationService $notificationService): void
    {
        $notificationService->markRead($notificationId, auth()->id());
        $this->resetPage();
    }

    public function markAllRead(ModuleNotificationService $notificationService): void
    {
        $notificationService->markAllRead(auth()->id());
        $this->resetPage();
    }

    public function render(ModuleNotificationService $notificationService)
    {
        $notifications = $notificationService->notificationsFor(auth()->id())
            ->with(['reads' => fn ($reads) => $reads->where('user_id', auth()->id())])
            ->latest()
            ->paginate(15);

        $layout = match (true) {
            request()->routeIs('student.*') => 'layouts.student',
            request()->routeIs('parent.*') => 'layouts.parent',
            request()->routeIs('teacher.*') => 'layouts.teacher',
            request()->routeIs('admin.*') => 'layouts.admin',
            default => 'layouts.finance',
        };

        return view('livewire.notifications.notification-inbox', compact('notifications'))
            ->layout($layout);
    }
}
