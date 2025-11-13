<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Pet;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Revenue report.
     */
    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'group_by' => 'nullable|in:day,week,month,year',
        ]);

        $query = Invoice::query();

        if ($request->from_date) {
            $query->whereDate('invoice_date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('invoice_date', '<=', $request->to_date);
        }

        $totalRevenue = $query->clone()->where('status', 'paid')->sum('total_amount');
        $pendingRevenue = $query->clone()->where('status', 'pending')->sum('total_amount');
        $totalInvoices = $query->clone()->count();
        $paidInvoices = $query->clone()->where('status', 'paid')->count();

        $report = [
            'total_revenue' => (float) $totalRevenue,
            'pending_revenue' => (float) $pendingRevenue,
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'average_invoice_value' => $paidInvoices > 0 ? round($totalRevenue / $paidInvoices, 2) : 0,
        ];

        // Group by period if requested
        if ($request->group_by) {
            $groupBy = match($request->group_by) {
                'day' => 'DATE(invoice_date)',
                'week' => 'YEARWEEK(invoice_date)',
                'month' => 'DATE_FORMAT(invoice_date, "%Y-%m")',
                'year' => 'YEAR(invoice_date)',
            };

            $periodData = $query->clone()
                ->where('status', 'paid')
                ->select(DB::raw("$groupBy as period, SUM(total_amount) as revenue, COUNT(*) as count"))
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $report['by_period'] = $periodData;
        }

        return response()->json($report);
    }

    /**
     * Appointment statistics.
     */
    public function appointments(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = Appointment::query();

        if ($request->from_date) {
            $query->whereDate('appointment_date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('appointment_date', '<=', $request->to_date);
        }

        $report = [
            'total_appointments' => $query->clone()->count(),
            'by_status' => $query->clone()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_type' => $query->clone()
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_veterinarian' => $query->clone()
                ->with('veterinarian:id,name')
                ->select('veterinarian_id', DB::raw('count(*) as count'))
                ->groupBy('veterinarian_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'veterinarian' => $item->veterinarian->name ?? 'Unknown',
                        'count' => $item->count,
                    ];
                }),
            'completion_rate' => $this->calculateCompletionRate($query->clone()),
            'no_show_rate' => $this->calculateNoShowRate($query->clone()),
        ];

        return response()->json($report);
    }

    /**
     * Popular services report.
     */
    public function services(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = Appointment::query();

        if ($request->from_date) {
            $query->whereDate('appointment_date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('appointment_date', '<=', $request->to_date);
        }

        $services = $query->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->limit($request->limit ?? 10)
            ->get();

        return response()->json([
            'popular_services' => $services,
        ]);
    }

    /**
     * Product sales report.
     */
    public function productSales(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        // Get top selling products from invoice items
        $topProducts = DB::table('invoice_items')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(invoice_items.subtotal) as total_revenue')
            )
            ->when($request->from_date, function ($query, $fromDate) {
                return $query->whereDate('invoices.invoice_date', '>=', $fromDate);
            })
            ->when($request->to_date, function ($query, $toDate) {
                return $query->whereDate('invoices.invoice_date', '<=', $toDate);
            })
            ->where('invoices.status', 'paid')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_quantity')
            ->limit($request->limit ?? 10)
            ->get();

        return response()->json([
            'top_selling_products' => $topProducts,
        ]);
    }

    /**
     * Client retention metrics.
     */
    public function clientRetention(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $totalOwners = User::role('owner')->count();

        $activeOwnersQuery = User::role('owner')
            ->whereHas('appointments', function ($q) use ($request) {
                if ($request->from_date) {
                    $q->whereDate('appointment_date', '>=', $request->from_date);
                }
                if ($request->to_date) {
                    $q->whereDate('appointment_date', '<=', $request->to_date);
                }
            });

        $activeOwners = $activeOwnersQuery->count();

        $returningOwners = User::role('owner')
            ->whereHas('appointments', function ($q) use ($request) {
                $q->select('user_id')
                    ->when($request->from_date, function ($query, $fromDate) {
                        return $query->whereDate('appointment_date', '>=', $fromDate);
                    })
                    ->when($request->to_date, function ($query, $toDate) {
                        return $query->whereDate('appointment_date', '<=', $toDate);
                    })
                    ->groupBy('user_id')
                    ->havingRaw('COUNT(*) > 1');
            })
            ->count();

        return response()->json([
            'total_clients' => $totalOwners,
            'active_clients' => $activeOwners,
            'returning_clients' => $returningOwners,
            'retention_rate' => $totalOwners > 0 ? round(($activeOwners / $totalOwners) * 100, 2) : 0,
            'return_rate' => $activeOwners > 0 ? round(($returningOwners / $activeOwners) * 100, 2) : 0,
        ]);
    }

    /**
     * Pets report.
     */
    public function pets(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = Pet::query();

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $totalPets = $query->clone()->count();
        
        $bySpecies = $query->clone()
            ->select('species', DB::raw('count(*) as count'))
            ->groupBy('species')
            ->pluck('count', 'species');

        return response()->json([
            'total_pets' => $totalPets,
            'by_species' => $bySpecies,
        ]);
    }

    /**
     * Calculate appointment completion rate.
     */
    private function calculateCompletionRate($query): float
    {
        $total = $query->clone()->whereIn('status', ['completed', 'cancelled', 'no_show'])->count();
        $completed = $query->clone()->where('status', 'completed')->count();

        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    /**
     * Calculate no-show rate.
     */
    private function calculateNoShowRate($query): float
    {
        $total = $query->clone()->whereIn('status', ['completed', 'cancelled', 'no_show'])->count();
        $noShows = $query->clone()->where('status', 'no_show')->count();

        return $total > 0 ? round(($noShows / $total) * 100, 2) : 0;
    }
}
