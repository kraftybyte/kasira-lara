<?php

namespace App\Livewire;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Pil pemberitahuan operasional di topbar: pesanan lewat SOP, pesanan QR, stok habis, reservasi.
 */
class OpsAlerts extends Component
{
    #[Locked]
    public ?int $tenantId = null;

    public function mount(): void
    {
        $this->tenantId = Filament::getTenant()?->getKey();
    }

    public function render(): View
    {
        $tenant = Filament::getTenant() ?? ($this->tenantId ? Tenant::find($this->tenantId) : null);
        $summary = $tenant ? $this->summary($tenant) : ['alerts' => [], 'status' => ''];

        return view('livewire.ops-alerts', [
            'tenant' => $tenant,
            'alerts' => $summary['alerts'],
            'status' => $summary['status'],
            'updatedAt' => $tenant?->now()->format('H:i') ?? '',
        ]);
    }

    /**
     * @return array{alerts: array<int, array{level: string, text: string, url: string}>, status: string}
     */
    public function summary(Tenant $tenant): array
    {
        $user = auth()->user();
        $kitchenOnly = (bool) $user?->hasRole('kitchen') && ! $user->hasAnyRole(['super_admin', 'owner', 'kepala_toko', 'cashier']);
        $slug = $tenant->getRouteKey();
        $url = fn (string $path): string => url("/admin/{$slug}/{$path}");
        $sop = max(1, (int) (TenantSetting::where('tenant_id', $tenant->id)->value('kitchen_sop_minutes') ?? 15));
        $alerts = [];

        $kitchen = Sale::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', '!=', 'cancelled')
            ->whereNull('closed_at')
            ->whereNull('kitchen_completed_at');

        $cooking = (clone $kitchen)->whereNull('served_at')->count();
        $late = (clone $kitchen)->whereNull('served_at')->where('created_at', '<=', now()->subMinutes($sop))->count();
        if ($late > 0) {
            $alerts[] = ['level' => 'danger', 'text' => "{$late} pesanan menunggu lebih dari {$sop} menit (SOP)", 'url' => $url('kitchen')];
        }

        $fromQr = (clone $kitchen)->whereNull('served_at')->where('source', 'customer')->count();
        if ($fromQr > 0) {
            $alerts[] = ['level' => 'info', 'text' => "{$fromQr} pesanan dari QR meja, cek dapur", 'url' => $url('kitchen')];
        }

        $ready = (clone $kitchen)->whereNotNull('served_at')->count();
        if ($ready > 0) {
            $alerts[] = ['level' => 'info', 'text' => "{$ready} pesanan siap diantar ke meja", 'url' => $url('kitchen')];
        }

        if (! $kitchenOnly) {
            $unpaid = Sale::query()->where('tenant_id', $tenant->id)->where('source', 'customer')->where('payment_method', 'counter')->where('status', 'pending')->count();
            if ($unpaid > 0) {
                $alerts[] = ['level' => 'warning', 'text' => "{$unpaid} pesanan QR menunggu dibayar di kasir", 'url' => $url('tables-overview')];
            }

            $ingredients = Ingredient::query()->where('tenant_id', $tenant->id)->where('is_active', true);
            $ingredientsOut = (clone $ingredients)->where('stock', '<=', 0)->count();
            if ($ingredientsOut > 0) {
                $alerts[] = ['level' => 'danger', 'text' => "{$ingredientsOut} bahan baku habis", 'url' => $url('ingredients')];
            }
            $ingredientsLow = (clone $ingredients)->where('stock', '>', 0)->where('minimum_stock', '>', 0)->whereColumn('stock', '<=', 'minimum_stock')->count();
            if ($ingredientsLow > 0) {
                $alerts[] = ['level' => 'warning', 'text' => "{$ingredientsLow} bahan baku menipis", 'url' => $url('ingredients')];
            }

            $products = Product::query()->where('tenant_id', $tenant->id)->where('is_active', true)->where('rate_type', '!=', 'duration');
            $productsOut = (clone $products)->where('stock', '<=', 0)->count();
            if ($productsOut > 0) {
                $alerts[] = ['level' => 'danger', 'text' => "{$productsOut} produk habis, tidak bisa dijual", 'url' => $url('products')];
            }
            $productsLow = (clone $products)->where('stock', '>', 0)->where('minimum_stock', '>', 0)->whereColumn('stock', '<=', 'minimum_stock')->count();
            if ($productsLow > 0) {
                $alerts[] = ['level' => 'warning', 'text' => "{$productsLow} produk stok menipis", 'url' => $url('products')];
            }

            $now = $tenant->now();
            $reservations = Reservation::query()
                ->where('tenant_id', $tenant->id)
                ->whereDate('reservation_date', $now->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])
                ->get()
                ->filter(function (Reservation $reservation) use ($now): bool {
                    $time = $reservation->reservation_time?->format('H:i');

                    return $time !== null && $time >= $now->copy()->subMinutes(15)->format('H:i') && $time <= $now->copy()->addMinutes(60)->format('H:i');
                });
            foreach ($reservations as $reservation) {
                $alerts[] = ['level' => 'info', 'text' => 'Reservasi '.$reservation->reservation_time->format('H:i').' '.$reservation->customer_name.' ('.$reservation->guest_count.' orang)', 'url' => $url('reservations')];
            }
        }

        $rank = ['danger' => 0, 'warning' => 1, 'info' => 2];
        usort($alerts, fn (array $a, array $b): int => $rank[$a['level']] <=> $rank[$b['level']]);

        $status = $cooking > 0 ? "{$cooking} pesanan sedang dimasak, semua masih dalam SOP" : 'Dapur kosong';
        if (! $kitchenOnly) {
            $status .= ', stok aman, tidak ada pesanan menunggu bayar';
        }

        return ['alerts' => $alerts, 'status' => $status];
    }
}
