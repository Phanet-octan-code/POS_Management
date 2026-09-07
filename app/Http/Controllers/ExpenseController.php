<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\ActivityLoggerService;
use App\Services\NotificationService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Display a listing of expenses with search, date, and category filters.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim($request->get('search', '')),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'category_id' => $request->get('category_id'),
            'user_id' => $request->get('user_id'),
        ];

        $query = Expense::with(['category', 'user'])->filter($filters);

        $expenses = $query->latest('expense_date')->latest('id')->paginate(15)->withQueryString();

        $categories = ExpenseCategory::orderBy('name')->get();
        $users = User::select('id', 'name')->orderBy('name')->get();

        // Financial Summaries & Net Profit Impact
        $filteredTotal = (float) (clone $query)->sum('amount');
        $allTimeTotal = (float) Expense::sum('amount');

        // Net Profit Calculation for the selected date range (or current month if no dates set)
        $dateStart = $filters['start_date'] ?: now()->startOfMonth()->toDateString();
        $dateEnd = $filters['end_date'] ?: now()->endOfMonth()->toDateString();
        $profitMetrics = $this->reportService->getDashboardMetrics($dateStart, $dateEnd);

        return view('expenses.index', compact(
            'expenses',
            'categories',
            'users',
            'filters',
            'filteredTotal',
            'allTimeTotal',
            'profitMetrics',
            'dateStart',
            'dateEnd'
        ));
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(ExpenseRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->input('user_id') ?: (Auth::id() ?? 1);

        // Generate unique reference number EXP-YYYY-XXXXXX
        $year = date('Y');
        $latest = Expense::withTrashed()
            ->where('reference_no', 'like', "EXP-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $nextNum = 1;
        if ($latest && preg_match('/^EXP-\d{4}-(\d+)$/', $latest->reference_no, $m)) {
            $nextNum = ((int) $m[1]) + 1;
        }

        do {
            $candidate = sprintf('EXP-%s-%06d', $year, $nextNum);
            $exists = Expense::withTrashed()->where('reference_no', $candidate)->exists();
            if ($exists) {
                $nextNum++;
            }
        } while ($exists);

        $data['reference_no'] = $candidate;

        $expense = Expense::create($data);
        $expense->load(['category', 'user']);

        app(NotificationService::class)->notifyExpense($expense);

        ActivityLoggerService::log(
            action: 'expense.create',
            description: "recorded expense '{$expense->title}'",
            subject: $expense,
            properties: ['amount' => $expense->amount, 'category' => $expense->category?->name],
            module: 'expenses'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Expense recorded successfully.',
                'expense' => $expense,
            ]);
        }

        return redirect()->route('expenses.index')
            ->with('success', "Expense '{$expense->title}' recorded successfully.");
    }

    /**
     * Display the specified expense details (JSON for modal inspection or direct view).
     */
    public function show(Expense $expense): JsonResponse|View
    {
        $expense->load(['category', 'user']);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'expense' => [
                    'id' => $expense->id,
                    'reference_no' => $expense->reference_no,
                    'name' => $expense->title,
                    'title' => $expense->title,
                    'category_id' => $expense->expense_category_id,
                    'category_name' => $expense->category?->name ?? 'Uncategorized',
                    'category_badge' => $expense->category_color,
                    'amount' => (float) $expense->amount,
                    'amount_formatted' => '$' . number_format($expense->amount, 2),
                    'expense_date' => $expense->expense_date->format('Y-m-d'),
                    'expense_date_formatted' => $expense->expense_date->format('M d, Y'),
                    'description' => $expense->notes ?? '',
                    'notes' => $expense->notes ?? '',
                    'user_id' => $expense->user_id,
                    'user_name' => $expense->user?->name ?? 'Staff',
                    'created_at' => $expense->created_at?->format('M d, Y H:i:s'),
                ],
            ]);
        }

        return view('expenses.show', compact('expense'));
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        if ($request->filled('user_id')) {
            $data['user_id'] = $request->input('user_id');
        }

        $expense->update($data);
        $expense->load(['category', 'user']);

        ActivityLoggerService::log(
            action: 'expense.update',
            description: "updated expense '{$expense->title}'",
            subject: $expense,
            properties: ['amount' => $expense->amount],
            module: 'expenses'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully.',
                'expense' => $expense,
            ]);
        }

        return redirect()->route('expenses.index')
            ->with('success', "Expense '{$expense->title}' updated successfully.");
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Request $request, Expense $expense): RedirectResponse|JsonResponse
    {
        $title = $expense->title;
        $ref = $expense->reference_no;

        $expense->delete();

        ActivityLoggerService::log(
            action: 'expense.delete',
            description: "deleted expense '{$title}'",
            module: 'expenses'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Expense '{$title}' deleted successfully.",
            ]);
        }

        return redirect()->route('expenses.index')
            ->with('success', "Expense '{$title}' deleted successfully.");
    }
}
