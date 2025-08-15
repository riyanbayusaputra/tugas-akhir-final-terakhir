<?php

namespace App\Livewire;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Store;
use App\Models\DeliveryArea;
use Livewire\Component;
use App\Models\ProductOptionItem;
use App\Services\MidtransService;
use App\Notifications\NewOrderNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Checkout extends Component
{
    public $showMap = true;

    public $selectedOptions = [];
    public $selectedOptionItems = [];
    public $selectedOptionItemsName = [];
    public $selectedOptionItemsPrice = [];
    public $selectedOptionItemsId = [];
    public $selectedOptionItemsJson = [];
    public $selectedOptionItemsJsonName = [];
    public $carts = [];
    
    // Pisahkan subtotal dan total untuk clarity
    public $subtotal = 0;
    public $shippingCost = 0;
    public $total = 0;
    
    public $store;
    public $price_adjustment = 0;
    public $isCustomCatering = false;
    public $customCatering = [
        'menu_description' => '',
    ];
    
    protected $midtrans;
    
    public $shippingData = [
        'recipient_name' => '',
        'phone' => '',
        'shipping_address' => '',
        'noted' => '',
        'delivery_date' => '',
        'delivery_time' => '',
    ];

    // Data wilayah
    public $availableProvinsis = [];
    public $availableKabupatens = [];
    public $availableKecamatans = [];
    
    public $selected_provinsi = '';
    public $selected_kabupaten = '';
    public $selected_kecamatan = '';

    // Koordinat untuk map picker
    public $userLatitude = null;
    public $userLongitude = null;
    public $isCalculatingShipping = false;
    public $mapSelectedAddress = '';
    public $shippingDistance = 0; // dalam km
    public $shippingInfo = '';
    
    // Koordinat toko/admin (sesuaikan dengan lokasi toko Anda)
   private $adminCoordinates = [
    'lat' => -6.861722,    // Tegal Latitude
    'lon' => 109.1334094    // Tegal Longitude  
];



    

    // Konfigurasi ongkir
    private $shippingConfig = [
        'rate_per_km' => 2000,    // Rp 2.000 per km
        'minimum_cost' => 5000,   // Ongkir minimal Rp 5.000
        // 'maximum_distance' => 50, // Maksimal 50 km (TAMBAHKAN INI!)
        'free_shipping_threshold' => 1000000, // Free shipping untuk belanja di atas Rp 1.000.000
    ];

    // Add listeners for Livewire events
    protected $listeners = [
        'coordinatesUpdated' => 'handleCoordinatesUpdated'
    ];

    protected function rules()
    {
        $rules = [
            'shippingData.recipient_name' => 'required|min:3',
            'shippingData.phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'shippingData.shipping_address' => 'required|min:5',
            'shippingData.delivery_date' => 'required|date|after_or_equal:today', // PERBAIKAN: Tambah validasi tanggal
            'shippingData.delivery_time' => 'required',
            'selected_provinsi' => 'required',
            'selected_kabupaten' => 'required',
            'selected_kecamatan' => 'required',
            'userLatitude' => 'required|numeric|between:-90,90',
            'userLongitude' => 'required|numeric|between:-180,180',
        ];

        if ($this->isCustomCatering) {
            $rules['customCatering.menu_description'] = 'required|min:5';
        }

        return $rules;
    }

    protected $messages = [
        'shippingData.phone.required' => 'Nomor telepon wajib diisi.',
        'shippingData.phone.min' => 'Nomor telepon minimal 10 karakter.',
        'shippingData.phone.regex' => 'Format nomor telepon tidak valid.',
        'shippingData.recipient_name.required' => 'Nama penerima wajib diisi.',
        'shippingData.recipient_name.min' => 'Nama penerima minimal 3 karakter.',
        'shippingData.shipping_address.required' => 'Alamat pengiriman wajib diisi.',
        'shippingData.shipping_address.min' => 'Alamat pengiriman minimal 5 karakter.',
        'shippingData.delivery_date.required' => 'Tanggal pengiriman wajib dipilih.',
        'shippingData.delivery_date.date' => 'Format tanggal tidak valid.',
        'shippingData.delivery_date.after_or_equal' => 'Tanggal pengiriman tidak boleh kurang dari hari ini.',
        'shippingData.delivery_time.required' => 'Waktu pengiriman wajib dipilih.',
        'selected_provinsi.required' => 'Provinsi wajib dipilih.',
        'selected_kabupaten.required' => 'Kabupaten/Kota wajib dipilih.',
        'selected_kecamatan.required' => 'Kecamatan wajib dipilih.',
        'customCatering.menu_description.required' => 'Deskripsi menu wajib diisi.',
        'userLatitude.required' => 'Silakan pilih lokasi pada peta.',
        'userLongitude.required' => 'Silakan pilih lokasi pada peta.',
        'userLatitude.between' => 'Koordinat latitude tidak valid.',
        'userLongitude.between' => 'Koordinat longitude tidak valid.',
    ];

    public function boot(MidtransService $midtrans)
    {
        $this->midtrans = $midtrans;
    }

    public function mount()
    {
        $this->loadCarts();
        if ($this->carts->isEmpty()) {
            return redirect()->route('home');
        }
        
        // PERBAIKAN: Tambah penanganan error untuk store
        $this->store = Store::first();
        if (!$this->store) {
            Log::error('Store not found during checkout mount');
            $this->dispatch('showAlert', [
                'message' => 'Konfigurasi toko tidak ditemukan. Silakan hubungi administrator.',
                'type' => 'error'
            ]);
            return;
        }

        if (auth()->check()) {
            $user = auth()->user();
            $this->shippingData['recipient_name'] = $user->name;
            $this->shippingData['phone'] = $user->no_telepon ?? '';
        }

        $this->loadAvailableAreas();
    }

    public function loadAvailableAreas()
    {
        try {
            $this->availableProvinsis = DeliveryArea::getAvailableProvinsi()->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading available areas: ' . $e->getMessage());
            $this->availableProvinsis = [];
        }
    }

    public function updatedSelectedProvinsi($provinsiId)
    {
        $this->selected_provinsi = $provinsiId;
        $this->selected_kabupaten = '';
        $this->selected_kecamatan = '';
        $this->availableKabupatens = [];
        $this->availableKecamatans = [];
        $this->resetShippingCost();
        
        if (!empty($provinsiId)) {
            try {
                $this->availableKabupatens = DeliveryArea::getAvailableKabupaten($provinsiId)->toArray();
            } catch (\Exception $e) {
                Log::error('Error loading kabupaten: ' . $e->getMessage());
                $this->availableKabupatens = [];
            }
        }
        
        $this->calculateTotal();
    }

    public function updatedSelectedKabupaten($kabupatenId)
    {
        $this->selected_kabupaten = $kabupatenId;
        $this->selected_kecamatan = '';
        $this->availableKecamatans = [];
        $this->resetShippingCost();
        
        if (!empty($kabupatenId) && !empty($this->selected_provinsi)) {
            try {
                $this->availableKecamatans = DeliveryArea::getAvailableKecamatan($kabupatenId)->toArray();
            } catch (\Exception $e) {
                Log::error('Error loading kecamatan: ' . $e->getMessage());
                $this->availableKecamatans = [];
            }
        }
        
        $this->calculateTotal();
    }

    public function updatedSelectedKecamatan($kecamatanId)
    {
        $this->selected_kecamatan = $kecamatanId;
        
        // Auto calculate shipping jika sudah ada koordinat
        if (!empty($kecamatanId) && $this->userLatitude && $this->userLongitude) {
            $this->calculateShippingCost();
        } else {
            $this->resetShippingCost();
        }
    }

    /**
     * Handle coordinates updated from JavaScript
     */
    public function handleCoordinatesUpdated($data)
    {
        $this->updateCoordinates($data['latitude'], $data['longitude'], $data['address'] ?? '');
    }

    /**
     * Method untuk handle koordinat dari map picker
     */
    public function updateCoordinates($latitude, $longitude, $address = '', $distance = null, $shippingCost = null)
    {
        Log::info('Update coordinates called', [
            'lat' => $latitude,
            'lng' => $longitude,
            'address' => $address,
            'distance_from_js' => $distance,
            'shipping_cost_from_js' => $shippingCost
        ]);

        // Validasi koordinat
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            $this->addError('coordinates', 'Koordinat tidak valid');
            Log::error('Invalid coordinates provided');
            return;
        }

        // Validasi rentang koordinat
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            $this->addError('coordinates', 'Koordinat di luar rentang yang valid');
            Log::error('Coordinates out of valid range');
            return;
        }

        $this->userLatitude = (float) $latitude;
        $this->userLongitude = (float) $longitude;
        $this->mapSelectedAddress = $address;
        
        // PERBAIKAN: Jika JavaScript sudah mengirim distance dan shippingCost yang valid, gunakan itu
        if ($distance !== null && $shippingCost !== null && is_numeric($distance) && is_numeric($shippingCost)) {
            Log::info('Using distance and shipping cost from JavaScript', [
                'distance' => $distance,
                'shipping_cost' => $shippingCost
            ]);
            
            $this->shippingDistance = (float) $distance;
            $this->shippingCost = (float) $shippingCost;
            
            // Update shipping info
            $this->updateShippingInfo($distance);
            
            // Recalculate total
            $this->calculateTotal();
            
            // Clear errors
            $this->resetErrorBag(['coordinates', 'shipping']);
            
            Log::info('Coordinates and shipping updated from JS', [
                'userLat' => $this->userLatitude,
                'userLng' => $this->userLongitude,
                'distance' => $this->shippingDistance,
                'cost' => $this->shippingCost
            ]);
            
            return;
        }
        
        // Clear error koordinat jika ada
        $this->resetErrorBag(['coordinates']);
        
        Log::info('Coordinates updated, will calculate shipping', [
            'userLat' => $this->userLatitude,
            'userLng' => $this->userLongitude,
            'selected_kecamatan' => $this->selected_kecamatan
        ]);
        
        // Hitung ongkir jika area sudah dipilih
        if ($this->selected_provinsi && $this->selected_kabupaten && $this->selected_kecamatan) {
            $this->calculateShippingCost();
        } else {
            Log::info('Area not complete, skipping shipping calculation');
        }
    }

    /**
     * Method untuk menghitung shipping cost berdasarkan jarak real
     */
    private function calculateShippingRate($distanceKm)
    {
        // Cek apakah memenuhi syarat free shipping
        if ($this->subtotal >= $this->shippingConfig['free_shipping_threshold']) {
            Log::info('Free shipping applied', [
                'subtotal' => $this->subtotal,
                'threshold' => $this->shippingConfig['free_shipping_threshold']
            ]);
            return 0;
        }
        
        // PENTING: Gunakan ceil() sama seperti JavaScript Math.ceil()
        $calculatedCost = ceil($distanceKm) * $this->shippingConfig['rate_per_km'];
        $finalCost = max($calculatedCost, $this->shippingConfig['minimum_cost']);
        
        Log::info('Shipping rate calculation', [
            'distance_km' => $distanceKm,
            'distance_ceil' => ceil($distanceKm),
            'rate_per_km' => $this->shippingConfig['rate_per_km'],
            'calculated_cost' => $calculatedCost,
            'minimum_cost' => $this->shippingConfig['minimum_cost'],
            'final_cost' => $finalCost,
            'subtotal' => $this->subtotal
        ]);
        
        return $finalCost;
    }

    /**
     * PERBAIKAN: Tambah method calculateShippingCost yang hilang
     */
    public function calculateShippingCost()
    {
        if (!$this->userLatitude || !$this->userLongitude) {
            Log::warning('Cannot calculate shipping: coordinates not set');
            return;
        }

        $this->isCalculatingShipping = true;

        try {
            // Calculate distance
            $distance = $this->calculateStraightLineDistance();
            
            if ($distance === null) {
                throw new \Exception('Failed to calculate distance');
            }

            $this->shippingDistance = $distance;
            $this->shippingCost = $this->calculateShippingRate($distance);
            
            $this->updateShippingInfo($distance);
            $this->calculateTotal();

            Log::info('Shipping cost calculated successfully', [
                'distance' => $this->shippingDistance,
                'cost' => $this->shippingCost
            ]);

        } catch (\Exception $e) {
            Log::error('Error calculating shipping cost: ' . $e->getMessage());
            $this->shippingDistance = 0;
            $this->shippingCost = 0;
            $this->shippingInfo = 'Error menghitung ongkos kirim';
        } finally {
            $this->isCalculatingShipping = false;
        }
    }

    /**
     * Method untuk menghitung jarak garis lurus (Haversine formula) - lebih reliable
     */
    private function calculateStraightLineDistance()
    {
        try {
            // Konversi derajat ke radian - SAMA dengan JavaScript
            $lat1 = deg2rad($this->adminCoordinates['lat']);
            $lon1 = deg2rad($this->adminCoordinates['lon']);
            $lat2 = deg2rad($this->userLatitude);
            $lon2 = deg2rad($this->userLongitude);

            $dlat = $lat2 - $lat1;
            $dlon = $lon2 - $lon1;

            // Haversine formula - SAMA dengan JavaScript
            $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            
            $earthRadius = 6371; // Earth radius in kilometers - SAMA dengan JavaScript
            $distance = $earthRadius * $c;
            
            // Tambahkan faktor koreksi - SAMA dengan JavaScript (30% lebih jauh)
            $adjustedDistance = $distance * 1.3;
            
            Log::info('Distance calculation details', [
                'admin_coords' => $this->adminCoordinates,
                'user_coords' => ['lat' => $this->userLatitude, 'lng' => $this->userLongitude],
                'straight_line' => $distance,
                'adjusted' => $adjustedDistance
            ]);
            
            return $adjustedDistance;
            
        } catch (\Exception $e) {
            Log::error('Error calculating straight line distance: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Method untuk update info shipping
     */
    private function updateShippingInfo($distanceKm)
    {
        $isFree = $this->subtotal >= $this->shippingConfig['free_shipping_threshold'];
        
        if ($isFree) {
            $this->shippingInfo = 'Gratis ongkir (belanja minimal Rp ' . number_format($this->shippingConfig['free_shipping_threshold']) . ')';
        } else {
            $this->shippingInfo = 'Jarak: ' . number_format($distanceKm, 1) . ' km • Tarif: Rp ' . number_format($this->shippingConfig['rate_per_km']) . '/km';
        }
    }

    /**
     * Method untuk reset shipping cost
     */
    private function resetShippingCost()
    {
        $this->shippingCost = 0;
        $this->shippingDistance = 0;
        $this->shippingInfo = '';
        $this->calculateTotal();
        
        // Dispatch event untuk update UI
        $this->dispatch('shippingReset');
    }

    /**
     * Method untuk recalculate shipping secara manual
     */
    public function recalculateShipping()
    {
        Log::info('Manual recalculate shipping triggered');
        
        if ($this->userLatitude && $this->userLongitude && 
            $this->selected_provinsi && $this->selected_kabupaten && $this->selected_kecamatan) {
            
            $this->calculateShippingCost();
        } else {
            $this->dispatch('showAlert', [
                'message' => 'Silakan lengkapi data area dan pilih lokasi pada peta terlebih dahulu.',
                'type' => 'warning'
            ]);
        }
    }

    public function loadCarts()
    {
        try {
            $this->carts = Cart::where('user_id', auth()->id())
                ->with('product')
                ->get();

            $this->calculateTotal();
        } catch (\Exception $e) {
            Log::error('Error loading carts: ' . $e->getMessage());
            $this->carts = collect();
        }
    }

    public function calculateTotal()
    {
        // Hitung subtotal dari produk
        $this->subtotal = 0;
        foreach ($this->carts as $cart) {
            if ($cart->product) { // PERBAIKAN: Tambah pengecekan product exists
                $this->subtotal += $cart->product->price * $cart->quantity;
            }
        }
        
        // Recalculate shipping cost jika subtotal berubah (untuk free shipping)
        if ($this->shippingDistance > 0) {
            $oldShippingCost = $this->shippingCost;
            $this->shippingCost = $this->calculateShippingRate($this->shippingDistance);
            $this->updateShippingInfo($this->shippingDistance);
            
            // Dispatch event jika ongkir berubah
            if ($oldShippingCost != $this->shippingCost) {
                $this->dispatch('shippingUpdated', [
                    'cost' => $this->shippingCost,
                    'info' => $this->shippingInfo
                ]);
            }
        }
        
        // Total = subtotal + ongkos kirim
        $this->total = $this->subtotal + $this->shippingCost;
        
        Log::info('Total calculated', [
            'subtotal' => $this->subtotal,
            'shipping' => $this->shippingCost,
            'total' => $this->total
        ]);
    }

    /**
     * Method untuk mendapatkan info jarak (untuk debugging/display)
     */
    public function getDistanceInfo()
    {
        if ($this->shippingDistance > 0) {
            return [
                'distance' => number_format($this->shippingDistance, 1) . ' km',
                'cost' => 'Rp ' . number_format($this->shippingCost),
                'info' => $this->shippingInfo,
                'is_free' => $this->shippingCost == 0 && $this->shippingDistance > 0
            ];
        }
        return null;
    }

    protected function mapCustomOptionsToNames(array $customOptions): array
    {
        $names = [];

        try {
            foreach ($customOptions as $optionTypeId => $optionItemId) {
                if (is_array($optionItemId)) {
                    foreach ($optionItemId as $id) {
                        $item = ProductOptionItem::find($id);
                        if ($item) {
                            $names[] = $item->name;
                        }
                    }
                } else {
                    $item = ProductOptionItem::find($optionItemId);
                    if ($item) {
                        $names[] = $item->name;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error mapping custom options: ' . $e->getMessage());
        }

        return $names;
    }

    protected function getAllCartCustomOptionsJson(): string
    {
        $allNames = [];

        try {
            foreach ($this->carts as $cart) {
                $customOptions = json_decode($cart->custom_options_json, true);
                if (is_array($customOptions) && !empty($customOptions)) {
                    $names = $this->mapCustomOptionsToNames($customOptions);
                    $allNames = array_merge($allNames, $names);
                }
            }

            $allNames = array_values(array_unique($allNames));
        } catch (\Exception $e) {
            Log::error('Error getting cart custom options: ' . $e->getMessage());
            $allNames = [];
        }

        return json_encode($allNames);
    }

    public function render()
    {
        if ($this->carts->isEmpty()) {
            return redirect()->route('home');
        }
        
        return view('livewire.checkout', [
            'distanceInfo' => $this->getDistanceInfo(),
            'adminCoordinates' => $this->adminCoordinates,
            'shippingConfig' => $this->shippingConfig
        ])->layout('components.layouts.app', ['hideBottomNav' => true]);
    }

    public function createOrder()
    {
        if (!$this->carts->isEmpty()) {
            // PERBAIKAN: Gunakan database transaction untuk keamanan
            DB::beginTransaction();
            
            try {
                // Validasi form terlebih dahulu
                $validatedData = $this->validate();
                
                Log::info('Creating order with data', [
                    'user_id' => auth()->id(),
                    'selected_area' => [
                        'provinsi' => $this->selected_provinsi,
                        'kabupaten' => $this->selected_kabupaten,
                        'kecamatan' => $this->selected_kecamatan
                    ],
                    'coordinates' => [$this->userLatitude, $this->userLongitude],
                    'shipping_distance' => $this->shippingDistance,
                    'shipping_cost' => $this->shippingCost,
                    'total' => $this->total
                ]);
                
                // Validasi area tersedia dengan kecamatan
                $deliveryArea = DeliveryArea::active()
                    ->where('provinsi_id', $this->selected_provinsi)
                    ->where('kabupaten_id', $this->selected_kabupaten)
                    ->where('kecamatan_id', $this->selected_kecamatan)
                    ->first();

                if (!$deliveryArea) {
                    throw new \Exception('Wilayah yang dipilih tidak tersedia untuk layanan pengiriman');
                }

                // Validasi koordinat
                if (!$this->userLatitude || !$this->userLongitude) {
                    throw new \Exception('Koordinat lokasi pengiriman belum dipilih');
                }

                // Validasi jarak maksimal
                if (isset($this->shippingConfig['maximum_distance']) && 
                    $this->shippingDistance > $this->shippingConfig['maximum_distance']) {
                    throw new \Exception('Lokasi pengiriman terlalu jauh (maksimal ' . $this->shippingConfig['maximum_distance'] . ' km)');
                }

                // Pastikan ongkos kirim sudah dihitung
                if ($this->shippingDistance == 0) {
                    $this->calculateShippingCost();
                    if ($this->shippingDistance == 0) {
                        throw new \Exception('Gagal menghitung ongkos kirim');
                    }
                }

                // Validasi cart items masih ada dan valid
                $currentCarts = Cart::where('user_id', auth()->id())
                    ->with('product')
                    ->get();
                
                if ($currentCarts->isEmpty()) {
                    throw new \Exception('Keranjang belanja kosong');
                }

                // Validasi semua produk masih tersedia
                foreach ($currentCarts as $cart) {
                    if (!$cart->product) {
                        throw new \Exception('Produk dalam keranjang tidak valid');
                    }
                }

                $customOptionsJson = $this->getAllCartCustomOptionsJson();

                // Generate order number yang unique
                do {
                    $orderNumber = 'INV-' . strtoupper(uniqid());
                } while (Order::where('order_number', $orderNumber)->exists());

                // PERBAIKAN: Pastikan semua field yang required ada
                $orderData = [
                    'user_id' => auth()->id(),
                    'order_number' => $orderNumber,
                    'subtotal' => $this->subtotal,
                    'shipping_cost' => $this->shippingCost,
                    'total_amount' => $this->total,
                    'status' => 'checking',
                    'payment_status' => 'unpaid',
                    'recipient_name' => $this->shippingData['recipient_name'],
                    'phone' => $this->shippingData['phone'],
                    'shipping_address' => $this->shippingData['shipping_address'],
                    'noted' => $this->shippingData['noted'] ?? '',
                    'delivery_date' => $this->shippingData['delivery_date'],
                    'delivery_time' => $this->shippingData['delivery_time'],
                    'is_custom_catering' => $this->isCustomCatering,
                    'provinsi_id' => $this->selected_provinsi,
                    'kabupaten_id' => $this->selected_kabupaten,
                    'kecamatan_id' => $this->selected_kecamatan,
                    'provinsi_name' => $deliveryArea->provinsi_name,
                    'kabupaten_name' => $deliveryArea->kabupaten_name,
                    'kecamatan_name' => $deliveryArea->kecamatan_name,
                    'custom_options_json' => $customOptionsJson,
                    // Field koordinat dan shipping info - dikomentar karena belum ada di database
                    // 'user_latitude' => $this->userLatitude,
                    // 'user_longitude' => $this->userLongitude,
                    // 'map_selected_address' => $this->mapSelectedAddress ?? '',
                    // 'shipping_distance' => $this->shippingDistance,
                    // 'shipping_info' => $this->shippingInfo,
                ];

                Log::info('Creating order with data:', $orderData);

                $order = Order::create($orderData);

                if (!$order) {
                    throw new \Exception('Gagal membuat order');
                }

                // Simpan order items
                foreach ($currentCarts as $cart) {
                    $customOptions = json_decode($cart->custom_options_json, true);
                    $names = [];
                    if (is_array($customOptions) && !empty($customOptions)) {
                        $names = $this->mapCustomOptionsToNames($customOptions);
                    }

                    $orderItem = $order->items()->create([
                        'product_id' => $cart->product_id,
                        'product_name' => $cart->product->name,
                        'product_description' => $cart->product->description ?? '',
                        'quantity' => $cart->quantity,
                        'price' => $cart->product->price,
                        'custom_options_json' => json_encode($names),
                    ]);

                    if (!$orderItem) {
                        throw new \Exception('Gagal menyimpan item pesanan');
                    }
                }

                // Simpan custom catering jika ada
                if ($this->isCustomCatering && !empty($this->customCatering['menu_description'])) {
                    $customCatering = $order->customCatering()->create([
                        'menu_description' => $this->customCatering['menu_description'],
                    ]);

                    if (!$customCatering) {
                        throw new \Exception('Gagal menyimpan data custom catering');
                    }
                }

                // Commit transaction sebelum hapus cart
                DB::commit();

                // Hapus cart setelah order berhasil (diluar transaction)
                Cart::where('user_id', auth()->id())->delete();

                // Kirim notifikasi
                try {
                    if ($this->store && $this->store->email_notification) {
                        Notification::route('mail', $this->store->email_notification)
                            ->notify(new NewOrderNotification($order));
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send order notification: ' . $e->getMessage());
                    // Jangan throw error karena order sudah berhasil dibuat
                }

                Log::info('Order created successfully', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => auth()->id()
                ]);

                // Redirect ke halaman detail order
                return redirect()->route('order-detail', ['orderNumber' => $order->order_number]);

            } catch (\Illuminate\Validation\ValidationException $e) {
                DB::rollback();
                
                Log::error('Validation error during checkout', [
                    'user_id' => auth()->id(),
                    'errors' => $e->errors()
                ]);
                
                // Validation errors akan ditangani otomatis oleh Livewire
                throw $e;
                
            } catch (\Exception $e) {
                DB::rollback();
                
                Log::error('Checkout error: ' . $e->getMessage(), [
                    'user_id' => auth()->id(),
                    'coordinates' => [$this->userLatitude, $this->userLongitude],
                    'shipping_data' => $this->shippingData,
                    'selected_area' => [
                        'provinsi' => $this->selected_provinsi,
                        'kabupaten' => $this->selected_kabupaten,
                        'kecamatan' => $this->selected_kecamatan
                    ],
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->dispatch('showAlert', [
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
                
                return;
            }
        } else {
            $this->dispatch('showAlert', [
                'message' => 'Keranjang belanja kosong',
                'type' => 'error'
            ]);
        }
    }
}