<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $farmersPerBarangay = Farmer::select('barangay', DB::raw('count(*) as total'))
            ->groupBy('barangay')
            ->orderByDesc('total')
            ->get();

        $cropDistribution = Farmer::select('crop_type', DB::raw('count(*) as total'))
            ->groupBy('crop_type')
            ->orderByDesc('total')
            ->get();

        $distributionsPerProgram = Program::withCount('distributions')->get();

        return view('admin.analytics', [
            'totalFarmers' => Farmer::count(),
            'totalPrograms' => Program::count(),
            'activePrograms' => Program::where('is_active', true)->count(),
            'totalBeneficiaries' => Distribution::distinct('farmer_id')->count('farmer_id'),
            'totalDistributions' => Distribution::count(),
            'farmersPerBarangay' => $farmersPerBarangay,
            'cropDistribution' => $cropDistribution,
            'distributionsPerProgram' => $distributionsPerProgram,
        ]);
    }
}
