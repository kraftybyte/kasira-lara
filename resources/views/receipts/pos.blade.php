<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        {{ $sale->invoice_number }}
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            width: 100%;
        }

        .receipt {
            width: 80mm;
            margin: 0 auto;
            padding: 4mm;
        }

        .center {
            text-align: center;
        }

        .logo {
            display: block;
            max-width: 45mm;
            max-height: 20mm;
            width: auto;
            height: auto;
            margin: 0 auto 5px;
            object-fit: contain;
        }

        .store-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .store-info {
            font-size: 9px;
            line-height: 1.4;
        }

        .header-text {
            font-size: 9px;
            line-height: 1.4;
            margin-top: 4px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 7px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info {
            font-size: 9px;
            line-height: 1.5;
        }

        .info td {
            padding: 1px 0;
            vertical-align: top;
        }

        .info-label {
            width: 30%;
        }

        .items {
            font-size: 9px;
        }

        .items td {
            padding: 2px 0;
            vertical-align: top;
        }

        .product-name {
            font-weight: 700;
            line-height: 1.4;
        }

        .product-detail {
            font-size: 8px;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
        }

        .summary {
            font-size: 9px;
        }

        .summary td {
            padding: 2px 0;
        }

        .summary-value {
            text-align: right;
            white-space: nowrap;
        }

        .grand-total td {
            font-size: 12px;
            font-weight: 700;
            padding: 4px 0;
        }

        .footer {
            text-align: center;
            font-size: 8px;
            line-height: 1.5;
            margin-top: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | SCREEN
        |--------------------------------------------------------------------------
        */

        @media screen {

            body {
                background: #f1f1f1;
            }

            .receipt {
                min-height: 100vh;
                background: #fff;
                box-shadow:
                    0 0 15px
                    rgba(0, 0, 0, .08);
            }

        }

        /*
        |--------------------------------------------------------------------------
        | PRINT 80MM
        |--------------------------------------------------------------------------
        */

        @media print {

            @page {
                size: 80mm auto;
                margin: 0;
            }

            html,
            body {
                width: 80mm;
                margin: 0;
                padding: 0;
            }

            .receipt {
                width: 80mm;
                margin: 0;
                padding: 3mm;
                box-shadow: none;
            }

        }

        /*
        |--------------------------------------------------------------------------
        | PRINT 58MM
        |--------------------------------------------------------------------------
        */

        @media print {

            body.receipt-58mm {
                width: 58mm;
            }

            body.receipt-58mm .receipt {
                width: 58mm;
                padding: 2mm;
            }

            body.receipt-58mm .store-name {
                font-size: 16px;
            }

            body.receipt-58mm .store-info,
            body.receipt-58mm .header-text {
                font-size: 8px;
            }

            body.receipt-58mm .info,
            body.receipt-58mm .items,
            body.receipt-58mm .summary {
                font-size: 8px;
            }

            body.receipt-58mm .product-detail {
                font-size: 7px;
            }

            body.receipt-58mm .grand-total td {
                font-size: 10px;
            }

            body.receipt-58mm .logo {
                max-width: 35mm;
                max-height: 15mm;
            }

            @page {
                size: 58mm auto;
                margin: 0;
            }

        }

    </style>

</head>

@php

    /*
    |--------------------------------------------------------------------------
    | RECEIPT SETTING
    |--------------------------------------------------------------------------
    |
    | Semua setting dibuat null-safe.
    | Kalau tenant belum memiliki setting, struk tetap berjalan.
    |
    */

    $receiptSetting = $receiptSetting ?? null;

    /*
    |--------------------------------------------------------------------------
    | STORE INFORMATION
    |--------------------------------------------------------------------------
    */

    $storeName = filled($receiptSetting?->store_name)
        ? $receiptSetting->store_name
        : ($tenant->name ?? 'Kasira');

    $storeAddress = $receiptSetting?->address ?? null;

    $storePhone = $receiptSetting?->phone ?? null;

    $storeEmail = $receiptSetting?->email ?? null;

    $headerText = $receiptSetting?->header_text ?? null;

    $footerText = $receiptSetting?->footer_text ?? null;

    /*
    |--------------------------------------------------------------------------
    | LOGO
    |--------------------------------------------------------------------------
    */

    $showLogo = (bool) (
        $receiptSetting?->show_logo ?? false
    );

    $logoUrl = null;

    if (
        $showLogo &&
        filled($receiptSetting?->logo)
    ) {
        $logoUrl = \Illuminate\Support\Facades\Storage::url(
            $receiptSetting->logo
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAPER SIZE
    |--------------------------------------------------------------------------
    */

    $size = request()->query(
        'size',
        '80mm'
    );

    $size = in_array(
        $size,
        ['58mm', '80mm'],
        true
    )
        ? $size
        : '80mm';

    /*
    |--------------------------------------------------------------------------
    | PAYMENT
    |--------------------------------------------------------------------------
    */

    $payment = $sale->payments->first();

    $method = strtolower(
        (string) ($payment?->method ?? '')
    );

    $paymentLabel = match ($method) {

        'cash' => 'Tunai',

        'qris' => 'QRIS',

        'transfer',
        'bank_transfer' => 'Transfer',

        'debit' => 'Debit',

        'credit',
        'credit_card' => 'Credit Card',

        default => $payment?->method
            ? ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $payment->method
                )
            )
            : '-',

    };

@endphp


<body
    class="{{ $size === '58mm'
        ? 'receipt-58mm'
        : 'receipt-80mm' }}"
