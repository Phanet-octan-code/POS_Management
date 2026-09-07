<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;
use App\Services\ActivityLoggerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PosReceiptController extends Controller
{
    /**
     * Retrieve standard store branding and contact metadata.
     */
    protected function getStoreMetadata(): array
    {
        return [
            'store_name' => Setting::get('store_name', 'OmniPOS Superstore'),
            'store_address' => Setting::get('store_address', '100 Downtown Boulevard, Metropolis'),
            'store_phone' => Setting::get('store_phone', '+1 (555) 019-2831'),
            'store_email' => Setting::get('store_email', 'contact@omnipos.com'),
            'store_website' => Setting::get('store_website', 'www.omnipos.com'),
            'store_logo' => Setting::get('store_logo', 'images/store-logo.svg'),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
            'store_tax' => Setting::get('store_tax', '10'),
            'receipt_footer' => Setting::get('receipt_footer', 'Thank you for your purchase! Returns accepted within 14 days with original receipt.'),
        ];
    }

    /**
     * Display printable receipt with live format switcher (58mm, 80mm, A4).
     */
    public function show(Request $request, Sale $sale): View
    {
        $sale->load(['items.product', 'customer', 'user']);
        $defaultFormat = Setting::get('receipt_size', '80mm');
        $format = strtolower($request->get('format', $defaultFormat));

        if (!in_array($format, ['58mm', '80mm', 'a4'])) {
            $format = in_array($defaultFormat, ['58mm', '80mm', 'a4']) ? $defaultFormat : '80mm';
        }

        $store = $this->getStoreMetadata();
        $isReprint = $sale->reprint_count > 0;

        return view('pos.receipts.receipt', compact('sale', 'format', 'store', 'isReprint'));
    }

    /**
     * Generate and download PDF receipt using DomPDF for 58mm, 80mm, or A4.
     */
    public function downloadPdf(Request $request, Sale $sale): Response
    {
        $sale->load(['items.product', 'customer', 'user']);
        $defaultFormat = Setting::get('receipt_size', 'a4');
        $format = strtolower($request->get('format', $defaultFormat));

        if (!in_array($format, ['58mm', '80mm', 'a4'])) {
            $format = 'a4';
        }

        $store = $this->getStoreMetadata();
        $isReprint = $sale->reprint_count > 0;

        // Base64 encode logo for 100% reliable DomPDF rendering without local filesystem resolution issues
        $logoPath = public_path($store['store_logo']);
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = match($extension) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                default => 'image/png',
            };
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('pos.receipts.pdf', compact('sale', 'format', 'store', 'isReprint', 'logoBase64'));

        // Configure paper size: 58mm (164.4pt), 80mm (226.8pt), A4 (595.3 x 841.9pt)
        if ($format === '58mm') {
            // Estimate height based on items count
            $height = max(500, 320 + ($sale->items->count() * 45));
            $pdf->setPaper([0, 0, 164.41, $height], 'portrait');
        } elseif ($format === '80mm') {
            $height = max(550, 360 + ($sale->items->count() * 40));
            $pdf->setPaper([0, 0, 226.77, $height], 'portrait');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        $filename = "Receipt-{$sale->invoice_no}-{$format}.pdf";

        if ($request->boolean('stream')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Record receipt reprint, audit logging, and redirect to view.
     */
    public function reprint(Request $request, Sale $sale): RedirectResponse|JsonResponse
    {
        $sale->increment('reprint_count');

        ActivityLoggerService::log(
            'sale.reprinted',
            "Reprinted receipt for sale invoice {$sale->invoice_no} (Reprint #{$sale->reprint_count})",
            $sale
        );

        $format = $request->get('format', '80mm');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Receipt #{$sale->invoice_no} marked as reprinted.",
                'reprint_count' => $sale->reprint_count,
                'receipt_url' => route('pos.receipt.show', ['sale' => $sale, 'format' => $format]),
            ]);
        }

        return redirect()->route('pos.receipt.show', [
            'sale' => $sale,
            'format' => $format,
        ]);
    }
}
