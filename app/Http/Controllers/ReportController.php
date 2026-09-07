<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Report Hub: Defaults to Sales Report.
     */
    public function index(Request $request): View|RedirectResponse
    {
        return redirect()->route('reports.sales', $request->query());
    }

    /**
     * 1. SALES REPORT
     */
    public function sales(Request $request): View|Response|StreamedResponse
    {
        $dateRange = $this->reportService->resolveDateRange($request);
        $export = strtolower($request->get('export', ''));
        $isExport = in_array($export, ['pdf', 'csv', 'excel']);

        $report = $this->reportService->getSalesReport($dateRange, $request->all(), $isExport);
        $records = $report['records'];
        $summary = $report['summary'];

        // PDF / CSV / Excel Export Dispatch
        if ($export === 'csv') {
            $headers = ['Invoice #', 'Sale Date', 'Customer', 'Cashier', 'Subtotal ($)', 'Discount ($)', 'Tax ($)', 'Total Amount ($)', 'Payment Method', 'Payment Status'];
            $rows = $records->map(fn($s) => [
                $s->invoice_no,
                $s->sale_date->format('Y-m-d H:i'),
                $s->customer?->name ?? 'Walk-in Customer',
                $s->user?->name ?? 'Staff',
                number_format($s->subtotal, 2),
                number_format($s->discount_amount, 2),
                number_format($s->tax_amount, 2),
                number_format($s->total_amount, 2),
                ucfirst($s->payment_method),
                ucfirst($s->payment_status),
            ])->toArray();

            return $this->reportService->exportCsv($headers, $rows, "Sales-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.csv");
        }

        if ($export === 'pdf' || $export === 'excel') {
            $headers = [
                ['label' => 'Invoice #', 'class' => 'text-left'],
                ['label' => 'Sale Date', 'class' => 'text-center'],
                ['label' => 'Customer', 'class' => 'text-left'],
                ['label' => 'Cashier', 'class' => 'text-left'],
                ['label' => 'Subtotal', 'class' => 'text-right'],
                ['label' => 'Discount', 'class' => 'text-right'],
                ['label' => 'Tax', 'class' => 'text-right'],
                ['label' => 'Total Amount', 'class' => 'text-right fw-bold'],
                ['label' => 'Payment', 'class' => 'text-center'],
                ['label' => 'Status', 'class' => 'text-center'],
            ];

            $rows = $records->map(fn($s) => [
                $s->invoice_no,
                $s->sale_date->format('M d, Y H:i'),
                $s->customer?->name ?? 'Walk-in',
                $s->user?->name ?? 'Staff',
                '$' . number_format($s->subtotal, 2),
                '-$' . number_format($s->discount_amount, 2),
                '$' . number_format($s->tax_amount, 2),
                '$' . number_format($s->total_amount, 2),
                ucfirst($s->payment_method),
                ucfirst($s->payment_status),
            ])->toArray();

            $kpis = [
                ['label' => 'Total Revenue', 'value' => '$' . number_format($summary['total_sales'], 2), 'style' => 'color: #0d6efd;'],
                ['label' => 'Total Invoices', 'value' => number_format($summary['total_invoices'])],
                ['label' => 'Avg Order Value', 'value' => '$' . number_format($summary['avg_order_value'], 2)],
                ['label' => 'Total Discounts', 'value' => '-$' . number_format($summary['total_discount'], 2), 'style' => 'color: #dc2626;'],
                ['label' => 'Total Tax Collected', 'value' => '$' . number_format($summary['total_tax'], 2)],
            ];

            $data = [
                'title' => 'Sales Summary Report',
                'dateRange' => $dateRange,
                'headers' => $headers,
                'rows' => $rows,
                'kpis' => $kpis,
            ];

            if ($export === 'pdf') {
                return $this->reportService->exportPdf('reports.pdf.template', $data, "Sales-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.pdf", 'landscape');
            }

            return $this->reportService->exportExcel('reports.excel.template', $data, "Sales-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.xls");
        }

        return view('reports.sales', compact('records', 'summary', 'dateRange'));
    }

    /**
     * 2. PURCHASE REPORT
     */
    public function purchases(Request $request): View|Response|StreamedResponse
    {
        $dateRange = $this->reportService->resolveDateRange($request);
        $export = strtolower($request->get('export', ''));
        $isExport = in_array($export, ['pdf', 'csv', 'excel']);

        $report = $this->reportService->getPurchaseReport($dateRange, $request->all(), $isExport);
        $records = $report['records'];
        $summary = $report['summary'];

        if ($export === 'csv') {
            $headers = ['Purchase #', 'Purchase Date', 'Supplier', 'Items Count', 'Total Amount ($)', 'Paid ($)', 'Due ($)', 'Payment Status'];
            $rows = $records->map(fn($p) => [
                $p->purchase_number,
                $p->purchase_date->format('Y-m-d'),
                $p->supplier?->name ?? 'N/A',
                $p->items?->count() ?? 0,
                number_format($p->total_amount, 2),
                number_format($p->paid_amount, 2),
                number_format($p->due_amount, 2),
                ucfirst($p->payment_status),
            ])->toArray();

            return $this->reportService->exportCsv($headers, $rows, "Purchase-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.csv");
        }

        if ($export === 'pdf' || $export === 'excel') {
            $headers = [
                ['label' => 'Purchase #', 'class' => 'text-left'],
                ['label' => 'Date', 'class' => 'text-center'],
                ['label' => 'Supplier', 'class' => 'text-left'],
                ['label' => 'Items', 'class' => 'text-center'],
                ['label' => 'Total Amount', 'class' => 'text-right fw-bold'],
                ['label' => 'Paid Amount', 'class' => 'text-right'],
                ['label' => 'Due Amount', 'class' => 'text-right text-danger'],
                ['label' => 'Status', 'class' => 'text-center'],
            ];

            $rows = $records->map(fn($p) => [
                $p->purchase_number,
                $p->purchase_date->format('M d, Y'),
                $p->supplier?->name ?? 'N/A',
                $p->items?->count() ?? 0,
                '$' . number_format($p->total_amount, 2),
                '$' . number_format($p->paid_amount, 2),
                '$' . number_format($p->due_amount, 2),
                ucfirst($p->payment_status),
            ])->toArray();

            $kpis = [
                ['label' => 'Total Procurement', 'value' => '$' . number_format($summary['total_purchases'], 2), 'style' => 'color: #0d6efd;'],
                ['label' => 'Total Orders', 'value' => number_format($summary['total_orders'])],
                ['label' => 'Total Paid', 'value' => '$' . number_format($summary['total_paid'], 2), 'style' => 'color: #16a34a;'],
                ['label' => 'Total Due / Payable', 'value' => '$' . number_format($summary['total_due'], 2), 'style' => 'color: #dc2626;'],
            ];

            $data = [
                'title' => 'Procurement & Purchase Report',
                'dateRange' => $dateRange,
                'headers' => $headers,
                'rows' => $rows,
                'kpis' => $kpis,
            ];

            if ($export === 'pdf') {
                return $this->reportService->exportPdf('reports.pdf.template', $data, "Purchase-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.pdf", 'landscape');
            }

            return $this->reportService->exportExcel('reports.excel.template', $data, "Purchase-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.xls");
        }

        return view('reports.purchases', compact('records', 'summary', 'dateRange'));
    }

    /**
     * 3. PROFIT REPORT
     * Gross Profit = Sales Revenue - Product Cost
     * Net Profit = Gross Profit - Expenses
     */
    public function profit(Request $request): View|Response|StreamedResponse
    {
        $dateRange = $this->reportService->resolveDateRange($request);
        $export = strtolower($request->get('export', ''));
        $isExport = in_array($export, ['pdf', 'csv', 'excel']);

        $report = $this->reportService->getProfitReport($dateRange, $request->all(), $isExport);
        $records = $report['records'];
        $summary = $report['summary'];

        if ($export === 'csv') {
            $headers = ['Invoice #', 'Sale Date', 'Customer', 'Units Sold', 'Sales Revenue ($)', 'Product Cost / COGS ($)', 'Gross Profit ($)', 'Gross Margin (%)'];
            $rows = $records->map(fn($s) => [
                $s->invoice_no,
                $s->sale_date->format('Y-m-d H:i'),
                $s->customer?->name ?? 'Walk-in Customer',
                $s->items_count_total,
                number_format($s->total_amount, 2),
                number_format($s->calculated_cogs, 2),
                number_format($s->calculated_gross_profit, 2),
                number_format($s->calculated_margin, 2) . '%',
            ])->toArray();

            return $this->reportService->exportCsv($headers, $rows, "Profit-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.csv");
        }

        if ($export === 'pdf' || $export === 'excel') {
            $headers = [
                ['label' => 'Invoice #', 'class' => 'text-left'],
                ['label' => 'Sale Date', 'class' => 'text-center'],
                ['label' => 'Customer', 'class' => 'text-left'],
                ['label' => 'Units Sold', 'class' => 'text-center'],
                ['label' => 'Revenue', 'class' => 'text-right'],
                ['label' => 'Product Cost (COGS)', 'class' => 'text-right'],
                ['label' => 'Gross Profit', 'class' => 'text-right fw-bold'],
                ['label' => 'Gross Margin', 'class' => 'text-center'],
            ];

            $rows = $records->map(fn($s) => [
                $s->invoice_no,
                $s->sale_date->format('M d, Y H:i'),
                $s->customer?->name ?? 'Walk-in',
                $s->items_count_total,
                '$' . number_format($s->total_amount, 2),
                '$' . number_format($s->calculated_cogs, 2),
                '$' . number_format($s->calculated_gross_profit, 2),
                number_format($s->calculated_margin, 1) . '%',
            ])->toArray();

            $kpis = [
                ['label' => 'Gross Sales Revenue', 'value' => '$' . number_format($summary['total_revenue'], 2)],
                ['label' => 'Product Cost (COGS)', 'value' => '$' . number_format($summary['total_cogs'], 2)],
                ['label' => 'Gross Profit (Rev - COGS)', 'value' => '$' . number_format($summary['gross_profit'], 2), 'style' => 'color: #0d6efd;'],
                ['label' => 'Operating Expenses', 'value' => '-$' . number_format($summary['total_expenses'], 2), 'style' => 'color: #dc2626;'],
                ['label' => 'Net Profit (Gross - Exp)', 'value' => '$' . number_format($summary['net_profit'], 2), 'style' => 'color: #16a34a; font-weight: bold;'],
            ];

            $data = [
                'title' => 'Profitability Statement & Report',
                'dateRange' => $dateRange,
                'headers' => $headers,
                'rows' => $rows,
                'kpis' => $kpis,
            ];

            if ($export === 'pdf') {
                return $this->reportService->exportPdf('reports.pdf.template', $data, "Profit-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.pdf", 'landscape');
            }

            return $this->reportService->exportExcel('reports.excel.template', $data, "Profit-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.xls");
        }

        return view('reports.profit', compact('records', 'summary', 'dateRange'));
    }

    /**
     * 4. INVENTORY REPORT
     */
    public function inventory(Request $request): View|Response|StreamedResponse
    {
        $dateRange = $this->reportService->resolveDateRange($request);
        $export = strtolower($request->get('export', ''));
        $isExport = in_array($export, ['pdf', 'csv', 'excel']);

        $report = $this->reportService->getInventoryReport($request->all(), $isExport);
        $records = $report['records'];
        $summary = $report['summary'];

        if ($export === 'csv') {
            $headers = ['Product Name', 'SKU', 'Category', 'Cost Price ($)', 'Selling Price ($)', 'In Stock', 'Alert Qty', 'Total Cost Value ($)', 'Total Retail Value ($)', 'Potential Profit ($)', 'Status'];
            $rows = $records->map(fn($p) => [
                $p->name,
                $p->sku,
                $p->category?->name ?? 'N/A',
                number_format($p->cost_price, 2),
                number_format($p->selling_price, 2),
                $p->stock_quantity,
                $p->alert_quantity,
                number_format(max(0, $p->stock_quantity) * $p->cost_price, 2),
                number_format(max(0, $p->stock_quantity) * $p->selling_price, 2),
                number_format(max(0, $p->stock_quantity) * ($p->selling_price - $p->cost_price), 2),
                $p->stock_status_label,
            ])->toArray();

            return $this->reportService->exportCsv($headers, $rows, "Inventory-Report-" . date('Y-m-d') . ".csv");
        }

        if ($export === 'pdf' || $export === 'excel') {
            $headers = [
                ['label' => 'Product Name', 'class' => 'text-left'],
                ['label' => 'SKU', 'class' => 'text-left'],
                ['label' => 'Category', 'class' => 'text-left'],
                ['label' => 'Cost', 'class' => 'text-right'],
                ['label' => 'Price', 'class' => 'text-right'],
                ['label' => 'Stock', 'class' => 'text-center fw-bold'],
                ['label' => 'Alert', 'class' => 'text-center'],
                ['label' => 'Cost Value', 'class' => 'text-right'],
                ['label' => 'Retail Value', 'class' => 'text-right'],
                ['label' => 'Potential Profit', 'class' => 'text-right fw-bold'],
                ['label' => 'Status', 'class' => 'text-center'],
            ];

            $rows = $records->map(fn($p) => [
                $p->name,
                $p->sku,
                $p->category?->name ?? 'N/A',
                '$' . number_format($p->cost_price, 2),
                '$' . number_format($p->selling_price, 2),
                $p->stock_quantity . ' ' . $p->unit,
                $p->alert_quantity . ' ' . $p->unit,
                '$' . number_format(max(0, $p->stock_quantity) * $p->cost_price, 2),
                '$' . number_format(max(0, $p->stock_quantity) * $p->selling_price, 2),
                '$' . number_format(max(0, $p->stock_quantity) * ($p->selling_price - $p->cost_price), 2),
                $p->stock_status_label,
            ])->toArray();

            $kpis = [
                ['label' => 'Total Products', 'value' => number_format($summary['total_items'])],
                ['label' => 'Total Units in Stock', 'value' => number_format($summary['total_units'])],
                ['label' => 'Total Inventory Cost Value', 'value' => '$' . number_format($summary['total_cost_value'], 2), 'style' => 'color: #0d6efd;'],
                ['label' => 'Total Potential Retail Value', 'value' => '$' . number_format($summary['total_retail_value'], 2)],
                ['label' => 'Potential Gross Margin', 'value' => '$' . number_format($summary['potential_profit'], 2), 'style' => 'color: #16a34a;'],
            ];

            $data = [
                'title' => 'Inventory Valuation & Stock Report',
                'dateRange' => ['label' => 'Current Live Stock Position (' . date('M d, Y') . ')'],
                'headers' => $headers,
                'rows' => $rows,
                'kpis' => $kpis,
            ];

            if ($export === 'pdf') {
                return $this->reportService->exportPdf('reports.pdf.template', $data, "Inventory-Report-" . date('Y-m-d') . ".pdf", 'landscape');
            }

            return $this->reportService->exportExcel('reports.excel.template', $data, "Inventory-Report-" . date('Y-m-d') . ".xls");
        }

        return view('reports.inventory', compact('records', 'summary', 'dateRange'));
    }

    /**
     * 5. CUSTOMER REPORT
     */
    public function customers(Request $request): View|Response|StreamedResponse
    {
        $dateRange = $this->reportService->resolveDateRange($request);
        $export = strtolower($request->get('export', ''));
        $isExport = in_array($export, ['pdf', 'csv', 'excel']);

        $report = $this->reportService->getCustomerReport($dateRange, $request->all(), $isExport);
        $records = $report['records'];
        $summary = $report['summary'];

        if ($export === 'csv') {
            $headers = ['Customer Name', 'Phone', 'Email', 'Type', 'Orders in Period', 'Total Spent in Period ($)', 'Current Balance ($)', 'Status'];
            $rows = $records->map(fn($c) => [
                $c->name,
                $c->phone ?? 'N/A',
                $c->email ?? 'N/A',
                ucfirst($c->customer_type),
                $c->sales_count,
                number_format($c->sales_sum_total_amount ?? 0, 2),
                number_format($c->balance, 2),
                ucfirst($c->status),
            ])->toArray();

            return $this->reportService->exportCsv($headers, $rows, "Customer-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.csv");
        }

        if ($export === 'pdf' || $export === 'excel') {
            $headers = [
                ['label' => 'Customer Name', 'class' => 'text-left'],
                ['label' => 'Phone', 'class' => 'text-left'],
                ['label' => 'Customer Type', 'class' => 'text-center'],
                ['label' => 'Orders (Period)', 'class' => 'text-center'],
                ['label' => 'Period Spent', 'class' => 'text-right fw-bold'],
                ['label' => 'Balance Due', 'class' => 'text-right text-danger'],
                ['label' => 'Status', 'class' => 'text-center'],
            ];

            $rows = $records->map(fn($c) => [
                $c->name,
                $c->phone ?? 'N/A',
                ucfirst($c->customer_type),
                $c->sales_count,
                '$' . number_format($c->sales_sum_total_amount ?? 0, 2),
                '$' . number_format($c->balance, 2),
                ucfirst($c->status),
            ])->toArray();

            $kpis = [
                ['label' => 'Total Customers', 'value' => number_format($summary['total_customers'])],
                ['label' => 'Orders in Period', 'value' => number_format($summary['total_orders'])],
                ['label' => 'Total Customer Revenue', 'value' => '$' . number_format($summary['total_revenue'], 2), 'style' => 'color: #0d6efd;'],
                ['label' => 'Total Receivables (Due)', 'value' => '$' . number_format($summary['total_receivables'], 2), 'style' => 'color: #dc2626;'],
            ];

            $data = [
                'title' => 'Customer Sales & Balances Report',
                'dateRange' => $dateRange,
                'headers' => $headers,
                'rows' => $rows,
                'kpis' => $kpis,
            ];

            if ($export === 'pdf') {
                return $this->reportService->exportPdf('reports.pdf.template', $data, "Customer-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.pdf", 'landscape');
            }

            return $this->reportService->exportExcel('reports.excel.template', $data, "Customer-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.xls");
        }

        return view('reports.customers', compact('records', 'summary', 'dateRange'));
    }

    /**
     * 6. SUPPLIER REPORT
     */
    public function suppliers(Request $request): View|Response|StreamedResponse
    {
        $dateRange = $this->reportService->resolveDateRange($request);
        $export = strtolower($request->get('export', ''));
        $isExport = in_array($export, ['pdf', 'csv', 'excel']);

        $report = $this->reportService->getSupplierReport($dateRange, $request->all(), $isExport);
        $records = $report['records'];
        $summary = $report['summary'];

        if ($export === 'csv') {
            $headers = ['Supplier Name', 'Company', 'Phone', 'Email', 'Purchases (Period)', 'Total Sourced ($)', 'Outstanding Balance ($)', 'Status'];
            $rows = $records->map(fn($s) => [
                $s->name,
                $s->company ?? 'N/A',
                $s->phone ?? 'N/A',
                $s->email ?? 'N/A',
                $s->purchases_count,
                number_format($s->purchases_sum_total_amount ?? 0, 2),
                number_format($s->balance, 2),
                ucfirst($s->status),
            ])->toArray();

            return $this->reportService->exportCsv($headers, $rows, "Supplier-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.csv");
        }

        if ($export === 'pdf' || $export === 'excel') {
            $headers = [
                ['label' => 'Supplier Name', 'class' => 'text-left'],
                ['label' => 'Company', 'class' => 'text-left'],
                ['label' => 'Phone', 'class' => 'text-left'],
                ['label' => 'Purchases (Period)', 'class' => 'text-center'],
                ['label' => 'Sourced Amount', 'class' => 'text-right fw-bold'],
                ['label' => 'Balance Payable', 'class' => 'text-right text-danger'],
                ['label' => 'Status', 'class' => 'text-center'],
            ];

            $rows = $records->map(fn($s) => [
                $s->name,
                $s->company ?? 'N/A',
                $s->phone ?? 'N/A',
                $s->purchases_count,
                '$' . number_format($s->purchases_sum_total_amount ?? 0, 2),
                '$' . number_format($s->balance, 2),
                ucfirst($s->status),
            ])->toArray();

            $kpis = [
                ['label' => 'Total Suppliers', 'value' => number_format($summary['total_suppliers'])],
                ['label' => 'Purchase Orders', 'value' => number_format($summary['total_orders'])],
                ['label' => 'Total Sourced Volume', 'value' => '$' . number_format($summary['total_purchased'], 2), 'style' => 'color: #0d6efd;'],
                ['label' => 'Total Payables Due', 'value' => '$' . number_format($summary['total_payables'], 2), 'style' => 'color: #dc2626;'],
            ];

            $data = [
                'title' => 'Supplier Procurement & Payables Report',
                'dateRange' => $dateRange,
                'headers' => $headers,
                'rows' => $rows,
                'kpis' => $kpis,
            ];

            if ($export === 'pdf') {
                return $this->reportService->exportPdf('reports.pdf.template', $data, "Supplier-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.pdf", 'landscape');
            }

            return $this->reportService->exportExcel('reports.excel.template', $data, "Supplier-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.xls");
        }

        return view('reports.suppliers', compact('records', 'summary', 'dateRange'));
    }

    /**
     * 7. EXPENSE REPORT
     */
    public function expenses(Request $request): View|Response|StreamedResponse
    {
        $dateRange = $this->reportService->resolveDateRange($request);
        $export = strtolower($request->get('export', ''));
        $isExport = in_array($export, ['pdf', 'csv', 'excel']);

        $report = $this->reportService->getExpenseReport($dateRange, $request->all(), $isExport);
        $records = $report['records'];
        $summary = $report['summary'];

        if ($export === 'csv') {
            $headers = ['Ref #', 'Expense Date', 'Expense Name', 'Category', 'Recorded By', 'Amount ($)', 'Notes'];
            $rows = $records->map(fn($e) => [
                $e->reference_no,
                $e->expense_date->format('Y-m-d'),
                $e->title,
                $e->category?->name ?? 'Uncategorized',
                $e->user?->name ?? 'Staff',
                number_format($e->amount, 2),
                $e->notes ?? '',
            ])->toArray();

            return $this->reportService->exportCsv($headers, $rows, "Expense-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.csv");
        }

        if ($export === 'pdf' || $export === 'excel') {
            $headers = [
                ['label' => 'Ref #', 'class' => 'text-left'],
                ['label' => 'Date', 'class' => 'text-center'],
                ['label' => 'Expense Name', 'class' => 'text-left'],
                ['label' => 'Category', 'class' => 'text-left'],
                ['label' => 'Recorded By', 'class' => 'text-left'],
                ['label' => 'Amount', 'class' => 'text-right fw-bold text-danger'],
                ['label' => 'Notes', 'class' => 'text-left'],
            ];

            $rows = $records->map(fn($e) => [
                $e->reference_no,
                $e->expense_date->format('M d, Y'),
                $e->title,
                $e->category?->name ?? 'Uncategorized',
                $e->user?->name ?? 'Staff',
                '$' . number_format($e->amount, 2),
                $e->notes ?? 'N/A',
            ])->toArray();

            $kpis = [
                ['label' => 'Total Expenses', 'value' => '$' . number_format($summary['total_expenses'], 2), 'style' => 'color: #dc2626; font-weight: bold;'],
                ['label' => 'Expense Entries', 'value' => number_format($summary['total_count'])],
                ['label' => 'Average Expense', 'value' => '$' . number_format($summary['avg_expense'], 2)],
            ];

            $data = [
                'title' => 'Operating Expense Audit Report',
                'dateRange' => $dateRange,
                'headers' => $headers,
                'rows' => $rows,
                'kpis' => $kpis,
            ];

            if ($export === 'pdf') {
                return $this->reportService->exportPdf('reports.pdf.template', $data, "Expense-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.pdf", 'landscape');
            }

            return $this->reportService->exportExcel('reports.excel.template', $data, "Expense-Report-{$dateRange['start_date']}-to-{$dateRange['end_date']}.xls");
        }

        return view('reports.expenses', compact('records', 'summary', 'dateRange'));
    }
}
