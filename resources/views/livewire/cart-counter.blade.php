<a href="{{ route('client.cart.index') }}" class="relative inline-flex items-center">
    <i class="fas fa-shopping-cart text-gray-600 text-xl"></i>
    @if($count > 0)
        <span class="ml-2 inline-flex items-center justify-center rounded-full bg-yellow-600 px-2 py-0.5 text-xs font-semibold text-white">
            {{ $count }}
        </span>
    @endif
</a>
