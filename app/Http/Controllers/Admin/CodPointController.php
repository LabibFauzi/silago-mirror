<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CodPoint;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CodPointController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function index(): View
    {
        $codPoints = CodPoint::latest()->paginate(15);
        return view('admin.cod_points.index', compact('codPoints'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'is_active' => ['boolean'],
        ]);

        try {
            DB::transaction(function () use ($request) {
                $codPoint = new CodPoint();
                $codPoint->name = $request->input('name');
                $codPoint->address = $request->input('address');
                $codPoint->lat = $request->input('lat');
                $codPoint->lng = $request->input('lng');
                $codPoint->is_active = $request->input('is_active', true);
                $codPoint->save();

                $this->auditLogService->logSystem('Tambah titik COD', $codPoint->name);
            });

            return back()->with('success', 'Titik COD berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambahkan titik COD: ' . $e->getMessage());
        }
    }

    public function update(Request $request, CodPoint $codPoint): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'is_active' => ['boolean'],
        ]);

        try {
            DB::transaction(function () use ($request, $codPoint) {
                $codPoint->name = $request->input('name');
                $codPoint->address = $request->input('address');
                $codPoint->lat = $request->input('lat');
                $codPoint->lng = $request->input('lng');
                $codPoint->is_active = $request->input('is_active', true);
                $codPoint->save();

                $this->auditLogService->logSystem('Ubah titik COD', $codPoint->name);
            });

            return back()->with('success', 'Titik COD berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui titik COD: ' . $e->getMessage());
        }
    }
}
