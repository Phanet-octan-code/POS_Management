<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReturnRequest;
use App\Models\ReturnOrder;
use App\Models\Sale;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ReturnController extends Controller
{
    public function __construct(
        protected ReturnService $returnService
    ) {}

    /**
     * Display a paginated listing of completed product returns.
     */
    public function index(Request $request): View
    {
        $query = ReturnOrder::with(['sale', 'customer', 'user', 'items.product']);

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('sale', fn($sq) => $sq->where('invoice_no', 'like', "%{$search}%"))
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        if ($startDate = $request->get('start_date')) {
            $query->whereDate('return_date', '>=', $startDate);
        }

        if ($endDate = $request->get('end_date')) {
            $query->whereDate('return_date', '<=', $endDate);
        }

        $returns = $query->latest('return_date')->paginate(15)->withQueryString();

        // Summary Statistics
        $totalRefundsSum = (float) (clone $query)->sum('total_refund');
        $totalReturnsCount = (clone $query)->count();

        return view('returns.index', compact('returns', 'totalRefundsSum', 'totalReturnsCount'));
    }

    /**
     * Display the interface to process a new product return.
     */
    public function create(Request $request): View
    {
        $prefillInvoice = trim($request->get('invoice_no', ''));
        if (!$prefillInvoice && $saleId = $request->get('sale_id')) {
            $sale = Sale::find($saleId);
            $prefillInvoice = $sale?->invoice_no ?? '';
        }

        $initialData = null;
        $errorMessage = null;

        if ($prefillInvoice) {
            try {
                $initialData = $this->returnService->getInvoiceReturnableDetails($prefillInvoice);
            } catch (InvalidArgumentException $e) {
                $errorMessage = $e->getMessage();
            }
        }

        return view('returns.create', compact('prefillInvoice', 'initialData', 'errorMessage'));
    }

    /**
     * AJAX endpoint to search an invoice and fetch returnable items and limits.
     */
    public function searchSale(Request $request): JsonResponse
    {
        $invoiceNo = trim($request->get('invoice_no', ''));

        if (!$invoiceNo) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter or scan an invoice number.',
            ], 400);
        }

        try {
            $data = $this->returnService->getInvoiceReturnableDetails($invoiceNo);

            return response()->json([
                'success' => true,
                'sale' => [
                    'id' => $data['sale']->id,
                    'invoice_no' => $data['sale']->invoice_no,
                    'sale_date' => $data['sale']->sale_date->format('Y-m-d H:i'),
                    'customer_name' => $data['sale']->customer?->name ?? 'Walk-in Customer',
                    'cashier_name' => $data['sale']->user?->name ?? 'Staff',
                    'total_amount' => number_format($data['sale']->total_amount, 2),
                    'payment_method' => $data['sale']->payment_method_label,
                    'payment_status' => $data['sale']->payment_status_label,
                ],
                'items' => $data['items'],
                'total_returnable_units' => $data['total_returnable_units'],
                'is_fully_returned' => $data['is_fully_returned'],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process and complete the product return.
     */
    public function store(ReturnRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $returnOrder = $this->returnService->processReturn($request->validated());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Return #{$returnOrder->return_number} processed successfully.",
                    'return_id' => $returnOrder->id,
                    'return_number' => $returnOrder->return_number,
                    'total_refund' => number_format($returnOrder->total_refund, 2),
                    'redirect_url' => route('returns.show', $returnOrder),
                ]);
            }

            return redirect()->route('returns.show', $returnOrder)
                ->with('success', "Return #{$returnOrder->return_number} processed successfully with refund of \${$returnOrder->total_refund}.");
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing the return: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Display details and printable return slip for a completed return.
     */
    public function show(ReturnOrder $return): View
    {
        $return->load(['sale.customer', 'customer', 'user', 'items.product']);
        return view('returns.show', compact('return'));
    }
}
