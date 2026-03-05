@props(['title' => 'Notifications'])

@php
    $user = auth()->user();
    $notifications = $user ? $user->notifications()->latest()->take(6)->get() : collect();
    $unreadIds = $user ? $user->unreadNotifications->pluck('id')->all() : [];
@endphp

<div class="bg-white rounded-xl shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
        @if($user && $user->unreadNotifications->count() > 0)
            <span class="text-xs font-semibold bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">
                {{ $user->unreadNotifications->count() }} non lue(s)
            </span>
        @endif
    </div>

    @if($notifications->isEmpty())
        <p class="text-sm text-gray-500">Aucune notification pour le moment.</p>
    @else
        <div class="space-y-3">
            @foreach($notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $isUnread = in_array($notification->id, $unreadIds, true);
                @endphp
                <div class="p-3 rounded-lg border {{ $isUnread ? 'border-yellow-200 bg-yellow-50' : 'border-gray-100 bg-gray-50' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ $data['title'] ?? 'Notification' }}
                            </p>
                            <p class="text-xs text-gray-600 mt-1">
                                {{ $data['message'] ?? '' }}
                            </p>
                            @if(!empty($data['action_url']))
                                <a href="{{ $data['action_url'] }}"
                                   class="inline-flex items-center mt-2 text-xs font-semibold text-yellow-600 hover:text-yellow-700">
                                    {{ $data['action_text'] ?? 'Voir' }}
                                </a>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
