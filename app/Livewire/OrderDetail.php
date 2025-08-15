<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PaymentMethod;
use App\Models\Order;
use App\Models\Store;
use Carbon\Carbon;
use App\Services\OrderStatusService;
use App\Services\MidtransService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderDetail extends Component
{
    public $order;
    public $paymentDeadline;
    public $paymentMethods;
    public $store;
    
    // Property untuk modal konfirmasi pembatalan
    public $showCancelModal = false;
    public $cancelReason = '';

    public function mount($orderNumber)
    {
        $this->order = Order::where('order_number', $orderNumber)
                        ->where('user_id', auth()->id()) // Tambah security check
                        ->firstOrFail();
                        
        $this->paymentDeadline = Carbon::parse($this->order->created_at)->addHours(12);
        $this->paymentMethods = PaymentMethod::all();
        $this->store = Store::first();
    }

    public function getStatusInfo()
    {
        return OrderStatusService::getStatusInfo(
            $this->order->status,
            $this->paymentDeadline,
            $this->order->completed_at,
            $this->order->payment_gateway_transaction_id
        );
    }

    /**
     * Cek apakah order bisa dibatalkan oleh user
     */
    public function canCancelOrder()
    {
        // User hanya bisa membatalkan jika status masih 'checking' atau 'pending'
        return in_array($this->order->status, [
            OrderStatusService::STATUS_CHECKING,
            OrderStatusService::STATUS_PENDING
        ]) && $this->order->payment_status !== OrderStatusService::PAYMENT_PAID;
    }

    /**
     * Tampilkan modal konfirmasi pembatalan
     */
    public function showCancelConfirmation()
    {
        if (!$this->canCancelOrder()) {
            $this->dispatch('showAlert', [
                'message' => 'Pesanan tidak dapat dibatalkan pada tahap ini.',
                'type' => 'error'
            ]);
            return;
        }

        $this->showCancelModal = true;
        $this->cancelReason = '';
    }

    /**
     * Tutup modal pembatalan
     */
    public function closeCancelModal()
    {
        $this->showCancelModal = false;
        $this->cancelReason = '';
    }

    /**
     * Proses pembatalan pesanan
     */
    public function cancelOrder()
    {
        // Validasi
        if (!$this->canCancelOrder()) {
            $this->dispatch('showAlert', [
                'message' => 'Pesanan tidak dapat dibatalkan pada tahap ini.',
                'type' => 'error'
            ]);
            return;
        }

        // Validasi alasan pembatalan
        if (empty(trim($this->cancelReason))) {
            $this->addError('cancelReason', 'Alasan pembatalan wajib diisi.');
            return;
        }

        if (strlen(trim($this->cancelReason)) < 10) {
            $this->addError('cancelReason', 'Alasan pembatalan minimal 10 karakter.');
            return;
        }

        DB::beginTransaction();
        
        try {
            // Update status order
            $this->order->update([
                'status' => OrderStatusService::STATUS_CANCELLED,
                'payment_status' => OrderStatusService::PAYMENT_UNPAID,
                'cancelled_at' => now(),
                'cancel_reason' => trim($this->cancelReason),
                'cancelled_by' => 'user' // Menandai dibatalkan oleh user
            ]);

            // Jika ada transaksi Midtrans yang pending, coba cancel
            if ($this->order->payment_gateway_transaction_id) {
                try {
                    $midtrans = app(MidtransService::class);
                    $midtrans->cancel($this->order->payment_gateway_transaction_id);
                } catch (\Exception $e) {
                    Log::warning('Failed to cancel Midtrans transaction: ' . $e->getMessage(), [
                        'order_id' => $this->order->id,
                        'transaction_id' => $this->order->payment_gateway_transaction_id
                    ]);
                    // Tidak throw error karena pembatalan order sudah berhasil
                }
            }

            DB::commit();

            // Refresh order data
            $this->order->refresh();
            
            // Tutup modal
            $this->closeCancelModal();

            // Tampilkan notifikasi sukses
            $this->dispatch('showAlert', [
                'message' => 'Pesanan berhasil dibatalkan.',
                'type' => 'success'
            ]);

            Log::info('Order cancelled by user', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'user_id' => auth()->id(),
                'reason' => $this->cancelReason
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Failed to cancel order: ' . $e->getMessage(), [
                'order_id' => $this->order->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('showAlert', [
                'message' => 'Terjadi kesalahan saat membatalkan pesanan. Silakan coba lagi.',
                'type' => 'error'
            ]);
        }
    }

    public function render()
    {
        $statusInfo = $this->getStatusInfo();
        $this->checkPaymentStatus();
       
        return view('livewire.order-detail', [
            'statusInfo' => $statusInfo,
            'order' => $this->order,
            'canCancel' => $this->canCancelOrder()
        ])->layout('components.layouts.app', ['hideBottomNav' => true]);
    }

    public function checkPaymentStatus()
    {
        if ($this->order && $this->order->payment_gateway_transaction_id && 
            $this->order->status !== OrderStatusService::STATUS_CANCELLED) {
            try {
                $midtrans = app(MidtransService::class);
                $status = $midtrans->getStatus($this->order);
                $latestStatus = $status['data']->transaction_status;

                // Cek apakah status berubah
                if ($latestStatus) {
                    // Update database jika ada perubahan status
                    $this->order->update([
                        'payment_gateway_data' => json_encode($status['data'])
                    ]);

                    switch ($latestStatus) {
                        case 'settlement':
                            $this->order->update([
                                'payment_status' => OrderStatusService::PAYMENT_PAID,
                            ]);
                            break;
                        case 'pending':
                            $this->order->update([
                                'payment_status' => OrderStatusService::PAYMENT_UNPAID,
                                'status' => OrderStatusService::STATUS_PENDING
                            ]);
                            break;
                        case 'deny':
                        case 'cancel':
                        case 'expire':
                            $this->order->update([
                                'payment_status' => OrderStatusService::PAYMENT_UNPAID,
                                'status' => OrderStatusService::STATUS_CANCELLED,
                                'cancelled_at' => now(),
                                'cancel_reason' => 'Pembayaran ' . $latestStatus,
                                'cancelled_by' => 'system'
                            ]);
                            break;
                    }
                    
                    // Refresh order setelah update
                    $this->order->refresh();
                }
            } catch (\Exception $e) {
                Log::error('Error checking payment status: ' . $e->getMessage());
            }
        }
    }
}