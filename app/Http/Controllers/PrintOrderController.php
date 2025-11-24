<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class PrintOrderController extends Controller
{
    public function printKitchenTicket(Order $order): Response
    {
        // Load order with relationships
        $order->load(['items.product', 'items.variant']);

        // Generate PDF with thermal receipt dimensions (80mm wide)
        $pdf = Pdf::loadView('orders.print-kitchen-ticket', [
            'order' => $order,
        ])->setPaper([0, 0, 226.77, 999], 'portrait'); // 80mm width in points (80mm = 226.77pt)

        return $pdf->stream('order-'.$order->id.'-kitchen.pdf');
    }

    public function printCustomerReceipt(Order $order): Response
    {
        // Load order with relationships
        $order->load(['items.product', 'items.variant']);

        // Generate PDF with thermal receipt dimensions (80mm wide)
        $pdf = Pdf::loadView('orders.print-customer-receipt', [
            'order' => $order,
        ])->setPaper([0, 0, 226.77, 999], 'portrait');

        return $pdf->stream('receipt-'.$order->id.'.pdf');
    }
}
