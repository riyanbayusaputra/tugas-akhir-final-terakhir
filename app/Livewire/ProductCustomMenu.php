<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Cart;

class ProductCustomMenu extends Component
{
    public $product;
    public $selectedOptions = [];
    public $cartCount = 0;

    public function mount($slug)
    {
        $this->product = Product::with('options.optionItems')
            ->where('slug', $slug)
            ->firstOrFail();

        // Tidak auto-select, biarkan kosong
        foreach ($this->product->options as $option) { 
            $this->selectedOptions[$option->id] = [];
        }

        $this->updateCartCount();
    }

    public function updatedSelectedOptions()
    {
        // Method ini akan dipanggil setiap kali selectedOptions berubah
        // Memastikan UI update langsung
    }

    public function updateCartCount()
    {
        $this->cartCount = auth()->check()
            ? Cart::where('user_id', auth()->id())->sum('quantity')
            : 0;
    }

    

    public function addToCart()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Validasi: cek apakah semua option sudah dipilih
        foreach ($this->product->options as $option) {
            if (empty($this->selectedOptions[$option->id])) {
                $this->dispatch('showAlert', [
                    'message' => 'Silakan pilih ' . $option->name . ' terlebih dahulu',
                    'type' => 'error'
                ]);
                return;
            }
        }

        try {
            $customOptionsJson = json_encode($this->selectedOptions);

            $existingCart = Cart::where('user_id', auth()->id())
                ->where('product_id', $this->product->id)
                ->where('custom_options_json', $customOptionsJson)
                ->first(); 

            if ($existingCart) {
                $existingCart->update([
                    'quantity' => $existingCart->quantity + 1
                ]);
            } else {
                Cart::create([
                    'user_id' => auth()->id(),
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'custom_options_json' => $customOptionsJson,
                ]);
            }

            $this->updateCartCount();
           
            $this->dispatch('showAlert', [
                'message' => 'Berhasil ditambahkan ke keranjang',
                'type' => 'success'
            ]);

        } catch(\Exception $e) {
            \Log::error('Cart Error: ' . $e->getMessage());
            
            $this->dispatch('showAlert', [
                'message' => 'Gagal menambahkan ke keranjang: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.product-custom-menu');
    }
}