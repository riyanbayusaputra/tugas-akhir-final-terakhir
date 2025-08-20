<div class="max-w-[480px] mx-auto bg-white min-h-screen relative shadow-lg pb-32">
    <!-- Header -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[480px] bg-white z-50">
        <div class="flex items-center h-16 px-4 border-b border-gray-100">
            <button onclick="history.back()" class="p-2 hover:bg-gray-50 rounded-full">
                <i class="bi bi-arrow-left text-xl"></i>
            </button>
            <h1 class="ml-2 text-lg font-medium">Keranjang</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="pt-16 px-4">
        <!-- Store Section -->
        <div class="pt-4">
            <!-- Clear All Button (only show if cart not empty) -->
            @if($carts->isNotEmpty())
                <div class="flex justify-end mb-4">
                    <button 
                        wire:click="clearCart" 
                        class="text-xs text-red-500 hover:text-red-700 hover:bg-red-50 px-3 py-1 rounded-lg transition-colors"
                        wire:confirm="Yakin ingin mengosongkan keranjang belanja?"
                    >
                        <i class="bi bi-trash text-xs mr-1"></i>
                        Kosongkan Keranjang
                    </button>
                </div>
            @endif
            
            <!-- Cart Items -->
            <div class="space-y-4">
                @forelse($carts as $cart) 
                    <div class="flex gap-3 pb-4 border-b border-gray-100" wire:key="cart-{{ $cart->id }}"> 
                        <!-- Product Image -->
                        <div class="flex-shrink-0">
                            <img src="{{$cart->product->first_image_url ?? asset('image/no-pictures.png')}}" 
                                alt=""
                                loading="lazy"
                                class="w-20 h-20 object-cover rounded-lg">
                        </div>

                        <!-- Product Details -->
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <h3 class="text-sm font-medium line-clamp-2 flex-1 pr-2">{{$cart->product->name}}</h3>
                                <!-- Delete Button -->
                                <button 
                                    wire:click="removeItem({{$cart->id}})" 
                                    class="flex-shrink-0 p-1 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors"
                                 
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-primary font-semibold">Rp {{number_format($cart->product->price, 0, ',', '.')}}</span>
                                
                                <!-- Input Quantity - UPDATED FOR LIVE INPUT -->
                                <div class="flex items-center">
                                    <label class="text-xs text-gray-500 mr-2">Qty:</label>
                                    <input 
                                        type="number" 
                                        min="1" 
                                        max="9999"
                                        class="w-16 px-2 py-1 text-sm text-center border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                        value="{{$cart->quantity}}" 
                                        wire:input="updateQuantity({{$cart->id}}, $event.target.value)"
                                        wire:keyup="updateQuantity({{$cart->id}}, $event.target.value)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center min-h-[60vh]">
                        <!-- Icon cart kosong -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <p class="text-xl font-medium text-gray-400 mb-2">Keranjang Belanja Kosong</p>
                        <p class="text-sm text-gray-400">Belum ada produk yang ditambahkan</p>
                        <a href="{{ route('home') }}" class="mt-6 px-6 py-2 bg-primary text-white rounded-full text-sm hover:bg-primary/90 transition-colors">
                            Mulai Belanja
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @if($carts->isNotEmpty())
        <div class="flex items-center justify-end mt-4 px-4">
            <a href="{{ route('home') }}" class="text-primary text-sm font-medium bg-primary/10 hover:bg-primary/20 py-2 px-4 rounded-lg">
                Tambah item
            </a>
        </div>
    @endif

    @if($carts->isNotEmpty())
    <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[480px] bg-white border-t border-gray-100 p-4 z-50">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-sm text-gray-600">Total Pembayaran:</p>
                <p class="text-lg font-semibold text-primary">Rp {{number_format($total)}}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500">{{$totalItems}} Menu</p>
            </div>
        </div>
        @php
            $canCheckout = true;
            
            foreach($carts as $cart) {
                // Cari nama kategori dari cart ini
                $categoryName = '';
                foreach($categories as $category) {
                    if ($category->id == $cart->product->category_id) {
                        $categoryName = strtolower($category->name);
                        break;
                    }
                }
                
                // Skip jika kategori mengandung "tumpeng" atau "tupeng"
                if (str_contains($categoryName, 'tumpeng') || str_contains($categoryName, 'tupeng')) {
                    continue;
                }
                // Jika kategori mengandung "prasmanan"
                if (str_contains($categoryName, 'prasmanan')) {
                    // Minimal 50 porsi
                    if ($cart->quantity < 50) {
                        $canCheckout = false;
                        break;
                    }
                }
                
              if (str_contains($categoryName, 'nasi box')) {
                    // Minimal 30 porsi
                    if ($cart->quantity < 30) {
                        $canCheckout = false;
                        break;
                    }
                }
                
                // Untuk kategori Snack, minimal 20
                if (str_contains($categoryName, 'snack')) {
                    if ($cart->quantity < 20) {
                        $canCheckout = false;
                        break;
                    }
                }
            }
        @endphp
        @if($canCheckout)
            <button wire:click="checkout" class="w-full h-12 flex items-center justify-center rounded-full bg-primary text-white font-medium hover:bg-primary/90 transition-colors">
                Checkout
            </button>
        @else
            @php
            $messages = [];
            foreach($carts as $cart) {
                $categoryName = '';
                foreach($categories as $category) {
                if ($category->id == $cart->product->category_id) {
                    $categoryName = strtolower($category->name);
                    break;
                }
                }
                if (str_contains($categoryName, 'prasmanan') && $cart->quantity < 50) {
                $messages[] = 'Prasmanan min. 50 porsi';
                }
                if (str_contains($categoryName, 'nasi box') && $cart->quantity < 30) {
                $messages[] = 'Nasi Box min. 30 porsi';
                }
                if (str_contains($categoryName, 'snack') && $cart->quantity < 20) {
                $messages[] = 'Snack min. 20 porsi';
                }
            }
            $messages = array_unique($messages);
            @endphp
            <button class="w-full h-12 flex items-center justify-center rounded-full bg-gray-300 text-gray-500 font-medium cursor-not-allowed" disabled>
            {{ implode(' | ', $messages) }}
            </button>
        @endif
    </div>
@endif
</div>