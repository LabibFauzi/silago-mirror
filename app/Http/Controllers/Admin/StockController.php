<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AuditLogService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SettingService $settingService
    ) {}

    public function index(Request $request): View
    {
        $filter = $request->input('status') ?: 'all';
        $search = $request->input('q');

        $query = Product::where('verification_status', 'verified');
        
        $allCount = (clone $query)->count();
        $availableCount = (clone $query)->where('stock', '>', 0)->count();
        $outCount = (clone $query)->where('stock', 0)->count();

        $products = $query->with(['seller', 'category', 'images'])
            ->when($filter && $filter !== 'all', function ($q) use ($filter) {
                if ($filter === 'available') {
                    $q->where('stock', '>', 0);
                } elseif ($filter === 'out') {
                    $q->where('stock', 0);
                }
            })
            ->when($search, function ($q) use ($search) {
                $searchLower = strtolower($search);
                $q->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereHas('seller', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]))
                      ->orWhereHas('category', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.stock.index', compact('products', 'allCount', 'availableCount', 'outCount', 'filter'));
    }
}
