<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ReportsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Response;
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

    public function exportPdf(): Response
    {
        $pdf = Pdf::loadView('admin.reports-export', [
            'farmers' => Farmer::latest()->take(20)->get(),
            'programs' => Program::latest()->take(20)->get(),
            'distributions' => Distribution::with(['farmer', 'program'])
                ->latest()
                ->take(20)
                ->get(),
        ]);

        return $pdf->download(
            'fams-report-' . now()->format('YmdHis') . '.pdf'
        );
    }

    public function exportExcel()
    {
        return Excel::download(
            new ReportsExport(),
            'fams-report-' . now()->format('YmdHis') . '.xlsx'
        );
    }
}