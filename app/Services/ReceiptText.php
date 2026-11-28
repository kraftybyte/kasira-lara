<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\TenantSetting;

/**
 * Struk sebagai baris teks polos selebar kertas termal (32 kolom = 58 mm, 48 kolom = 80 mm).
 * Dipakai POS untuk mencetak langsung ke printer termal lewat ESC/POS tanpa dialog cetak.
 */
class ReceiptText
{
    /**
     * @return array<int, string>
     */
    public static function lines(Sale $sale, int $width = 32): array
    {
        $sale->loadMissing(['items.modifiers', 'customer', 'user', 'table', 'tenant']);
        $setting = TenantSetting::getOrCreateForTenant((int) $sale->tenant_id);
        $tenant = $sale->tenant;

        $rp = fn (float $n): string => 'Rp '.number_format($n, 0, ',', '.');
        $rule = str_repeat('-', $width);
        $lines = [];

        $push = function (string $text) use (&$lines, $width): void {
            foreach (self::wrap($text, $width) as $line) {
                $lines[] = $line;
            }
        };
        $center = function (string $text) use (&$lines, $width): void {
            foreach (self::wrap($text, $width) as $line) {
                $lines[] = str_pad($line, $width, ' ', STR_PAD_BOTH);
            }
        };
        $row = function (string $left, string $right) use (&$lines, $width): void {
            $space = $width - mb_strlen($right) - 1;
            $left = mb_strlen($left) > $space ? mb_substr($left, 0, max(0, $space)) : $left;
            $lines[] = $left.str_repeat(' ', max(1, $width - mb_strlen($left) - mb_strlen($right))).$right;
        };

        $center(filled($setting->store_name) ? $setting->store_name : ($tenant?->name ?? 'Kasira'));
        if (($setting->show_address ?? true) && filled($setting->address)) {
            $center($setting->address);
        }
        if (($setting->show_phone ?? true) && filled($setting->phone)) {
            $center($setting->phone);
        }
        $lines[] = $rule;

        if ($setting->show_invoice_number ?? true) {
            $row('No', (string) $sale->invoice_number);
        }
        $timezone = $tenant?->timezone ?? config('app.timezone');
        $row('Tgl', $sale->created_at->setTimezone($timezone)->format('d/m/Y H:i'));
        if (($setting->show_cashier ?? true) && $sale->user) {
            $row('Kasir', (string) $sale->user->name);
        }
        $customerName = $sale->customer?->name ?? $sale->customer_name;
        if (($setting->show_customer ?? true) && filled($customerName)) {
            $row('Pelanggan', (string) $customerName);
        }
        if ($sale->table) {
            $row('Meja', (string) $sale->table->name);
        }
        $lines[] = $rule;

        foreach ($sale->items as $item) {
            $push((string) $item->product_name);
            $qty = rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',');
            $row('  '.$qty.' x '.$rp((float) $item->unit_price), $rp((float) $item->total));
            foreach ($item->modifiers as $modifier) {
                $push('  + '.($modifier->modifier_name ?? $modifier->name));
            }
            if (filled($item->notes)) {
                $push('  * '.$item->notes);
            }
        }
        $lines[] = $rule;

        $row('Subtotal', $rp((float) $sale->subtotal));
        if (($setting->show_tax ?? true) && (float) $sale->tax > 0) {
            $row('PPN '.rtrim(rtrim(number_format((float) ($setting->tax_rate ?? 0), 2, '.', ''), '0'), '.').'%', $rp((float) $sale->tax));
        }
        $row('TOTAL', $rp((float) $sale->grand_total));
        if ($setting->show_payment_method ?? true) {
            $row('Bayar', self::methodLabel((string) $sale->payment_method));
        }
        $row('Dibayar', $rp((float) $sale->paid_amount));
        if ((float) $sale->change_amount > 0) {
            $row('Kembali', $rp((float) $sale->change_amount));
        }
        $lines[] = $rule;

        if (($setting->show_footer ?? true) && filled($setting->footer_text)) {
            foreach (preg_split('/\r?\n/', (string) $setting->footer_text) as $footerLine) {
                $center($footerLine);
            }
        } else {
            $center('Terima kasih');
        }

        return $lines;
    }

    public static function methodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'Tunai',
            'qris' => 'QRIS',
            'qris_manual' => 'QRIS Manual',
            'va' => 'Virtual Account',
            'transfer' => 'Transfer',
            'edc' => 'EDC',
            'counter' => 'Kasir',
            default => ucfirst(str_replace('_', ' ', $method)),
        };
    }

    /**
     * @return array<int, string>
     */
    protected static function wrap(string $text, int $width): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if ($text === '') {
            return [''];
        }

        return explode("\n", wordwrap($text, $width, "\n", true));
    }
}
