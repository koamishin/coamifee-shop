<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Ticket - Order #{{ $order->id }}</title>
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

        .ticket {
            width: 100%;
            padding: 2mm 0.5mm;
            text-align: center;
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 1mm;
            margin-bottom: 1.5mm;
        }

        .header h1 {
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .header p {
            font-size: 6pt;
            margin-top: 0.5mm;
        }

        .order-info {
            margin-bottom: 1.5mm;
            font-size: 7pt;
            line-height: 1.2;
            margin-left: auto;
            margin-right: auto;
            width: 95%;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5mm;
        }

        .info-label {
            font-weight: bold;
        }

        .items-section {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 1mm 0;
            margin-bottom: 1.5mm;
        }

        .items-title {
            font-size: 7pt;
            font-weight: bold;
            margin-bottom: 1mm;
            text-align: center;
        }

        .item {
            margin-bottom: 1mm;
            font-size: 7pt;
            line-height: 1.1;
        }

        .item-name {
            font-weight: bold;
        }

        .item-quantity {
            margin-top: 0.3mm;
        }

        .item-variant {
            font-size: 5pt;
            color: #333;
            margin-top: 0.3mm;
        }

        .special-notes {
            background: #f5f5f5;
            padding: 1mm;
            margin-bottom: 1.5mm;
            font-size: 6pt;
            border: 1px solid #ddd;
            margin-left: auto;
            margin-right: auto;
            width: 95%;
        }

        .special-notes-title {
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        .addons {
            background: #fffacd;
            padding: 1mm;
            margin-bottom: 1.5mm;
            font-size: 6pt;
            border: 1px solid #daa;
            margin-left: auto;
            margin-right: auto;
            width: 95%;
        }

        .addons-title {
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        .addon-item {
            margin-bottom: 0.3mm;
        }

        .footer {
            text-align: center;
            font-size: 6pt;
            padding-top: 1mm;
            border-top: 1px dashed #000;
        }

        .order-time {
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        .print-date {
            font-size: 5pt;
            color: #666;
        }

        .customer-name {
            font-weight: bold;
            text-align: center;
            margin-bottom: 1mm;
            font-size: 7pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 0.3mm 0;
            font-size: 6pt;
        }

        td:first-child {
            text-align: left;
        }

        td:last-child {
            text-align: right;
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

            .ticket {
                margin: 0 auto;
                padding: 1mm 0.5mm;
                page-break-after: avoid;
                page-break-inside: avoid;
                height: auto;
                max-height: 195mm;
                overflow: hidden;
            }

            .header, .items-section, .special-notes, .addons, .footer {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        {{-- Header --}}
        <div class="header">
            <h1>KITCHEN TICKET</h1>
            <p>Order #{{ $order->id }}</p>
        </div>

        {{-- Customer Info --}}
        <div class="customer-name">
            {{ $order->customer_name }}
            @if($order->table_number)
                <br>Table: {{ $order->table_number }}
            @endif
        </div>

        {{-- Order Info --}}
        <div class="order-info">
            <div class="info-row">
                <span class="info-label">Time:</span>
                <span>{{ $order->created_at->now()->setTimezone('Asia/Manila')->format('H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Order Type:</span>
                <span>{{ ucfirst($order->order_type) }}</span>
            </div>
            @if($order->order_type === 'delivery' && $order->delivery_address)
                <div class="info-row">
                    <span class="info-label">Address:</span>
                </div>
                <p style="font-size: 8pt; margin-left: 0; word-wrap: break-word;">{{ $order->delivery_address }}</p>
            @endif
        </div>

        {{-- Items Section --}}
        <div class="items-section">
            <div class="items-title">ITEMS TO PREPARE</div>

            @foreach($order->items as $item)
                <div class="item">
                    <div class="item-name">
                        {{ $item->quantity }}x {{ Str::limit($item->product->name, 30, '...') }}
                    </div>

                    @if($item->variant_name)
                        <div class="item-variant">
                            {{ ucfirst(Str::limit($item->variant_name, 25, '...')) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Add-ons Section --}}
        @if($order->add_ons && count($order->add_ons) > 0)
            <div class="addons">
                <div class="addons-title">ADD-ONS:</div>
                @foreach($order->add_ons as $addOn)
                    <div class="addon-item">
                        • {{ $addOn['name'] }}
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Special Notes --}}
        @if($order->notes)
            <div class="special-notes">
                <div class="special-notes-title">SPECIAL INSTRUCTIONS:</div>
                <p>{{ $order->notes }}</p>
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <div class="order-time">
                Order #{{ $order->id }}
            </div>
            <div class="print-date">
                {{ now()->setTimezone('Asia/Manila')->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</body>
</html>
