<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use App\Services\ReportData;
use App\Services\ReportingPeriod;
use Illuminate\Http\Request;
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

    public function exportPdf(Request $request): Response
    {
        $report = new ReportData($request->hasAny(['academic_year', 'month']) ? new ReportingPeriod($request) : null);
        $pdf = Pdf::loadView('admin.reports-export', ['report' => $report])
            ->setPaper('a4', 'landscape');

        return $pdf->download($report->filename().'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $report = new ReportData($request->hasAny(['academic_year', 'month']) ? new ReportingPeriod($request) : null);

        return Excel::download(
            new ReportsExport($report),
            $report->filename().'.xlsx'
        );
    }
}
