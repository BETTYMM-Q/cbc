@php
    $notificationRoute = match (true) {
        request()->routeIs('teacher.*') => 'teacher.notifications.inbox', request()->routeIs('admin.*') => 'admin.notifications.inbox',
        request()->routeIs('parent.*') => 'parent.notifications.index', request()->routeIs('student.*') => 'student.notifications',
        request()->routeIs('finance.*') => 'finance.notifications.index', default => null,
    };
    $unreadNotifications = auth()->check() ? app(\App\Services\ModuleNotificationService::class)->unreadCount(auth()->id()) : 0;
@endphp
@if($notificationRoute && Route::has($notificationRoute))
<a href="{{ route($notificationRoute) }}" class="relative rounded-lg border border-gray-200 px-2.5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" aria-label="Notifications">&#128276;<span class="hidden sm:inline"> Notifications</span>@if($unreadNotifications)<span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1 text-center text-[10px] font-bold leading-5 text-white">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>@endif</a>
@endif