>

<div class="receipt">


    {{-- 
    |--------------------------------------------------------------------------
    | STORE HEADER
    |--------------------------------------------------------------------------
    --}}

    <div class="center">

        @if ($logoUrl)

            <img
                src="{{ $logoUrl }}"
                alt="{{ $storeName }}"
                class="logo"
            >

        @endif


        <div class="store-name">

            {{ $storeName }}

        </div>


        @if ($storeAddress)

            <div class="store-info">

                {!! nl2br(e($storeAddress)) !!}

            </div>

        @endif


        @if ($storePhone)

            <div class="store-info">

                Telp: {{ $storePhone }}

            </div>

        @endif


        @if ($storeEmail)

            <div class="store-info">

                {{ $storeEmail }}

            </div>

        @endif


        @if ($headerText)

            <div class="header-text">

                {!! nl2br(e($headerText)) !!}

            </div>

        @endif

    </div>


    <div class="divider"></div>


    {{-- 
    |--------------------------------------------------------------------------
    | TRANSACTION INFORMATION
    |--------------------------------------------------------------------------
    --}}

    <table class="info">

        <tr>

            <td class="info-label">
                Invoice
            </td>

            <td>
                : {{ $sale->invoice_number }}
            </td>

        </tr>


        <tr>

            <td>
                Tanggal
            </td>

            <td>
                :
                {{ $sale->created_at?->format(
                    'd/m/Y H:i'
                ) }}
            </td>

        </tr>


        <tr>

            <td>
                Kasir
            </td>

            <td>
                :
                {{ $sale->user?->name ?? '-' }}
            </td>

        </tr>


        <tr>

            <td>
                Customer
            </td>

            <td>
                :
                {{ $sale->customer?->name
                    ?? 'Walk-in Customer' }}
            </td>

        </tr>


        @if ($sale->customer?->member_code)

            <tr>

                <td>
                    Member
                </td>

                <td>
                    :
                    {{ $sale->customer->member_code }}
                </td>

            </tr>

        @endif

    </table>


    <div class="divider"></div>


    {{-- 
    |--------------------------------------------------------------------------
    | ITEMS
    |--------------------------------------------------------------------------
    --}}

    <table class="items">

        @foreach ($sale->items as $item)

            <tr>

                <td colspan="2">

                    <div class="product-name">

                        {{ $item->product_name }}

                    </div>

                </td>

            </tr>


            <tr>

                <td>

                    <div class="product-detail">

                        {{ rtrim(
                            rtrim(
                                number_format(
                                    (float) $item->quantity,
                                    3,
                                    ',',
                                    '.'
                                ),
                                '0'
                            ),
                            ','
                        ) }}

                        x

                        Rp
                        {{ number_format(
                            (float) $item->unit_price,
                            0,
                            ',',
                            '.'
                        ) }}

                    </div>

                </td>


                <td class="amount">

                    Rp
                    {{ number_format(
                        (float) $item->total,
                        0,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>

        @endforeach

    </table>


    <div class="divider"></div>


    {{-- 
    |--------------------------------------------------------------------------
    | TOTAL
    |--------------------------------------------------------------------------
    --}}

    <table class="summary">

        <tr>

            <td>
                Subtotal (sblm PPN)
            </td>

            <td class="summary-value">

                Rp
                {{ number_format(
                    (float) $sale->subtotal - (float) $sale->tax,
                    0,
                    ',',
                    '.'
                ) }}

            </td>

        </tr>


        @if ((float) $sale->discount > 0)

            <tr>

                <td>
                    Diskon
                </td>

                <td class="summary-value">

                    - Rp
                    {{ number_format(
                        (float) $sale->discount,
                        0,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>

        @endif


        @if ((float) $sale->tax > 0)

            <tr>

                <td>
                    PPN ({{ $receiptSetting?->tax_rate ?? 11 }}%)
                </td>

                <td class="summary-value">

                    Rp
                    {{ number_format(
                        (float) $sale->tax,
                        0,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>

        @endif


        <tr class="grand-total">

            <td>
                TOTAL
            </td>

            <td class="summary-value">

                Rp
                {{ number_format(
                    (float) $sale->grand_total,
                    0,
                    ',',
                    '.'
                ) }}

            </td>

        </tr>

    </table>


    <div class="divider"></div>


    {{-- 
    |--------------------------------------------------------------------------
    | PAYMENT
    |--------------------------------------------------------------------------
    --}}

    <table class="summary">

        <tr>

            <td>
                Pembayaran
            </td>

            <td class="summary-value">

                {{ $paymentLabel }}

            </td>

        </tr>


        <tr>

            <td>
                Dibayar
            </td>

            <td class="summary-value">

                Rp
                {{ number_format(
                    (float) $sale->paid_amount,
                    0,
                    ',',
                    '.'
                ) }}

            </td>

        </tr>


        @if ((float) $sale->change_amount > 0)

            <tr>

                <td>
                    Kembalian
                </td>

                <td class="summary-value">

                    Rp
                    {{ number_format(
                        (float) $sale->change_amount,
                        0,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>

        @endif

    </table>


    <div class="divider"></div>


    {{-- 
    |--------------------------------------------------------------------------
    | MEMBER INFORMATION
    |--------------------------------------------------------------------------
    --}}

    @if ($sale->customer?->is_member)

        <table class="summary">

            <tr>

                <td>
                    Member
                </td>

                <td class="summary-value">

                    {{ $sale->customer->member_code ?? '-' }}

                </td>

            </tr>


            <tr>

                <td>
                    Level
                </td>

                <td class="summary-value">

                    {{ $sale->customer->member_level ?? 'Bronze' }}

                </td>

            </tr>


            <tr>

                <td>
                    Points
                </td>

                <td class="summary-value">

                    {{ number_format(
                        (int) ($sale->customer->points ?? 0),
                        0,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>

        </table>


        <div class="divider"></div>

    @endif


    {{-- 
    |--------------------------------------------------------------------------
    | FOOTER
    |--------------------------------------------------------------------------
    --}}

    <div class="footer">

        @if ($footerText)

            {!! nl2br(e($footerText)) !!}

        @else

            <strong>
                Terima kasih telah berbelanja
            </strong>

            <br>

            Simpan struk ini sebagai bukti pembayaran.

            @if ((float) $sale->tax > 0)
                <br><br>
                <em>* Harga sudah termasuk PPN</em>
            @endif

            <br><br>

            Kasira POS

        @endif

    </div>


</div>


<script>

    window.addEventListener(
        'load',
        function () {

            setTimeout(
                function () {

                    window.print();

                },
                500
            );

        }
    );


    window.addEventListener(
        'afterprint',
        function () {

            setTimeout(
                function () {

                    window.close();

                },
                300
            );

        }
    );

</script>

</body>

</html>