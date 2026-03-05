<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border {{ $this->getStatusColor() }}">
    <i class="{{ $this->getStatusIcon() }} mr-1"></i>
    {{ $this->getStatusText() }}
</span>
