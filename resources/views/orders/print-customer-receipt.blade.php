<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Order #{{ $order->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            width: 58mm;
            padding: 0;
            background: white;
            color: #000;
            margin: 0 auto;
        }

        .receipt {
            width: 100%;
            padding: 2mm 0.5mm;
            text-align: center;
        }

        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 1mm;
            margin-bottom: 1.5mm;
        }

        .header h1 {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        .header p {
            font-size: 6pt;
            margin: 0.3mm 0;
        }

        .order-header {
            font-size: 7pt;
            margin-bottom: 1.5mm;
            text-align: center;
        }

        .order-header p {
            margin: 0.3mm 0;
        }

        .items-table {
            width: 100%;
            margin-bottom: 1.5mm;
            border-collapse: collapse;
            font-size: 7pt;
            margin-left: auto;
            margin-right: auto;
        }

        .items-table tr {
            border-bottom: 1px dashed #999;
        }

        .items-table td {
            padding: 0.5mm 0;
            text-align: center;
            vertical-align: top;
        }

        .item-name {
            text-align: left;
            width: 55%;
            font-size: 6pt;
            line-height: 1.2;
        }

        .item-qty {
            text-align: center;
            width: 15%;
        }

        .item-price {
            text-align: right;
            width: 30%;
        }

        .items-header {
            font-weight: bold;
            margin-bottom: 0.5mm;
            padding-bottom: 0.5mm;
            border-bottom: 1px solid #000;
            font-size: 6pt;
        }

        .totals {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 1mm 0;
            margin-bottom: 1.5mm;
            font-size: 7pt;
            margin-left: auto;
            margin-right: auto;
            width: 95%;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5mm;
        }

        .total-row.subtotal {
            font-weight: normal;
        }

        .total-row.discount {
            color: #008000;
        }

        .total-row.addons {
            color: #0066cc;
        }

        .total-row.final {
            font-weight: bold;
            font-size: 8pt;
            margin-top: 0.5mm;
            padding-top: 0.5mm;
            border-top: 1px dashed #000;
        }

        .payment-info {
            background: #f5f5f5;
            padding: 1mm;
            margin-bottom: 1.5mm;
            border: 1px solid #ddd;
            font-size: 6pt;
            margin-left: auto;
            margin-right: auto;
            width: 95%;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.3mm;
        }

        .payment-row:last-child {
            margin-bottom: 0;
        }

        .payment-label {
            font-weight: bold;
        }

        .footer {
            text-align: center;
            font-size: 6pt;
            padding-top: 1mm;
        }

        .thank-you {
            font-weight: bold;
            margin-bottom: 0.5mm;
            font-size: 7pt;
        }

        .receipt-number {
            font-size: 5pt;
            color: #666;
            margin-bottom: 0.3mm;
        }

        .notes-section {
            background: #fffacd;
            padding: 1mm;
            margin-bottom: 1.5mm;
            font-size: 6pt;
            border: 1px solid #daa;
            margin-left: auto;
            margin-right: auto;
            width: 95%;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        @media print {
            @page {
                margin: 0;
                size: 58mm auto;
                max-height: 200mm;
            }

            body {
                margin: 0;
                padding: 0;
                height: auto;
                overflow: hidden;
            }

            .receipt {
                margin: 0;
                padding: 1mm 0.5mm;
                page-break-after: avoid;
                page-break-inside: avoid;
                height: auto;
                max-height: 195mm;
                overflow: hidden;
            }

            .items-table {
                page-break-inside: avoid;
            }

            .totals, .payment-info, .notes-section, .footer {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        {{-- Header --}}
        <div class="header">
            <h1>RECEIPT</h1>
            <p>Order #{{ $order->id }}</p>
        </div>

        {{-- Order Header --}}
        
        <div class="order-header">
            <p>{{ $order->created_at->now()->setTimezone('Asia/Manila')->format('d/m/Y H:i') }}</p>
            <p style="font-weight: bold;">{{ $order->customer_name }}</p>
            @if($order->table_number)
                <p>Table {{ $order->table_number }}</p>
            @endif
        </div>

        {{-- Items --}}
        <table class="items-table">
            <tr class="items-header">
                <td class="item-name">Item</td>
                <td class="item-qty">Qty</td>
                <td class="item-price">Price</td>
            </tr>
            @foreach($order->items as $item)
                <tr>
                    <td class="item-name">
                        {{ Str::limit($item->product->name, 25, '...') }}
                        @if($item->variant_name)
                            <br><span style="font-size: 5pt;">({{ Str::limit($item->variant_name, 20, '...') }})</span>
                        @endif
                    </td>
                    <td class="item-qty">{{ $item->quantity }}</td>
                    <td class="item-price">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </table>

        {{-- Add-ons Section --}}
        @if($order->add_ons && count($order->add_ons) > 0)
            <table class="items-table">
                <tr class="items-header">
                    <td colspan="3" style="text-align: center;">Add-ons</td>
                </tr>
                @foreach($order->add_ons as $addOn)
                    <tr>
                        <td class="item-name">{{ $addOn['name'] }}</td>
                        <td class="item-qty">1</td>
                        <td class="item-price">{{ number_format($addOn['price'], 2) }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        {{-- Totals --}}
        <div class="totals">
            <div class="total-row subtotal">
                <span>Subtotal:</span>
                <span>{{ number_format($order->subtotal ?? $order->total, 2) }}</span>
            </div>

            @if($order->discount_amount > 0)
                <div class="total-row discount">
                    <span>Discount:</span>
                    <span>-{{ number_format($order->discount_amount, 2) }}</span>
                </div>
            @endif

            @if($order->add_ons_total > 0)
                <div class="total-row addons">
                    <span>Add-ons:</span>
                    <span>+{{ number_format($order->add_ons_total, 2) }}</span>
                </div>
            @endif

            <div class="total-row final">
                <span>TOTAL:</span>
                <span>{{ number_format($order->total, 2) }}</span>
            </div>
        </div>

        {{-- Payment Info --}}
        @if($order->payment_status === 'paid')
            <div class="payment-info">
                <div class="payment-row">
                    <span class="payment-label">Payment Method:</span>
                    <span>{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}</span>
                </div>
                @if($order->payment_method === 'cash')
                    @if($order->change_amount > 0)
                        <div class="payment-row">
                            <span class="payment-label">Change:</span>
                            <span>{{ number_format($order->change_amount, 2) }}</span>
                        </div>
                    @endif
                @endif
                <div class="payment-row">
                    <span class="payment-label">Status:</span>
                    <span>PAID</span>
                </div>
            </div>
        @endif

        {{-- Notes --}}
        @if($order->notes)
            <div class="notes-section">
                <div class="notes-title">Notes:</div>
                <p>{{ $order->notes }}</p>
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <div class="thank-you">Thank You!</div>
            <div class="receipt-number">
                Receipt #{{ $order->id }}
            </div>
            <div class="receipt-number">
                {{ now()->setTimezone('Asia/Manila')->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</body>
</html>
