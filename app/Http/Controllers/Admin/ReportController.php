<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports', [
            'totalFarmers' => Farmer::count(),
            'totalPrograms' => Program::count(),
            'totalDistributions' => Distribution::count(),
        ]);
    }

    public function exportPdf()
    {
        $html = view('admin.reports-export', [
            'farmers' => Farmer::latest()->take(20)->get(),
            'programs' => Program::latest()->take(20)->get(),
            'distributions' => Distribution::with(['farmer', 'program'])->latest()->take(20)->get(),
        ])->render();

        return response($html, 200)->header('Content-Type', 'application/pdf');
    }

    public function exportExcel()
    {
        $filename = 'fams-report-' . now()->format('YmdHis') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Farmers', 'Programs', 'Distributions']);
            fputcsv($handle, [Farmer::count(), Program::count(), Distribution::count()]);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
