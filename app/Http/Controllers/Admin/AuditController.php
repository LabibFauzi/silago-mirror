<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog as AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->input('category');
        $search = $request->input('q');

        $auditLogs = AuditLog::with('admin')
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($search, function ($q) use ($search) {
                $searchLower = strtolower($search);
                $q->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(action) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereHas('admin', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.audit.index', compact('auditLogs', 'category'));
    }
}
