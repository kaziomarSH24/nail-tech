<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Exports\TransactionsExport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class IncomeReportController extends Controller
{
    /**
     * Dashboard Statistics & List
     */
    public function index(Request $request)
    {
        // Get Date Ranges
        [$startDate, $endDate, $prevStartDate, $prevEndDate] = $this->getDateParams($request);

        // 2. Build Base Queries
        $query = Transaction::where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Previous query is usually NOT filtered by search (to show baseline growth)
        $previousQuery = Transaction::where('status', 'success')
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate]);

        // 3. Apply Search Filter (Only to Current Query)
        $this->applySearch($query, $request->search);

        // 4. Calculate Stats (Using clone to prevent query mutation)
        $currentTotal   = $query->clone()->sum('amount');
        $prevTotal      = $previousQuery->clone()->sum('amount');

        $currentProduct = $query->clone()->where('payable_type', 'App\Models\Order')->sum('amount');
        $prevProduct    = $previousQuery->clone()->where('payable_type', 'App\Models\Order')->sum('amount');

        $currentCourse  = $query->clone()->whereIn('payable_type', ['App\Models\Course', 'App\Models\Enrollment'])->sum('amount');
        $prevCourse     = $previousQuery->clone()->whereIn('payable_type', ['App\Models\Course', 'App\Models\Enrollment'])->sum('amount');

        $currentCount   = $query->clone()->count();
        $prevCount      = $previousQuery->clone()->count();

        // 5. Fetch & Format List Data
        $transactions = $query->with(['user', 'payable'])
            ->latest()
            ->paginate($request->per_page ?? 10);

        $formattedTransactions = $transactions->getCollection()->map(function ($txn) {
            return $this->formatTransactionForList($txn);
        });

        // 6. Final Response
        return response()->json([
            'stats' => [
                'total_income' => [
                    'value' => $currentTotal,
                    'growth' => $this->calculateGrowth($currentTotal, $prevTotal)
                ],
                'product_income' => [
                    'value' => $currentProduct,
                    'growth' => $this->calculateGrowth($currentProduct, $prevProduct)
                ],
                'course_income' => [
                    'value' => $currentCourse,
                    'growth' => $this->calculateGrowth($currentCourse, $prevCourse)
                ],
                'total_transactions' => [
                    'value' => $currentCount,
                    'growth' => $this->calculateGrowth($currentCount, $prevCount)
                ],
            ],
            'report' => [
                'data' => $formattedTransactions,
                'pagination' => [
                    'total' => $transactions->total(),
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'last_page' => $transactions->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Single Transaction Details
     */
    public function show($id)
    {
        $transaction = Transaction::with(['user', 'payable'])->findOrFail($id);

        // Default Values
        $sourceRef = 'N/A';
        $relatedLink = null;
        $subtotal = 0;
        $discount = 0;

        // Logic based on Type
        if ($transaction->payable_type === 'App\Models\Order') {
            $order = $transaction->payable;
            $sourceRef = '#' . ($order->shopify_order_id ?? $order->id);
            $subtotal = $order->total_amount;
            $discount = $order->discount_amount ?? 0;

            $relatedLink = [
                'type' => 'order',
                'label' => 'Shopify Order',
                'display_text' => $sourceRef,
                'id' => $order->id
            ];
        } elseif (in_array($transaction->payable_type, ['App\Models\Course', 'App\Models\Enrollment'])) {
            $enrollment = $transaction->payable;

            $courseTitle = ($transaction->payable_type === 'App\Models\Enrollment')
                ? ($enrollment->course->title ?? 'Course Enrollment')
                : ($enrollment->title ?? 'Course Purchase');

            $sourceRef = $courseTitle;
            $subtotal = $enrollment->course_price ?? $enrollment->price ?? $transaction->amount;
            $discount = $enrollment->discount_amount ?? 0;

            $relatedLink = [
                'type' => 'Enrollment',
                'label' => 'Course',
                'display_text' => 'View Course',
                'id' => $enrollment->course_id ?? $enrollment->id
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->gateway_transaction_id ?? ('TRX-' . $transaction->id),
                'date' => $transaction->created_at->format('d M, h:i A'),
                'status' => ucfirst($transaction->status),
                'source_reference' => $sourceRef,
                'user' => [
                    'name' => $transaction->user ? $transaction->user->name : 'Guest',
                    'email' => $transaction->user->email ?? 'N/A',
                    'avatar' => $transaction->user->avatar ?? null,
                ],
                'breakdown' => [
                    ['label' => 'Subtotal', 'value' => number_format($subtotal, 2)],
                    ['label' => 'Discount', 'value' => '-' . number_format($discount, 2)]
                ],
                'total_amount' => number_format($transaction->amount, 2),
                'currency' => strtoupper($transaction->currency ?? 'USD'),
                'related_link' => $relatedLink
            ]
        ]);
    }

    /**
     * Generate PDF/Excel Report
     */
    public function generateReport(Request $request)
    {
        $request->validate(['format' => 'required|in:pdf,csv,excel']);

        // Get Date Ranges (Current Only)
        [$startDate, $endDate] = $this->getDateParams($request);

        // 2. Build Query
        $query = Transaction::where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Apply Search
        $this->applySearch($query, $request->search);

        //  Fetch Data
        $transactions = $query->with(['user', 'payable'])->latest()->get();

        //  Export
        if ($request->format === 'pdf') {
            $pdf = Pdf::loadView('reports.income_pdf', ['transactions' => $transactions]);
            return $pdf->download('income_report_' . date('Y-m-d') . '.pdf');
        }
        $extension = $request->format === 'excel' ? 'xlsx' : 'csv';
        $fileName = 'income_report.' . $extension;

        return Excel::download(new TransactionsExport($transactions), $fileName);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Calculates Date Ranges for Current and Previous periods
     * Returns: [currentStart, currentEnd, prevStart, prevEnd]
     */
    private function getDateParams(Request $request): array
    {
        if ($request->has(['start_date', 'end_date'])) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end   = Carbon::parse($request->end_date)->endOfDay();
            $diff  = $start->diffInDays($end) + 1;

            return [$start, $end, $start->copy()->subDays($diff), $start->copy()->subSecond()];
        }

        $period = $request->period ?? 'this_month';
        $now = Carbon::now();

        return match ($period) {
            'last_7_days' => [
                $now->copy()->subDays(7),
                $now,
                $now->copy()->subDays(14),
                $now->copy()->subDays(7)
            ],
            'last_30_days' => [
                $now->copy()->subDays(30),
                $now,
                $now->copy()->subDays(60),
                $now->copy()->subDays(30)
            ],
            'last_month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
                $now->copy()->subMonths(2)->startOfMonth(),
                $now->copy()->subMonths(2)->endOfMonth()
            ],
            default => [ // 'this_month'
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth()
            ],
        };
    }

    /**
     * Applies Search Logic to Query
     */
    private function applySearch($query, $searchTerm)
    {
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('gateway_transaction_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('id', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('user', function ($uq) use ($searchTerm) {
                        $uq->where('name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }
    }

    /**
     * Formats a single transaction for the list
     */
    private function formatTransactionForList($txn)
    {
        $source = 'Unknown';
        $itemName = 'N/A';

        if ($txn->payable_type === 'App\Models\Order') {
            $source = 'Shopify';
            $itemName = 'Order #' . $txn->payable_id;
        } elseif (in_array($txn->payable_type, ['App\Models\Course', 'App\Models\Enrollment'])) {
            $source = 'Course';
            if ($txn->payable) {
                $itemName = ($txn->payable_type === 'App\Models\Enrollment')
                    ? ($txn->payable->course->title ?? 'Course Enrollment')
                    : ($txn->payable->title ?? 'Course Purchase');
            }
        }

        return [
            'id' => $txn->id,
            'date' => $txn->created_at->format('M d, Y'),
            'source' => $source,
            'item_name' => $itemName,
            'user_name' => $txn->user ? $txn->user->name : 'Guest',
            'amount' => number_format($txn->amount, 2),
            'status' => ucfirst($txn->status),
            'action_id' => $txn->id,
        ];
    }

    /**
     * Calculates Percentage Growth
     */
    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
