<div class="max-w-[480px] mx-auto bg-white min-h-screen relative shadow-lg pb-36 px-4">

    <!-- Header Gambar -->
    <div class="pt-6">
        <div class="w-full h-48 mb-3 relative">
            <img 
                src="{{ $product->first_image_url ?: 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=480&q=80' }}" 
                alt="{{ $product->name }}" 
                class="w-full h-48 object-cover rounded-md"
            >
        </div>
    </div>

    <!-- Judul -->
    <div class="mt-6 mb-8 text-center">
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Silahkan pilih isi menu</h3>
        <p class="text-gray-600 text-base">Pilih sesuai selera Anda</p>
        <div class="w-24 h-1 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full mx-auto mt-3"></div>
    </div>

    <!-- Validasi Error -->
    @if (session()->has('error'))
        <div class="text-red-500 text-sm text-center mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Opsi Dinamis -->
    @if($product->options->isEmpty())
        <div class="text-center text-gray-500 my-12">
            Produk ini tidak memiliki opsi kustom.
        </div>
    @else
        @foreach($product->options as $option)
        <div class="mb-12">
            <h3 class="text-xl font-bold text-gray-800 mb-2 capitalize tracking-wide flex items-center">
                <span class="w-2 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full mr-3"></span>
                Pilih {{ $option->name }}
            </h3>
            <p class="text-gray-500 text-sm ml-5">Silakan pilih salah satu atau lebih opsi di bawah</p>

            <div class="flex gap-4 overflow-x-auto pb-4 scrollbar-thin px-1">
                @foreach($option->optionItems as $item)
                <label class="min-w-[140px] bg-white border-2 rounded-2xl p-5 flex flex-col items-center text-center cursor-pointer hover:border-blue-300 hover:shadow-xl transition-all duration-300 shadow-md relative group
                    {{ in_array($item->id, $selectedOptions[$option->id] ?? []) ? 'border-blue-500 ring-2 ring-blue-100' : 'border-gray-200' }}"
                    wire:loading.class="opacity-75">
                    <input
                        type="checkbox"
                        class="hidden"
                        wire:model.live="selectedOptions.{{ $option->id }}"
                        value="{{ $item->id }}"
                    >

                    <!-- Ceklist -->
                    <div class="absolute -top-1.5 -right-1.5 w-6 h-6 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 border-2 border-white flex items-center justify-center shadow-lg z-20 transition-all duration-300
                        {{ in_array($item->id, $selectedOptions[$option->id] ?? []) ? 'opacity-100 scale-110' : 'opacity-0' }}">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>

                    <!-- Gambar Opsi -->
                    <div class="w-20 h-20 mb-3 relative overflow-hidden rounded-xl ring-2 ring-gray-100">
                        <img
                            src="{{ $item->image ? asset('storage/' . $item->image) : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=80&q=80' }}"
                            class="w-full h-full object-cover"
                            alt="{{ $item->name }}"
                        >
                    </div>

                    <!-- Nama Item -->
                    <span class="font-semibold text-gray-800 text-sm mb-1 leading-tight">{{ $item->name }}</span>

                    {{-- <!-- Harga Tambahan -->
                    @if($item->additional_price > 0)
                        <span class="text-xs font-medium text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 px-2 py-1">
                            +Rp {{ number_format($item->additional_price, 0, ',', '.') }}
                        </span>
                    @else
                        <span class="text-xs text-green-600 font-medium px-2 py-1 bg-green-50 rounded-full">Gratis</span>
                    @endif --}}

                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-purple-500/5 rounded-2xl transition-opacity duration-300
                        {{ in_array($item->id, $selectedOptions[$option->id] ?? []) ? 'opacity-100' : 'opacity-0' }}"></div>
                </label>
                @endforeach
            </div>
        </div>
        @endforeach
    @endif

   
    <!-- Tombol -->
<div class="right-0 left-0 bottom-0 bg-white border-t border-gray-100 p-4">
    <button 
        class="w-full font-semibold py-3 rounded-lg transition-all duration-200
            {{ $product->options->isEmpty() ? 'bg-gray-400 text-gray-600 cursor-not-allowed opacity-50' : 'bg-primary text-white hover:bg-primary-dark disabled:opacity-50' }}"
        wire:click="addToCart"
        wire:loading.attr="disabled"
        wire:target="addToCart"
        {{ $product->options->isEmpty() ? 'disabled' : '' }}
    >
        @if($product->options->isEmpty())
            <span>Produk Tidak Memiliki Opsi Custom</span>
        @else
            <span wire:loading.remove wire:target="addToCart">Tambahkan ke Keranjang</span>
            <span wire:loading wire:target="addToCart"></span>
        @endif
    </button>
</div>
</div>

