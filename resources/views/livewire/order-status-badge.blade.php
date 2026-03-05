@if($order)
<span wire:poll.10s
      class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $this->getStatusClass() }}">
    <i class="{{ $this->getStatusIcon() }} mr-1"></i>
    {{ $this->getStatusText() }}
</span>
@else
    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
        <i class="fas fa-question mr-1"></i>
        Inconnu
    </span>
@endif
