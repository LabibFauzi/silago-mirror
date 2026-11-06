<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CodPoint;

class CodPointController extends Controller
{
    public function index()
    {
        $points = CodPoint::where('is_active', true)->get();
        return response()->json($points);
    }
}
