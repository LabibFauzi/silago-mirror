<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReportRequest;
use App\Models\MarketplaceDeal;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function reportProduct(StoreReportRequest $request, Product $product)
    {
        $userId = $request->user()->id;

        if ($product->seller_id === $userId) {
            return response()->json(['message' => 'Tidak dapat melaporkan produk sendiri.'], 403);
        }

        $report = $product->reports()->create([
            'reporter_id' => $userId,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Laporan berhasil dibuat.', 'data' => $report], 201);
    }

    public function reportUser(StoreReportRequest $request, User $user)
    {
        $userId = $request->user()->id;

        if ($user->id === $userId) {
            return response()->json(['message' => 'Tidak dapat melaporkan diri sendiri.'], 403);
        }

        $report = $user->reports()->create([
            'reporter_id' => $userId,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Laporan berhasil dibuat.', 'data' => $report], 201);
    }

    public function reportDeal(StoreReportRequest $request, MarketplaceDeal $deal)
    {
        $userId = $request->user()->id;

        if ($deal->buyer_id !== $userId && $deal->seller_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $report = $deal->reports()->create([
            'reporter_id' => $userId,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Laporan berhasil dibuat.', 'data' => $report], 201);
    }
}
