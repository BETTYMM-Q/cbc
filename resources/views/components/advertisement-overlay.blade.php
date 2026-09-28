@props(['ad'])

@if($ad && !in_array($ad->id, session('dismissed_ads', [])))
<div x-data="{ open: true }" x-show="open" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 p-4 w-screen h-screen overflow-hidden">
    <div class="relative max-w-5xl w-full h-auto max-h-[90vh] flex flex-col items-center justify-center bg-slate-900 rounded-lg shadow-2xl p-2">
        <!-- Close / Dismiss Control -->
        <button @click="open = false" class="absolute -top-3 -right-3 bg-red-600 hover:bg-red-700 text-white rounded-full p-2 w-10 h-10 flex items-center justify-center font-bold text-lg z-10 shadow-lg focus:outline-none" aria-label="Close Advertisement">
            &times;
        </button>

        <!-- Media Display -->
        <div class="w-full h-full flex items-center justify-center overflow-hidden rounded">
            @if($ad->isVideo())
                <video src="{{ $ad->media_url }}" autoplay muted controls playsinline class="max-w-full max-h-[80vh] w-auto h-auto object-contain rounded">
                    Your browser does not support video playback.
                </video>
            @else
                <img src="{{ $ad->media_url }}" alt="{{ $ad->title ?? 'Advertisement' }}" class="max-w-full max-h-[80vh] w-auto h-auto object-contain rounded">
            @endif
        </div>
    </div>
</div>
@endif
