<div class="max-w-[480px] mx-auto bg-white min-h-screen relative shadow-lg">
  <!-- Header -->
  <div class="sticky top-0 bg-white z-50 border-b border-gray-100">
    <div class="flex items-center h-16 px-4">
      <button onclick="history.back()" class="p-2 hover:bg-gray-50 rounded-full">
        <i class="bi bi-arrow-left text-xl"></i>
      </button>
      <h1 class="ml-2 text-lg font-medium">Checkout</h1>
    </div>
  </div>

  <!-- Main Content -->
  <div class="p-4 space-y-8 pb-32">
    <!-- Section 1: Order Summary -->
    <div>
      <div class="flex items-center gap-2 mb-4">
        <i class="bi bi-cart-check text-lg text-primary"></i>
        <h2 class="text-lg font-medium">Ringkasan Pesanan</h2>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="space-y-4">
          @foreach($carts as $cart)
            <div class="flex gap-3">
              <img src="{{$cart->product->first_image_url ?? asset('image/no-pictures.png')}}" 
                   alt="{{$cart->product->name}}" 
                   class="w-20 h-20 object-cover rounded-lg">
              <div class="flex-1">
                <h3 class="text-sm font-medium line-clamp-2">{{$cart->product->name}}</h3>
                <div class="text-sm text-gray-500 mt-1">{{$cart->quantity}} x Rp {{number_format($cart->product->price)}}</div>
                <div class="text-primary font-medium">Rp {{number_format($cart->product->price * $cart->quantity)}}</div>
              </div>
            </div>
          @endforeach
          <div class="border-t pt-3 space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Subtotal:</span>
              <span class="font-medium" id="subtotal-amount">Rp {{number_format($subtotal)}}</span>
            </div>
            <div class="flex justify-between text-sm" id="shipping-cost-display" style="display: {{$shippingCost > 0 ? 'flex' : 'none'}};">
              <span class="text-gray-600">Ongkos Kirim:</span>
              <span class="font-medium text-green-600" id="shipping-amount">Rp {{number_format($shippingCost)}}</span>
            </div>
            <div class="flex justify-between text-sm" id="shipping-loading" style="display: none;">
              <span class="text-gray-600">Ongkos Kirim:</span>
              <span class="text-gray-500">
                <i class="bi bi-hourglass-split animate-spin mr-1"></i>Menghitung...
              </span>
            </div>
            <div class="flex justify-between text-base font-semibold border-t pt-2">
              <span>Total:</span>
              <span class="text-primary" id="total-amount">Rp {{number_format($total)}}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: Recipient Information -->
    <div>
      <div class="flex items-center gap-2 mb-4">
        <i class="bi bi-person text-lg text-primary"></i>
        <h2 class="text-lg font-medium">Data Penerima</h2>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 p-4 space-y-4">
        <div>
          <label class="text-sm text-gray-600 mb-1.5 block">
            Nama Lengkap <span class="text-red-500">*</span>
          </label>
          <input type="text" 
                 wire:model.live="shippingData.recipient_name"
                 class="w-full px-4 py-2 rounded-lg border @error('shippingData.recipient_name') border-red-500 focus:ring-red-500 focus:border-red-500 @else focus:ring-2 focus:ring-primary focus:border-primary @enderror"
                 placeholder="Masukkan nama lengkap penerima"
                 required>
          @error('shippingData.recipient_name')
            <span class="text-red-500 text-xs mt-1 block">{{$message}}</span>
          @enderror
        </div>
        <div>
          <label class="text-sm text-gray-600 mb-1.5 block">
            Nomor Telepon <span class="text-red-500">*</span>
          </label>
          <input wire:model.live="shippingData.phone"   
                 type="tel" 
                 class="w-full px-4 py-2 rounded-lg border @error('shippingData.phone') border-red-300 focus:ring-red-500 focus:border-red-500 @else focus:ring-2 focus:ring-primary focus:border-primary @enderror"
                 placeholder="Contoh: 08123456789"
                 required>
          @error('shippingData.phone')
            <span class="text-red-500 text-xs mt-1 block">{{$message}}</span>
          @enderror
        </div>
      </div>
    </div>

    <!-- Section 3: Location Selection -->
    <div>
      <div class="flex items-center gap-2 mb-4">
        <i class="bi bi-geo-alt text-lg text-primary"></i>
        <h2 class="text-lg font-medium">Pilih Lokasi</h2>
      </div>
      <div class="mb-2 px-2 py-2 bg-blue-50 border-l-4 border-blue-400 text-blue-800 rounded text-sm">
        <i class="bi bi-info-circle mr-1"></i>
        Pilih wilayah untuk menampilkan peta, kemudian tentukan lokasi dengan pin untuk menghitung ongkos kirim.
      </div>
      <div class="bg-white rounded-xl border border-gray-100 p-4 space-y-4">
        <div>
          <label for="provinsi" class="block text-sm text-gray-600 mb-1.5">
            Provinsi <span class="text-red-500">*</span>
          </label>
          <select id="provinsi" wire:model.live="selected_provinsi"
                  class="w-full px-4 py-2 rounded-lg border @error('selected_provinsi') border-red-300 focus:ring-red-500 focus:border-red-500 @else focus:ring-2 focus:ring-primary focus:border-primary @enderror bg-white"
                  required>
            <option value="">Pilih Provinsi</option>
            @foreach($availableProvinsis as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
          </select>
          @error('selected_provinsi')
            <span class="text-red-500 text-sm mt-1 block">
              <i class="bi bi-exclamation-circle mr-1"></i>{{ $message }}
            </span>
          @enderror
        </div>
        <div>
          <label for="kabupaten" class="block text-sm text-gray-600 mb-1.5">
            Kabupaten/Kota <span class="text-red-500">*</span>
          </label>
          <select id="kabupaten" wire:model.live="selected_kabupaten"
                  class="w-full px-4 py-2 rounded-lg border @error('selected_kabupaten') border-red-300 focus:ring-red-500 focus:border-red-500 @else focus:ring-2 focus:ring-primary focus:border-primary @enderror bg-white"
                  {{ empty($availableKabupatens) ? 'disabled' : '' }}
                  required>
            <option value="">{{ empty($availableKabupatens) ? 'Pilih provinsi terlebih dahulu' : 'Pilih Kabupaten/Kota' }}</option>
            @foreach($availableKabupatens as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
          </select>
          @error('selected_kabupaten')
            <span class="text-red-500 text-sm mt-1 block">
              <i class="bi bi-exclamation-circle mr-1"></i>{{ $message }}
            </span>
          @enderror
        </div>
        @if(!empty($availableKecamatans))
          <div>
            <label for="kecamatan" class="block text-sm text-gray-600 mb-1.5">
              Kecamatan <span class="text-red-500">*</span>
            </label>
            <select id="kecamatan" wire:model.live="selected_kecamatan"
                    class="w-full px-4 py-2 rounded-lg border @error('selected_kecamatan') border-red-300 focus:ring-red-500 focus:border-red-500 @else focus:ring-2 focus:ring-primary focus:border-primary @enderror bg-white"
                    required>
              <option value="">Pilih Kecamatan</option>
              @foreach($availableKecamatans as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
              @endforeach
            </select>
            @error('selected_kecamatan')
              <span class="text-red-500 text-sm mt-1 block">
                <i class="bi bi-exclamation-circle mr-1"></i>{{ $message }}
              </span>
            @enderror
          </div>
        @elseif(!empty($selected_kabupaten))
          <div>
            <label class="block text-sm text-gray-600 mb-1.5">
              Kecamatan <span class="text-red-500">*</span>
            </label>
            <div class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 text-gray-500 flex items-center">
              <i class="bi bi-hourglass-split animate-spin mr-2"></i>
              Memuat data kecamatan...
            </div>
          </div>
        @endif
      </div>
    </div>

    <!-- Section 4: Map Picker -->
    <div>
      <div class="flex items-center gap-2 mb-4">
        <i class="bi bi-map text-lg text-primary"></i>
        <h2 class="text-lg font-medium">Pilih Lokasi pada Peta</h2>
      </div>
      @if(!empty($selected_provinsi) && !empty($selected_kabupaten) && !empty($selected_kecamatan))
        <div class="bg-white rounded-xl border border-gray-100 p-4">
          <div class="mb-3 p-3 bg-orange-50 border border-orange-200 rounded-lg">
            <div class="text-sm text-orange-700 font-medium">
              <i class="bi bi-pin-map mr-1"></i>Tentukan Lokasi Pengiriman
            </div>
            <div class="text-xs text-orange-600 mt-1">
              Klik atau drag pin pada peta. Ongkos kirim akan dihitung berdasarkan jarak dari toko.
            </div>
          </div>
          <div class="mb-3 flex gap-2">
            <button onclick="getCurrentLocation()" class="px-3 py-2 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 flex items-center gap-1">
              <i class="bi bi-geo-alt-fill"></i>
              Gunakan Lokasi Saya
            </button>
            <button onclick="refreshMap()" class="px-3 py-2 bg-gray-500 text-white text-sm rounded-lg hover:bg-gray-600 flex items-center gap-1">
              <i class="bi bi-arrow-clockwise"></i>
              Refresh Peta
            </button>
          </div>
          <div class="relative">
            <div id="map" style="height: 300px;" class="rounded-lg"></div>
          </div>
          <div id="location-info-container" style="display: none;">
            <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
              <div class="text-sm text-green-700 font-medium mb-1">
                <i class="bi bi-check-circle mr-1"></i>Lokasi Terpilih:
              </div>
              <div class="text-sm text-green-600" id="coordinates-display">
                <i class="bi bi-pin-map mr-1"></i>Koordinat: -
              </div>
              <div class="text-sm text-green-600 mt-1" id="address-display">
                <i class="bi bi-house mr-1"></i>Alamat: -
              </div>
              <div class="text-sm text-green-600 mt-1" id="distance-display">
                <i class="bi bi-ruler mr-1"></i>Jarak: - km
              </div>
              <div class="text-sm text-green-600 mt-1" id="shipping-display">
                <i class="bi bi-truck mr-1"></i>Ongkos Kirim: Rp -
              </div>
            </div>
          </div>
        </div>
      @else
        <div class="bg-white rounded-xl border border-gray-100 p-4">
          <div class="text-center py-8 text-gray-500">
            <i class="bi bi-map text-4xl mb-3"></i>
            <div class="text-lg font-medium mb-2">Peta Lokasi Pengiriman untuk menghitung ongkir otomatis</div>
            <div class="text-sm">Pilih provinsi, kabupaten, dan kecamatan terlebih dahulu</div>
          </div>
        </div>
      @endif
    </div>

    <!-- Section 5: Shipping Address -->
    <div>
      <div class="flex items-center gap-2 mb-4">
        <i class="bi bi-house text-lg text-primary"></i>
        <h2 class="text-lg font-medium">Detail Alamat</h2>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div>
          <label class="text-sm text-gray-600 mb-1.5 block">
            Detail Alamat <span class="text-red-500">*</span>
          </label>
          <textarea wire:model.live="shippingData.shipping_address"
                    class="w-full px-4 py-2 rounded-lg border @error('shippingData.shipping_address') border-red-300 focus:ring-red-500 focus:border-red-500 @else focus:ring-2 focus:ring-primary focus:border-primary @enderror"
                    rows="3"
                    placeholder="Nama jalan, nomor rumah (patokan), RT/RW, Desa/Kelurahan"
                    required></textarea>
          @error('shippingData.shipping_address')
            <span class="text-red-500 text-xs mt-1 block">{{$message}}</span>
          @enderror
        </div>
      </div>
    </div>

    <!-- Section 6: Additional Notes -->
    <div>
      <div class="flex items-center gap-2 mb-4">
        <i class="bi bi-pencil text-lg text-primary"></i>
        <h2 class="text-lg font-medium">Catatan Tambahan</h2>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 p-4">
        <textarea wire:model.live="shippingData.noted"
                  class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-primary focus:border-primary"
                  rows="2"
                  placeholder="Catatan untuk kurir (opsional)"></textarea>
      </div>
    </div>

    <!-- Section 7: Event Schedule -->
    <div>
      <div class="flex items-center gap-2 mb-4">
        <i class="bi bi-clock text-lg text-primary"></i>
        <h2 class="text-lg font-medium">Tanggal Acara</h2>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 p-4 space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm text-gray-600 mb-1.5 block">
              Tanggal <span class="text-red-500">*</span>
            </label>
            <input type="date" wire:model.live="shippingData.delivery_date"
                 class="w-full border @error('shippingData.delivery_date') border-red-300 focus:ring-red-500 focus:border-red-500 @else focus:ring-2 focus:ring-primary focus:border-primary @enderror rounded-lg px-3 py-2 text-sm focus:outline-none"
                 required />
          </div>
          <div>
            <label class="text-sm text-gray-600 mb-1.5 block">
              Waktu <span class="text-red-500">*</span>
            </label>
            <input type="time" wire:model.live="shippingData.delivery_time"
                   class="w-full border @error('shippingData.delivery_time') border-red-300 focus:ring-red-500 focus:border-red-500 @else focus:ring-2 focus:ring-primary focus:border-primary @enderror rounded-lg px-3 py-2 text-sm focus:outline-none"
                   required />
          </div>
        </div>
        <div>
          <div class="flex items-center">
            <input wire:model.live="isCustomCatering" type="checkbox" id="customCatering" 
                   class="mr-2 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" />
            <label for="customCatering" class="font-medium">Custom Pesanan</label>
          </div>
        </div>
        @if ($isCustomCatering)
          <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <label for="menu_description" class="text-sm text-gray-600 mb-1.5 block">
              Deskripsikan menu yang anda inginkan <span class="text-red-500">*</span>
            </label>
            <textarea id="menu_description" wire:model.live="customCatering.menu_description"
                      class="w-full px-4 py-2 rounded-lg border @error('customCatering.menu_description') border-red-300 focus:ring-red-500 focus:border-red-500 @else focus:ring-2 focus:ring-primary focus:border-primary @enderror"
                      rows="3"
                      placeholder="Jelaskan menu custom yang Anda inginkan secara detail..."
                      required></textarea>
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Simple Bottom Button -->
  <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[480px] bg-white border-t border-gray-100 p-4 z-50">
    <div class="mb-3 text-center">
      <p class="text-lg font-semibold text-primary" id="final-total">Total: Rp {{number_format($total)}}</p>
      <p class="text-sm text-gray-600">{{count($carts)}} Menu</p>
    </div>
    
    @if ($errors->any())
      <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg">
        <div class="text-sm text-red-700">
          <i class="bi bi-exclamation-triangle mr-1"></i>
          Mohon lengkapi semua field yang wajib diisi.
        </div>
      </div>
    @endif

    <button wire:click="createOrder" 
            class="w-full h-12 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors flex items-center justify-center gap-2"
            wire:target="createOrder"
            wire:loading.attr="disabled">
      <span wire:loading.remove wire:target="createOrder">
        <i class="bi bi-bag-check"></i>
        Buat Pesanan
      </span>
      <span wire:loading wire:target="createOrder">
        <i class="bi bi-hourglass-split animate-spin"></i>
        Memproses...
      </span>
    </button>
  </div>
</div>
<script>
    // Global variables untuk data lokasi yang persistent
    window.locationData = {
        latitude: null,
        longitude: null,
        address: null,
        distance: null,
        shipping_cost: null,
        subtotal: 0
    };

    let map;
    let marker;
    let storeMarker;
    let isMapInitialized = false;
    let mapCheckInterval;
    let isUpdatingLocation = false; // Prevent multiple simultaneous updates
    
    const defaultLat = -6.8693;
    const defaultLon = 109.1402;
    
    const storeLocation = {
        lat: -6.861722,    
        lng: 109.1334094, 
        name: "Bintang Rasa Catering Tegal"
    };

    // Initialize subtotal and restore location from backup
    function initializeSubtotal() {
        try {
            const subtotalElement = document.getElementById('subtotal-amount');
            if (subtotalElement) {
                const subtotalText = subtotalElement.textContent || 'Rp 0';
                const subtotal = parseInt(subtotalText.replace(/[^\d]/g, '')) || 0;
                window.locationData.subtotal = subtotal;
                console.log('Subtotal initialized:', subtotal);
            }
            
            // Try to restore location from localStorage backup
            try {
                const backup = localStorage.getItem('checkoutLocation');
                if (backup) {
                    const locationBackup = JSON.parse(backup);
                    // Check if backup is recent (less than 1 hour old)
                    if (Date.now() - locationBackup.timestamp < 3600000) {
                        console.log('📱 Restoring location from backup:', locationBackup);
                        window.locationData.latitude = locationBackup.lat;
                        window.locationData.longitude = locationBackup.lng;
                        window.locationData.address = locationBackup.address;
                        window.locationData.distance = locationBackup.distance;
                        window.locationData.shipping_cost = locationBackup.shipping_cost;
                        
                        // Update displays immediately
                        updateLocationDisplay();
                        
                        // Trigger map restore when ready
                        setTimeout(() => {
                            if (map && marker && isMapInitialized) {
                                restoreLocationFromData();
                            }
                        }, 1000);
                    }
                }
            } catch (error) {
                console.log('⚠️ Could not restore from backup:', error);
            }
            
        } catch (error) {
            console.error('Error initializing subtotal:', error);
        }
    }

    // Calculate distance PERSIS SAMA dengan PHP
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const lat1Rad = lat1 * Math.PI / 180;
        const lon1Rad = lon1 * Math.PI / 180;
        const lat2Rad = lat2 * Math.PI / 180;
        const lon2Rad = lon2 * Math.PI / 180;

        const dlat = lat2Rad - lat1Rad;
        const dlon = lon2Rad - lon1Rad;

        const a = Math.sin(dlat/2) * Math.sin(dlat/2) + 
                  Math.cos(lat1Rad) * Math.cos(lat2Rad) * 
                  Math.sin(dlon/2) * Math.sin(dlon/2);
                  
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        
        const earthRadius = 6371;
        const distance = earthRadius * c;
        const adjustedDistance = distance * 1.3;
        
        console.log('Distance calculation:', {
            straightLine: distance,
            adjusted: adjustedDistance,
            coordinates: { lat1, lon1, lat2, lon2 }
        });
        
        return adjustedDistance;
    }

    // Calculate shipping cost SAMA PERSIS dengan PHP
    function calculateShippingCostByDistance(distance, subtotal) {
        const config = {
            rate_per_km: 2000,
            minimum_cost: 5000,
            free_shipping_threshold: 1000000
        };
        
        if (subtotal >= config.free_shipping_threshold) {
            console.log('Free shipping applied', { subtotal, threshold: config.free_shipping_threshold });
            return 0;
        }
        
        const calculatedCost = Math.ceil(distance) * config.rate_per_km;
        const finalCost = Math.max(calculatedCost, config.minimum_cost);
        
        console.log('Shipping cost calculation:', {
            distance: distance,
            distance_ceil: Math.ceil(distance),
            rate_per_km: config.rate_per_km,
            calculated_cost: calculatedCost,
            minimum_cost: config.minimum_cost,
            final_cost: finalCost,
            subtotal: subtotal
        });
        
        return finalCost;
    }

    // Update all UI elements with current location data
    function updateLocationDisplay() {
        const data = window.locationData;
        
        const coordinatesEl = document.getElementById('coordinates-display');
        if (coordinatesEl && data.latitude && data.longitude) {
            coordinatesEl.innerHTML = `<i class="bi bi-pin-map mr-1"></i>Koordinat: ${data.latitude.toFixed(6)}, ${data.longitude.toFixed(6)}`;
        }
        
        const addressEl = document.getElementById('address-display');
        if (addressEl) {
            addressEl.innerHTML = `<i class="bi bi-house mr-1"></i>Alamat: ${data.address || 'Alamat tidak diketahui'}`;
        }
        
        const distanceEl = document.getElementById('distance-display');
        if (distanceEl && data.distance) {
            distanceEl.innerHTML = `<i class="bi bi-ruler mr-1"></i>Jarak: ${data.distance.toFixed(1)} km`;
        }
        
        const shippingEl = document.getElementById('shipping-display');
        if (shippingEl && data.shipping_cost !== null) {
            const isFree = data.shipping_cost === 0 && data.distance > 0;
            const shippingText = isFree ? 'GRATIS' : `Rp ${data.shipping_cost.toLocaleString('id-ID')}`;
            shippingEl.innerHTML = `<i class="bi bi-truck mr-1"></i>Ongkos Kirim: ${shippingText}`;
        }
        
        const containerEl = document.getElementById('location-info-container');
        if (containerEl && data.latitude && data.longitude) {
            containerEl.style.display = 'block';
        }
        
        updatePricingDisplay();
    }

    // Update pricing display in order summary
    function updatePricingDisplay() {
        const data = window.locationData;
        
        const loadingEl = document.getElementById('shipping-loading');
        const displayEl = document.getElementById('shipping-cost-display');
        
        if (data.shipping_cost !== null) {
            if (loadingEl) loadingEl.style.display = 'none';
            
            if (data.shipping_cost > 0) {
                if (displayEl) displayEl.style.display = 'flex';
                
                const shippingAmountEl = document.getElementById('shipping-amount');
                if (shippingAmountEl) {
                    shippingAmountEl.textContent = 'Rp ' + data.shipping_cost.toLocaleString('id-ID');
                }
            } else {
                if (displayEl) {
                    displayEl.style.display = 'flex';
                    const shippingAmountEl = document.getElementById('shipping-amount');
                    if (shippingAmountEl) {
                        shippingAmountEl.textContent = 'GRATIS';
                        shippingAmountEl.className = 'font-medium text-green-600';
                    }
                }
            }
            
            const total = data.subtotal + data.shipping_cost;
            const totalAmountEl = document.getElementById('total-amount');
            const finalTotalEl = document.getElementById('final-total');
            
            if (totalAmountEl) {
                totalAmountEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
            }
            if (finalTotalEl) {
                finalTotalEl.textContent = 'Total: Rp ' + total.toLocaleString('id-ID');
            }
        } else {
            if (loadingEl) loadingEl.style.display = 'none';
            if (displayEl) displayEl.style.display = 'none';
            
            const totalAmountEl = document.getElementById('total-amount');
            const finalTotalEl = document.getElementById('final-total');
            
            if (totalAmountEl) {
                totalAmountEl.textContent = 'Rp ' + data.subtotal.toLocaleString('id-ID');
            }
            if (finalTotalEl) {
                finalTotalEl.textContent = 'Total: Rp ' + data.subtotal.toLocaleString('id-ID');
            }
        }
    }

    // Show loading state
    function showLoadingState() {
        const loadingEl = document.getElementById('shipping-loading');
        const displayEl = document.getElementById('shipping-cost-display');
        if (loadingEl) loadingEl.style.display = 'flex';
        if (displayEl) displayEl.style.display = 'none';
    }

    // Save location data and dispatch to Livewire
    function saveLocationData(lat, lng, address, distance, shippingCost) {
        window.locationData.latitude = lat;
        window.locationData.longitude = lng;
        window.locationData.address = address;
        window.locationData.distance = distance;
        window.locationData.shipping_cost = shippingCost;
        
        // Save to localStorage as backup (if available)
        try {
            const locationBackup = {
                lat: lat,
                lng: lng,
                address: address,
                distance: distance,
                shipping_cost: shippingCost,
                timestamp: Date.now()
            };
            localStorage.setItem('checkoutLocation', JSON.stringify(locationBackup));
            console.log('💾 Location backed up to localStorage');
        } catch (error) {
            console.log('⚠️ LocalStorage not available');
        }
        
        updateLocationDisplay();
        dispatchToLivewire(lat, lng, address, distance, shippingCost);
        
        console.log('✅ Location data saved:', { lat, lng, distance, shippingCost });
    }

    // Dispatch data to Livewire dengan parameter lengkap
    function dispatchToLivewire(lat, lng, address, distance, shippingCost) {
        console.log('Dispatching to Livewire:', { lat, lng, address, distance, shippingCost });
        
        try {
            const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
            if (component) {
                component.call('updateCoordinates', lat, lng, address, distance, shippingCost);
                console.log('✅ Livewire direct call successful');
                return;
            }
        } catch (error) {
            console.log('❌ Livewire direct call failed:', error);
        }

        try {
            if (window.Livewire && window.Livewire.dispatch) {
                window.Livewire.dispatch('coordinates-updated', {
                    latitude: lat,
                    longitude: lng,
                    address: address,
                    distance: distance,
                    shipping_cost: shippingCost
                });
                console.log('✅ Livewire dispatch successful');
                return;
            }
        } catch (error) {
            console.log('❌ Livewire dispatch failed:', error);
        }

        try {
            const event = new CustomEvent('coordinates-updated', {
                detail: {
                    latitude: lat,
                    longitude: lng,
                    address: address,
                    distance: distance,
                    shipping_cost: shippingCost
                }
            });
            window.dispatchEvent(event);
            console.log('✅ Browser event dispatched');
        } catch (error) {
            console.log('❌ Browser event failed:', error);
        }
    }

    // =============== ENHANCED PIN MOVEMENT FIXES ===============
    
    // Check if map element is visible and ready
    function isMapElementReady() {
        const mapElement = document.getElementById('map');
        if (!mapElement) {
            console.log('Map element not found');
            return false;
        }
        
        const rect = mapElement.getBoundingClientRect();
        const isVisible = mapElement.offsetParent !== null && 
                         rect.width > 0 && 
                         rect.height > 0 &&
                         window.getComputedStyle(mapElement).display !== 'none';
        
        console.log('Map element check:', {
            exists: !!mapElement,
            offsetParent: !!mapElement.offsetParent,
            width: rect.width,
            height: rect.height,
            display: window.getComputedStyle(mapElement).display,
            isVisible: isVisible
        });
        
        return isVisible;
    }

    // Destroy existing map properly
    function destroyMap() {
        if (map) {
            console.log('🗑️ Destroying existing map');
            try {
                map.remove();
            } catch (error) {
                console.log('Error destroying map:', error);
            }
            map = null;
            marker = null;
            storeMarker = null;
            isMapInitialized = false;
        }
    }

    // Enhanced map initialization with better pin handling
    function initMap() {
        console.log('🗺️ Attempting to initialize map...');
        
        if (!isMapElementReady()) {
            console.log('❌ Map element not ready, scheduling retry...');
            setTimeout(() => initMap(), 500);
            return;
        }

        if (isMapInitialized && map) {
            console.log('♻️ Map already initialized, just refreshing size...');
            try {
                map.invalidateSize();
                return;
            } catch (error) {
                console.log('⚠️ Error refreshing map, reinitializing...', error);
                destroyMap();
            }
        }

        try {
            console.log('🚀 Creating new map instance...');
            
            // Destroy any existing map first
            destroyMap();
            
            // Wait a bit for DOM to be ready
            setTimeout(() => {
                if (!isMapElementReady()) {
                    console.log('❌ Map element still not ready after timeout');
                    return;
                }
                
                // Initialize map
                map = L.map('map', {
                    center: [defaultLat, defaultLon],
                    zoom: 13,
                    zoomControl: true,
                    preferCanvas: true,
                    // Ensure map responds to interaction properly
                    tap: true,
                    touchZoom: true,
                    boxZoom: true,
                    doubleClickZoom: true,
                    dragging: true
                });

                // Add tile layer with error handling
                const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 18,
                    errorTileUrl: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'
                });
                
                tileLayer.on('tileerror', function(error) {
                    console.log('Tile loading error:', error);
                });
                
                tileLayer.addTo(map);
                
                // Add store marker first
                storeMarker = L.marker([storeLocation.lat, storeLocation.lng], {
                    icon: L.divIcon({
                        html: `
                            <div style="
                                position: relative;
                                width: 30px;
                                height: 30px;
                                background: #dc2626;
                                border-radius: 50% 50% 50% 0;
                                transform: rotate(-45deg);
                                border: 3px solid white;
                                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                            ">
                                <div style="
                                    transform: rotate(45deg);
                                    width: 100%;
                                    height: 100%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 14px;
                                    color: white;
                                ">
                                    🏪
                                </div>
                            </div>
                        `,
                        iconSize: [30, 42],
                        iconAnchor: [15, 42],
                        className: 'custom-store-icon'
                    }),
                    title: 'Lokasi Bintang Rasa Catering',
                    zIndexOffset: 100 // Ensure store marker is above delivery marker
                }).addTo(map);
                
                storeMarker.bindPopup(`
                    <div class="text-sm text-center font-medium">
                        <div class="text-red-600 mb-1">🏪 ${storeLocation.name}</div>
                        <div class="text-gray-600 text-xs">Lokasi Toko/Dapur</div>
                    </div>
                `);
                
                // Add delivery marker with enhanced drag handling
                marker = L.marker([defaultLat, defaultLon], { 
                    draggable: true,
                    icon: L.icon({
                        iconUrl: 'https://maps.gstatic.com/mapfiles/api-3/images/spotlight-poi2.png',
                        iconSize: [27, 43],
                        iconAnchor: [13, 43],
                        popupAnchor: [0, -40]
                    }),
                    title: 'Drag untuk memindahkan lokasi pengiriman',
                    zIndexOffset: 200, // Ensure delivery marker is above store marker
                    riseOnHover: true
                }).addTo(map);
                
                marker.bindPopup(`
                    <div class="text-sm text-center">
                        <div class="font-medium text-blue-600 mb-1">📍 Lokasi Pengiriman</div>
                        <div class="text-xs text-gray-600">
                            Drag marker ini atau klik di peta<br>
                            untuk menentukan lokasi pengiriman
                        </div>
                    </div>
                `).openPopup();

                // Enhanced map click event with better pin movement
                map.on('click', function(e) {
                    if (isUpdatingLocation) {
                        console.log('⚠️ Location update in progress, ignoring click');
                        return;
                    }
                    
                    console.log('🖱️ Map clicked at:', e.latlng.lat, e.latlng.lng);
                    moveMarkerToLocation(e.latlng.lat, e.latlng.lng);
                });
                
                // Enhanced marker drag events
                marker.on('dragstart', function(e) {
                    console.log('🔄 Marker drag started');
                    marker.closePopup();
                    showLoadingState();
                    isUpdatingLocation = true;
                });
                
                marker.on('drag', function(e) {
                    // Optional: Update coordinates display in real-time during drag
                    const position = e.target.getLatLng();
                    console.log('🔄 Marker dragging to:', position.lat, position.lng);
                });
                
                marker.on('dragend', function(e) {
                    const position = e.target.getLatLng();
                    console.log('✅ Marker drag ended at:', position.lat, position.lng);
                    
                    // Use the enhanced movement function
                    setTimeout(() => {
                        updateMarkerPosition(position.lat, position.lng);
                    }, 100);
                });
                
                // Map ready event
                map.on('load', function() {
                    console.log('✅ Map loaded successfully');
                    isMapInitialized = true;
                    
                    // Restore saved location if exists
                    setTimeout(() => {
                        if (window.locationData.latitude && window.locationData.longitude) {
                            console.log('🔄 Restoring location immediately after map load');
                            restoreLocationFromData();
                        }
                    }, 100);
                });

                // Ensure proper map sizing
                setTimeout(() => {
                    if (map) {
                        console.log('🔄 Final map size invalidation');
                        map.invalidateSize();
                        isMapInitialized = true;
                        
                        // Additional check for saved location
                        if (window.locationData.latitude && window.locationData.longitude) {
                            restoreLocationFromData();
                        }
                    }
                }, 500);
                
                console.log('✅ Map initialization completed');
                
            }, 100);
            
        } catch (error) {
            console.error('❌ Error initializing map:', error);
            isMapInitialized = false;
            
            // Retry after a delay
            setTimeout(() => {
                console.log('🔄 Retrying map initialization...');
                initMap();
            }, 2000);
        }
    }

    // NEW: Enhanced function to move marker to specific location
    function moveMarkerToLocation(lat, lng) {
        if (!marker || !map || !isMapInitialized) {
            console.log('❌ Cannot move marker - map not ready');
            return;
        }

        if (isUpdatingLocation) {
            console.log('⚠️ Already updating location, skipping');
            return;
        }

        console.log('📍 Moving marker to:', lat, lng);
        
        isUpdatingLocation = true;
        
        // Immediate visual feedback - move the marker first
        marker.setLatLng([lat, lng]);
        
        // Center map on new location with smooth animation
        map.setView([lat, lng], Math.max(map.getZoom(), 15), {
            animate: true,
            duration: 0.5
        });
        
        // Show loading popup immediately
        marker.setPopupContent(`
            <div class="text-sm text-center">
                <div class="animate-pulse text-blue-600">⏳ Menghitung...</div>
                <div class="text-xs text-gray-600">
                    Mendapatkan alamat dan<br>
                    menghitung ongkos kirim...
                </div>
            </div>
        `).openPopup();
        
        showLoadingState();
        
        // Process the location update
        updateMarkerPosition(lat, lng);
    }

    // Enhanced restore location function with better pin positioning
    function restoreLocationFromData() {
        const data = window.locationData;
        if (data.latitude && data.longitude && marker && map && isMapInitialized) {
            console.log('🔄 Restoring saved location:', data.latitude, data.longitude);
            
            // Move marker to saved position with animation
            marker.setLatLng([data.latitude, data.longitude]);
            
            // Center map on saved location
            map.setView([data.latitude, data.longitude], 15, {
                animate: true,
                duration: 0.8
            });
            
            const isFree = data.shipping_cost === 0 && data.distance > 0;
            const shippingText = isFree ? 'GRATIS' : `Rp ${data.shipping_cost.toLocaleString('id-ID')}`;
            
            // Update popup with saved data
            marker.setPopupContent(`
                <div class="text-sm">
                    <div class="font-medium text-green-600 mb-1">✅ Lokasi Tersimpan</div>
                    <div class="text-xs mb-2 text-gray-700">${data.address || 'Alamat tidak diketahui'}</div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600">Jarak: ${data.distance ? data.distance.toFixed(1) : '0'} km</span>
                        <span class="text-green-600 font-medium">${shippingText}</span>
                    </div>
                </div>
            `).openPopup();
            
            updateLocationDisplay();
            console.log('✅ Location restored successfully with pin movement');
        } else {
            console.log('❌ Cannot restore location - missing data or map not ready');
        }
    }

    // Enhanced update marker position with better error handling
    function updateMarkerPosition(lat, lng) {
        if (!marker || !map || !isMapInitialized) {
            console.log('❌ Map not ready for marker update');
            isUpdatingLocation = false;
            return;
        }
        
        // Ensure marker is at correct position
        marker.setLatLng([lat, lng]);
        showLoadingState();
        
        marker.setPopupContent(`
            <div class="text-sm text-center">
                <div class="animate-pulse text-blue-600">⏳ Menghitung...</div>
                <div class="text-xs text-gray-600">
                    Mendapatkan alamat dan<br>
                    menghitung ongkos kirim...
                </div>
            </div>
        `).openPopup();
        
        const storeLat = storeLocation.lat;
        const storeLng = storeLocation.lng;
        
        const distance = calculateDistance(storeLat, storeLng, lat, lng);
        const shippingCost = calculateShippingCostByDistance(distance, window.locationData.subtotal);
        
        console.log('Calculation results:', {
            storeCoordsUsed: { lat: storeLat, lng: storeLng },
            userCoords: { lat, lng },
            distance: distance,
            subtotal: window.locationData.subtotal,
            shippingCost: shippingCost
        });
        
        // Get address from coordinates with timeout
        const timeoutPromise = new Promise((_, reject) => 
            setTimeout(() => reject(new Error('Timeout')), 8000)
        );
        
        const fetchPromise = fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=id`, {
            headers: {
                'User-Agent': 'DeliveryApp/1.0'
            }
        }).then(response => response.json());
        
        Promise.race([fetchPromise, timeoutPromise])
            .then(data => {
                const address = data.display_name || 'Alamat tidak diketahui';
                
                const isFree = shippingCost === 0;
                const shippingText = isFree ? 'GRATIS' : `Rp ${shippingCost.toLocaleString('id-ID')}`;
                const shippingColor = isFree ? 'text-green-600' : 'text-blue-600';
                
                marker.setPopupContent(`
                    <div class="text-sm">
                        <div class="font-medium text-green-600 mb-1">✅ Lokasi Pengiriman</div>
                        <div class="text-xs mb-2 text-gray-700">${address}</div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Jarak: ${distance.toFixed(1)} km</span>
                            <span class="font-medium ${shippingColor}">${shippingText}</span>
                        </div>
                    </div>
                `).openPopup();
                
                saveLocationData(lat, lng, address, distance, shippingCost);
                isUpdatingLocation = false;
            })
            .catch(error => {
                console.log('Address fetch failed:', error);
                const fallbackAddress = 'Koordinat: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                
                const isFree = shippingCost === 0;
                const shippingText = isFree ? 'GRATIS' : `Rp ${shippingCost.toLocaleString('id-ID')}`;
                const shippingColor = isFree ? 'text-green-600' : 'text-blue-600';
                
                marker.setPopupContent(`
                    <div class="text-sm">
                        <div class="font-medium text-green-600 mb-1">✅ Lokasi Pengiriman</div>
                        <div class="text-xs mb-2 text-gray-700">${fallbackAddress}</div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Jarak: ${distance.toFixed(1)} km</span>
                            <span class="font-medium ${shippingColor}">${shippingText}</span>
                        </div>
                    </div>
                `).openPopup();
                
                saveLocationData(lat, lng, fallbackAddress, distance, shippingCost);
                isUpdatingLocation = false;
            });
    }

    // Enhanced get current location with better pin movement
    function getCurrentLocation() {
        if (!navigator.geolocation) {
            alert('Browser Anda tidak mendukung geolocation.');
            return;
        }
        
        if (!map || !isMapInitialized) {
            alert('Peta belum siap. Silakan tunggu sebentar dan coba lagi.');
            return;
        }
        
        if (isUpdatingLocation) {
            console.log('⚠️ Location update in progress, ignoring GPS request');
            return;
        }
        
        showLoadingState();
        
        if (marker) {
            marker.setPopupContent(`
                <div class="text-sm text-center">
                    <div class="animate-pulse text-blue-600">🌍 Mencari lokasi...</div>
                    <div class="text-xs text-gray-600">Menggunakan GPS...</div>
                </div>
            `).openPopup();
        }
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                console.log('📱 GPS location obtained:', lat, lng);
                
                if (map && marker && isMapInitialized) {
                    // Use enhanced movement function
                    moveMarkerToLocation(lat, lng);
                }
            }, 
            function(error) {
                let errorMsg = 'Tidak dapat mengakses lokasi Anda. Silakan pilih lokasi secara manual pada peta.';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMsg = 'Akses lokasi ditolak. Aktifkan GPS dan izinkan akses lokasi.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMsg = 'Informasi lokasi tidak tersedia.';
                        break;
                    case error.TIMEOUT:
                        errorMsg = 'Timeout mendapatkan lokasi.';
                        break;
                }
                alert(errorMsg);
                
                if (marker) {
                    marker.setPopupContent(`
                        <div class="text-sm text-center">
                            <div class="font-medium text-amber-600 mb-1">📍 Pilih Lokasi Manual</div>
                            <div class="text-xs text-gray-600">
                                Drag marker ini atau klik di peta<br>
                                untuk menentukan lokasi
                            </div>
                        </div>
                    `).openPopup();
                }
                
                const loadingEl = document.getElementById('shipping-loading');
                if (loadingEl) loadingEl.style.display = 'none';
                isUpdatingLocation = false;
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    }

    // Enhanced refresh map with better pin reset
    function refreshMap() {
        console.log('🔄 Refreshing map...');
        
        isUpdatingLocation = false; // Reset update flag
        
        if (map && isMapInitialized) {
            try {
                map.invalidateSize();
                setTimeout(() => {
                    if (map) {
                        // Reset to default location with smooth animation
                        map.setView([defaultLat, defaultLon], 13, {
                            animate: true,
                            duration: 0.8
                        });
                        
                        if (marker) {
                            // Reset marker to default position
                            marker.setLatLng([defaultLat, defaultLon]);
                            marker.setPopupContent(`
                                <div class="text-sm text-center">
                                    <div class="font-medium text-blue-600 mb-1">📍 Lokasi Pengiriman</div>
                                    <div class="text-xs text-gray-600">
                                        Drag marker ini atau klik di peta<br>
                                        untuk menentukan lokasi pengiriman
                                    </div>
                                </div>
                            `).openPopup();
                        }
                        
                        // Reset location data but preserve subtotal
                        const currentSubtotal = window.locationData.subtotal;
                        window.locationData = {
                            latitude: null,
                            longitude: null,
                            address: null,
                            distance: null,
                            shipping_cost: null,
                            subtotal: currentSubtotal
                        };
                        
                        // Clear localStorage backup
                        try {
                            localStorage.removeItem('checkoutLocation');
                        } catch (error) {
                            console.log('Could not clear localStorage');
                        }
                        
                        const containerEl = document.getElementById('location-info-container');
                        if (containerEl) containerEl.style.display = 'none';
                        
                        updatePricingDisplay();
                    }
                }, 100);
            } catch (error) {
                console.log('Error refreshing map, reinitializing...', error);
                destroyMap();
                setTimeout(() => initMap(), 500);
            }
        } else {
            destroyMap();
            setTimeout(() => initMap(), 500);
        }
    }

    // =============== MAP VISIBILITY AND HEALTH MONITORING ===============

    // Watch for Livewire component updates that might affect the map
    function watchForMapContainer() {
        const observer = new MutationObserver(function(mutations) {
            let shouldReinitMap = false;
            
            mutations.forEach(function(mutation) {
                // Check if map container was added/modified
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            if (node.id === 'map' || node.querySelector && node.querySelector('#map')) {
                                console.log('🔍 Map container detected in DOM changes');
                                shouldReinitMap = true;
                            }
                        }
                    });
                }
                
                // Check for style/attribute changes that might make map visible
                if (mutation.type === 'attributes' && 
                    (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                    const target = mutation.target;
                    if (target.id === 'map' || target.querySelector && target.querySelector('#map')) {
                        console.log('🔍 Map container style/class changed');
                        shouldReinitMap = true;
                    }
                }
            });
            
            if (shouldReinitMap && !isMapInitialized) {
                console.log('🔄 Scheduling map initialization from mutation observer');
                setTimeout(() => {
                    if (isMapElementReady() && !isMapInitialized) {
                        initMap();
                    }
                }, 300);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class', 'wire:loading', 'wire:target']
        });
        
        console.log('👀 Mutation observer started');
    }

    // Enhanced map health check with pin validation
    function startMapHealthCheck() {
        if (mapCheckInterval) {
            clearInterval(mapCheckInterval);
        }
        
        mapCheckInterval = setInterval(() => {
            const mapElement = document.getElementById('map');
            
            // Check if map element exists and is visible but map is not initialized
            if (mapElement && isMapElementReady() && !isMapInitialized) {
                console.log('🚨 Map element found but not initialized, fixing...');
                initMap();
                return;
            }
            
            // Check if map is initialized but container is empty or markers missing
            if (map && isMapInitialized) {
                try {
                    const mapContainer = map.getContainer();
                    const tiles = mapContainer.querySelectorAll('.leaflet-tile');
                    const leafletMapPane = mapContainer.querySelector('.leaflet-map-pane');
                    
                    // Check if map pane exists but is empty
                    if (!leafletMapPane || mapContainer.children.length === 0) {
                        console.log('🚨 Map container empty, reinitializing...');
                        destroyMap();
                        setTimeout(() => initMap(), 500);
                        return;
                    }
                    
                    // Check if markers are missing
                    if (!marker || !storeMarker) {
                        console.log('🚨 Markers missing, reinitializing map...');
                        destroyMap();
                        setTimeout(() => initMap(), 500);
                        return;
                    }
                    
                    // Validate marker positions
                    if (marker) {
                        const markerPos = marker.getLatLng();
                        if (!markerPos || isNaN(markerPos.lat) || isNaN(markerPos.lng)) {
                            console.log('🚨 Invalid marker position, fixing...');
                            marker.setLatLng([defaultLat, defaultLon]);
                        }
                    }
                    
                    // Check if tiles are missing (map appears blank)
                    if (tiles.length === 0 && isMapInitialized) {
                        console.log('🚨 Map initialized but no tiles loaded, forcing refresh...');
                        try {
                            // Force complete refresh
                            map.invalidateSize();
                            
                            // Get current view
                            const center = map.getCenter();
                            const zoom = map.getZoom();
                            
                            // Force redraw
                            setTimeout(() => {
                                if (map) {
                                    map.setView(center, zoom);
                                    map.invalidateSize();
                                }
                            }, 100);
                            
                            // If still no tiles after 2 seconds, reinitialize
                            setTimeout(() => {
                                const newTiles = mapContainer.querySelectorAll('.leaflet-tile');
                                if (newTiles.length === 0) {
                                    console.log('🚨 Tiles still not loading, reinitializing map...');
                                    destroyMap();
                                    setTimeout(() => initMap(), 500);
                                }
                            }, 2000);
                            
                        } catch (error) {
                            console.log('Error during forced refresh:', error);
                            destroyMap();
                            setTimeout(() => initMap(), 1000);
                        }
                    }
                    
                    // Check map size consistency
                    const rect = mapElement.getBoundingClientRect();
                    const mapSize = map.getSize();
                    
                    if (Math.abs(rect.width - mapSize.x) > 10 || Math.abs(rect.height - mapSize.y) > 10) {
                        console.log('🚨 Map size mismatch detected, fixing...', {
                            elementSize: { width: rect.width, height: rect.height },
                            mapSize: mapSize
                        });
                        map.invalidateSize();
                    }
                    
                } catch (error) {
                    console.log('Error in health check:', error);
                    // If we can't even check the map, it's probably broken
                    destroyMap();
                    setTimeout(() => initMap(), 1000);
                }
            }
        }, 2000);
        
        console.log('❤️ Enhanced map health check with pin validation started');
    }

    // Stop health check when map is working properly
    function stopMapHealthCheck() {
        if (mapCheckInterval) {
            clearInterval(mapCheckInterval);
            mapCheckInterval = null;
            console.log('⏹️ Map health check stopped');
        }
    }

    // =============== EVENT LISTENERS AND INITIALIZATION ===============

    // Enhanced Livewire event listeners
    document.addEventListener('livewire:initialized', function () {
        console.log('✅ Livewire initialized');
        initializeSubtotal();
        watchForMapContainer();
        
        // Try to initialize map immediately
        setTimeout(() => {
            if (isMapElementReady() && !isMapInitialized) {
                initMap();
            }
        }, 500);
        
        // Watch for Livewire updates that might affect map visibility
        Livewire.hook('morph.updated', ({ el, component }) => {
            console.log('🔄 Livewire morph updated, checking map...');
            setTimeout(() => {
                if (isMapElementReady() && !isMapInitialized) {
                    console.log('🗺️ Map became visible after Livewire update');
                    initMap();
                } else if (map && isMapInitialized) {
                    console.log('♻️ Refreshing existing map after Livewire update');
                    try {
                        // Enhanced refresh with multiple attempts
                        map.invalidateSize();
                        
                        // Force redraw
                        setTimeout(() => {
                            if (map) {
                                map.invalidateSize();
                                const currentCenter = map.getCenter();
                                const currentZoom = map.getZoom();
                                map.setView(currentCenter, currentZoom);
                                console.log('✅ Map refresh completed');
                            }
                        }, 100);
                        
                        // Restore location if exists
                        setTimeout(() => {
                            if (window.locationData.latitude && window.locationData.longitude) {
                                restoreLocationFromData();
                            }
                        }, 300);
                        
                    } catch (error) {
                        console.log('❌ Error refreshing map after Livewire update:', error);
                        console.log('🔄 Reinitializing map...');
                        destroyMap();
                        setTimeout(() => initMap(), 500);
                    }
                }
            }, 200);
        });
    });

    // Enhanced Livewire v2 support
    document.addEventListener('livewire:load', function () {
        console.log('✅ Livewire v2 loaded');
        initializeSubtotal();
        watchForMapContainer();
        
        setTimeout(() => {
            if (isMapElementReady() && !isMapInitialized) {
                initMap();
            }
        }, 500);
    });

    // DOM ready fallback
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ DOM Content Loaded');
        initializeSubtotal();
        watchForMapContainer();
        
        setTimeout(() => {
            if (isMapElementReady() && !isMapInitialized) {
                initMap();
            }
        }, 1000);
    });

    // Enhanced window load event
    window.addEventListener('load', function() {
        console.log('✅ Window fully loaded');
        initializeSubtotal();
        
        setTimeout(() => {
            if (isMapElementReady() && !isMapInitialized) {
                initMap();
            }
            startMapHealthCheck();
        }, 1500);
    });

    // Listen for window resize to fix map and pins
    window.addEventListener('resize', function() {
        if (map && isMapInitialized) {
            console.log('🔄 Window resized, invalidating map size');
            setTimeout(() => {
                map.invalidateSize();
                // Ensure markers are still properly positioned
                if (marker && window.locationData.latitude && window.locationData.longitude) {
                    marker.setLatLng([window.locationData.latitude, window.locationData.longitude]);
                }
            }, 100);
        }
    });

    // Listen for focus events (when user returns to tab)
    window.addEventListener('focus', function() {
        if (map && isMapInitialized) {
            console.log('🔄 Window focused, refreshing map');
            setTimeout(() => {
                map.invalidateSize();
                // Restore pin position if needed
                if (marker && window.locationData.latitude && window.locationData.longitude) {
                    restoreLocationFromData();
                }
            }, 200);
        }
    });

    // Force map initialization on visibility change
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && isMapElementReady() && !isMapInitialized) {
            console.log('🔄 Tab became visible, checking map...');
            setTimeout(() => initMap(), 500);
        }
    });

    // =============== ENHANCED LIVEWIRE INTEGRATION ===============

    // Handle Livewire component replacement/morphing with pin preservation
    function handleLivewireMapUpdate() {
        console.log('🔄 Handling Livewire map update...');
        
        if (!isMapElementReady()) {
            console.log('❌ Map element not ready after Livewire update');
            return;
        }
        
        if (map && isMapInitialized) {
            console.log('♻️ Map exists, performing safe refresh...');
            try {
                // Save current state
                const savedLocation = {
                    lat: window.locationData.latitude,
                    lng: window.locationData.longitude
                };
                
                // Safe refresh sequence
                const currentCenter = map.getCenter();
                const currentZoom = map.getZoom();
                
                // Force container refresh
                const container = map.getContainer();
                const originalHeight = container.style.height;
                container.style.height = '299px';
                setTimeout(() => {
                    container.style.height = originalHeight || '300px';
                    
                    if (map) {
                        map.invalidateSize();
                        map.setView(currentCenter, currentZoom);
                        
                        // Ensure markers are still properly positioned
                        if (!storeMarker || !marker) {
                            console.log('🚨 Markers missing after refresh, recreating...');
                            destroyMap();
                            setTimeout(() => initMap(), 300);
                        } else {
                            // Restore marker positions
                            if (savedLocation.lat && savedLocation.lng) {
                                console.log('📍 Restoring marker position after refresh');
                                marker.setLatLng([savedLocation.lat, savedLocation.lng]);
                            }
                        }
                        
                        // Restore location data after refresh
                        setTimeout(() => {
                            if (savedLocation.lat && savedLocation.lng) {
                                console.log('🔄 Restoring full location data after safe refresh');
                                restoreLocationFromData();
                            }
                        }, 400);
                    }
                }, 100);
                
            } catch (error) {
                console.log('❌ Safe refresh failed, reinitializing:', error);
                destroyMap();
                setTimeout(() => initMap(), 500);
            }
        } else {
            console.log('🗺️ Map not initialized, creating new instance...');
            destroyMap();
            setTimeout(() => initMap(), 300);
        }
    }

    // Listen for specific Livewire events that might break the map
    document.addEventListener('livewire:updated', function (event) {
        console.log('🔄 Livewire component updated');
        
        // Check if the update affected our checkout component
        const mapElement = document.getElementById('map');
        if (mapElement) {
            setTimeout(() => {
                handleLivewireMapUpdate();
            }, 150);
        }
    });

    // Additional Livewire v3 hooks
    if (window.Livewire) {
        // After DOM morphing
        document.addEventListener('livewire:morph.updated', function(event) {
            console.log('🔄 Livewire morph completed');
            setTimeout(() => {
                handleLivewireMapUpdate();
            }, 200);
        });
        
        // After component rendering
        document.addEventListener('livewire:rendered', function(event) {
            console.log('🔄 Livewire rendered');
            setTimeout(() => {
                if (isMapElementReady() && !isMapInitialized) {
                    initMap();
                }
            }, 100);
        });
    }

    // =============== DEBUGGING AND UTILITY FUNCTIONS ===============

    // Enhanced debugging functions
    window.debugLocation = function() {
        console.log('🔍 Location data:', window.locationData);
        console.log('🔍 Map initialized:', isMapInitialized);
        console.log('🔍 Map object:', map);
        console.log('🔍 Marker object:', marker);
        console.log('🔍 Store marker object:', storeMarker);
        console.log('🔍 Store coordinates:', storeLocation);
        console.log('🔍 Map element ready:', isMapElementReady());
        console.log('🔍 Update in progress:', isUpdatingLocation);
        
        if (map) {
            console.log('🔍 Map center:', map.getCenter());
            console.log('🔍 Map zoom:', map.getZoom());
            console.log('🔍 Map size:', map.getSize());
        }
        
        if (marker) {
            console.log('🔍 Marker position:', marker.getLatLng());
        }
    };

    window.forceInitMap = function() {
        console.log('🔧 Force initializing map...');
        destroyMap();
        isUpdatingLocation = false;
        setTimeout(() => {
            if (isMapElementReady()) {
                initMap();
            } else {
                console.log('❌ Map element not ready for force init');
            }
        }, 500);
    };

    // Enhanced emergency map recovery function
    window.emergencyMapFix = function() {
        console.log('🚑 Emergency map fix initiated...');
        
        // Stop health check
        stopMapHealthCheck();
        
        // Reset all flags
        isUpdatingLocation = false;
        
        // Destroy everything
        destroyMap();
        
        // Clear any existing intervals
        if (mapCheckInterval) {
            clearInterval(mapCheckInterval);
        }
        
        // Wait and reinitialize everything
        setTimeout(() => {
            console.log('🔄 Emergency reinitializing...');
            initializeSubtotal();
            
            if (isMapElementReady()) {
                initMap();
                startMapHealthCheck();
            } else {
                console.log('❌ Map element still not ready after emergency fix');
                alert('Terjadi masalah dengan peta. Silakan refresh halaman.');
            }
        }, 1000);
    };

    // Enhanced test pin movement function
    window.testPinMovement = function(lat = -6.8650, lng = 109.1350) {
        console.log('🧪 Testing pin movement to:', lat, lng);
        if (map && marker && isMapInitialized) {
            moveMarkerToLocation(lat, lng);
        } else {
            console.log('❌ Map not ready for testing');
        }
    };

    // Auto-recovery mechanism with pin validation
    setTimeout(() => {
        if (isMapElementReady() && !isMapInitialized) {
            console.log('⚡ Auto-recovery: Map element ready but not initialized');
            initMap();
        }
        startMapHealthCheck();
    }, 3000);

    // Periodic check for map element appearance
    function periodicMapCheck() {
        setTimeout(function checkMap() {
            if (isMapElementReady() && !isMapInitialized) {
                console.log('⏰ Periodic check: Map element found, initializing...');
                initMap();
            } else if (!isMapElementReady()) {
                // Continue checking
                setTimeout(checkMap, 2000);
            }
        }, 2000);
    }

    // Start periodic check
    periodicMapCheck();

    // Enhanced Intersection Observer for pin management
    if (typeof IntersectionObserver !== 'undefined') {
        const mapObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && entry.target.id === 'map') {
                    console.log('👁️ Map element became visible (Intersection Observer)');
                    setTimeout(() => {
                        if (!isMapInitialized) {
                            initMap();
                        } else if (map) {
                            map.invalidateSize();
                            // Ensure pins are properly positioned
                            if (marker && window.locationData.latitude && window.locationData.longitude) {
                                marker.setLatLng([window.locationData.latitude, window.locationData.longitude]);
                            }
                        }
                    }, 200);
                }
            });
        }, {
            threshold: 0.1
        });

        // Start observing when document is ready
        setTimeout(() => {
            const mapElement = document.getElementById('map');
            if (mapElement) {
                mapObserver.observe(mapElement);
                console.log('👁️ Intersection observer started for map');
            }
        }, 1000);
    }

    console.log('🚀 Enhanced maps script with improved pin movement loaded successfully');
</script>