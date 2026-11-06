<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceDeal;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::where('role', 'user')->count();
        $activeProducts = Product::where('status', 'available')->where('verification_status', 'verified')->count();
        $todayDeals = MarketplaceDeal::whereDate('created_at', today())->count();
        $pendingReports = Report::where('status', 'pending')->count();
        
        $recentReports = Report::with(['reporter', 'product', 'reportedUser', 'marketplaceDeal'])
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();
            
        // Generate 7-day chart data (always show 7 days even if no transactions)
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $dates->push(now()->subDays($i)->startOfDay());
        }
        
        $recentTransactions = MarketplaceDeal::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');
            
        $chartData = [
            'labels' => $dates->map(fn($date) => $date->format('d M'))->toArray(),
            'data' => $dates->map(fn($date) => $recentTransactions->get($date->format('Y-m-d'))?->total ?? 0)->toArray(),
        ];
        
        return view('admin.dashboard.index', compact('totalUsers', 'activeProducts', 'todayDeals', 'pendingReports', 'recentReports', 'chartData'));
    }
}
