<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Pet;
use App\Models\Product;
use App\Models\Vaccination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get comprehensive dashboard statistics
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Different stats based on role
        if ($user->isOwner()) {
            return $this->ownerDashboard($user);
        }
        
        return $this->staffDashboard($user);
    }

    /**
     * Owner dashboard statistics
     */
    private function ownerDashboard($user): JsonResponse
    {
        $stats = [
            'pets' => [
                'total' => Pet::where('user_id', $user->id)->count(),
                'active' => Pet::where('user_id', $user->id)->count()
            ],
            'appointments' => [
                'upcoming' => Appointment::where('user_id', $user->id)
                    ->where('appointment_date', '>=', now())
                    ->where('status', 'scheduled')
                    ->count(),
                'today' => Appointment::where('user_id', $user->id)
                    ->whereDate('appointment_date', today())
                    ->count()
            ],
            'vaccinations' => [
                'due_soon' => Vaccination::whereHas('pet', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->whereBetween('next_due_date', [now(), now()->addDays(30)])
                ->count(),
                'overdue' => Vaccination::whereHas('pet', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->where('next_due_date', '<', now())
                ->count()
            ],
            'invoices' => [
                'unpaid' => Invoice::where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                    ->count(),
                'unpaid_amount' => Invoice::where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                    ->sum('total_amount')
            ]
        ];

        return response()->json([
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => $stats
        ]);
    }

    /**
     * Staff dashboard statistics
     */
    private function staffDashboard($user): JsonResponse
    {
        $stats = [
            'today' => [
                'appointments' => Appointment::whereDate('appointment_date', today())->count(),
                'completed_appointments' => Appointment::whereDate('appointment_date', today())
                    ->where('status', 'completed')
                    ->count(),
                'revenue' => Invoice::whereDate('created_at', today())
                    ->sum('total_amount'),
                'payments_received' => Invoice::whereDate('updated_at', today())
                    ->where('status', 'paid')
                    ->sum('paid_amount')
            ],
            'appointments' => [
                'scheduled' => Appointment::where('status', 'scheduled')
                    ->where('appointment_date', '>=', now())
                    ->count(),
                'pending' => Appointment::where('status', 'pending')->count(),
                'today' => Appointment::whereDate('appointment_date', today())->count(),
                'this_week' => Appointment::whereBetween('appointment_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count()
            ],
            'pets' => [
                'total' => Pet::count(),
                'new_this_month' => Pet::whereMonth('created_at', now()->month)->count()
            ],
            'inventory' => [
                'low_stock_count' => Product::lowStock()->count(),
                'out_of_stock_count' => Product::where('quantity_in_stock', 0)
                    ->where('track_stock', true)
                    ->count(),
                'total_value' => Product::sum(DB::raw('quantity_in_stock * cost'))
            ],
            'vaccinations' => [
                'due_this_week' => Vaccination::whereBetween('next_due_date', [
                    now(),
                    now()->addDays(7)
                ])->count(),
                'due_this_month' => Vaccination::whereBetween('next_due_date', [
                    now(),
                    now()->addDays(30)
                ])->count(),
                'overdue' => Vaccination::where('next_due_date', '<', now())->count()
            ],
            'revenue' => [
                'today' => Invoice::whereDate('created_at', today())->sum('total_amount'),
                'this_week' => Invoice::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->sum('total_amount'),
                'this_month' => Invoice::whereMonth('created_at', now()->month)
                    ->sum('total_amount'),
                'unpaid_total' => Invoice::whereIn('status', ['pending', 'partially_paid', 'overdue'])
                    ->sum('total_amount')
            ]
        ];

        // Add vet-specific stats if user is a vet
        if ($user->hasRole('veterinarian')) {
            $stats['my_appointments'] = [
                'today' => Appointment::where('veterinarian_id', $user->id)
                    ->whereDate('appointment_date', today())
                    ->count(),
                'upcoming' => Appointment::where('veterinarian_id', $user->id)
                    ->where('appointment_date', '>', now())
                    ->where('status', 'scheduled')
                    ->count()
            ];
        }

        return response()->json([
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => $stats
        ]);
    }

    /**
     * Get today's appointments
     */
    public function todaysAppointments(): JsonResponse
    {
        $user = auth()->user();
        
        $query = Appointment::with(['pet', 'owner', 'veterinarian'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_date');

        // Filter by role
        if ($user->isOwner()) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('veterinarian')) {
            $query->where('veterinarian_id', $user->id);
        }

        $appointments = $query->get();

        return response()->json([
            'message' => 'Today\'s appointments retrieved successfully',
            'data' => $appointments,
            'count' => $appointments->count()
        ]);
    }

    /**
     * Get upcoming vaccinations
     */
    public function upcomingVaccinations(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        
        $vaccinations = Vaccination::with(['pet.owner', 'administeredBy'])
            ->whereBetween('next_due_date', [now(), now()->addDays($days)])
            ->orderBy('next_due_date')
            ->get();

        return response()->json([
            'message' => 'Upcoming vaccinations retrieved successfully',
            'data' => $vaccinations,
            'count' => $vaccinations->count()
        ]);
    }

    /**
     * Get overdue vaccinations
     */
    public function overdueVaccinations(): JsonResponse
    {
        $vaccinations = Vaccination::with(['pet.owner', 'administeredBy'])
            ->where('next_due_date', '<', now())
            ->orderBy('next_due_date')
            ->get();

        return response()->json([
            'message' => 'Overdue vaccinations retrieved successfully',
            'data' => $vaccinations,
            'count' => $vaccinations->count()
        ]);
    }

    /**
     * Get revenue summary
     */
    public function revenueSummary(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month'); // day, week, month, year

        $startDate = match($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()
        };

        $revenue = [
            'total' => Invoice::where('created_at', '>=', $startDate)
                ->sum('total_amount'),
            'paid' => Invoice::where('created_at', '>=', $startDate)
                ->where('status', 'paid')
                ->sum('total_amount'),
            'pending' => Invoice::where('created_at', '>=', $startDate)
                ->whereIn('status', ['pending', 'partially_paid'])
                ->sum('total_amount'),
            'invoices_count' => Invoice::where('created_at', '>=', $startDate)->count(),
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => now()->toDateString()
        ];

        return response()->json([
            'message' => 'Revenue summary retrieved successfully',
            'data' => $revenue
        ]);
    }
}
