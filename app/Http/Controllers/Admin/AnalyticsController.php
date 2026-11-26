<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use App\Models\Recommendation;
use App\Services\ReportingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $period = new ReportingPeriod($request);
        $selectedAcademicYear = $period->academicYear;
        $periodStart = $period->start;
        $periodEnd = $period->end;
        $currentYear = now()->month >= 6 ? now()->year : now()->year - 1;

        $academicYears = [];

        for ($year = $currentYear - 4; $year <= $currentYear + 1; $year++) {
            $academicYears[] = $year . '-' . ($year + 1);
        }

        /*
        |--------------------------------------------------------------------------
        | 01 - FARMER PROFILE OVERVIEW
        |--------------------------------------------------------------------------
        */

        $farmerQuery = Farmer::query()
            ->whereBetween('created_at', [$periodStart, $periodEnd]);

        $totalFarmers = (clone $farmerQuery)->count();

        $associationMembers = (clone $farmerQuery)
            ->where('association_membership', true)
            ->count();

        $nonAssociationMembers = (clone $farmerQuery)
            ->where('association_membership', false)
            ->count();

        $fourPsMembers = (clone $farmerQuery)
            ->where('four_ps_membership', true)
            ->count();

        $nonFourPsMembers = (clone $farmerQuery)
            ->where('four_ps_membership', false)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Farmers Per Barangay
        |--------------------------------------------------------------------------
        */

        $farmersPerBarangay = (clone $farmerQuery)
            ->select(
                'barangay',
                DB::raw('count(*) as total')
            )
            ->whereNotNull('barangay')
            ->groupBy('barangay')
            ->orderByDesc('total')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Farmers Per Crop + Percentage
        |--------------------------------------------------------------------------
        */

        $farmersPerCrop = (clone $farmerQuery)
            ->select(
                'crop_type',
                DB::raw('count(*) as total')
            )
            ->whereNotNull('crop_type')
            ->groupBy('crop_type')
            ->orderByDesc('total')
            ->get();

        $farmersPerCrop->transform(function ($item) use ($totalFarmers) {
            $item->percentage = $totalFarmers > 0
                ? round(($item->total / $totalFarmers) * 100, 2)
                : 0;

            return $item;
        });

        /*
        |--------------------------------------------------------------------------
        | Membership Overview
        |--------------------------------------------------------------------------
        */

        $membershipOverview = collect([
            [
                'label' => 'Association Members',
                'total' => $associationMembers,
            ],
            [
                'label' => 'Non-Association Members',
                'total' => $nonAssociationMembers,
            ],
            [
                'label' => '4Ps Members',
                'total' => $fourPsMembers,
            ],
            [
                'label' => 'Non-4Ps Members',
                'total' => $nonFourPsMembers,
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | 02 - PROGRAM & ELIGIBILITY ANALYTICS
        |--------------------------------------------------------------------------
        */

        $programQuery = Program::query()->whereNull('archived_at');
        $totalPrograms = (clone $programQuery)->count();

        $excludeArchivedBenefits = fn (Builder $query) => $query
            ->whereDoesntHave('benefit', fn (Builder $benefit) => $benefit->whereNotNull('archived_at'));

        $periodRecords = fn (Builder $query) => $excludeArchivedBenefits($query)
            ->whereBetween('created_at', [$periodStart, $periodEnd]);

        $now = now();

        /*
        |--------------------------------------------------------------------------
        | Automatic Program Status
        |--------------------------------------------------------------------------
        */

        $activePrograms = (clone $programQuery)
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->count();

        $upcomingPrograms = (clone $programQuery)
            ->whereNotNull('start_at')
            ->where('start_at', '>', $now)
            ->count();

        $inactivePrograms = (clone $programQuery)
            ->where(function ($query) use ($now) {
                $query
                    ->where(function ($query) use ($now) {
                        $query
                            ->whereNotNull('end_at')
                            ->where('end_at', '<', $now);
                    })
                    ->orWhereNull('start_at')
                    ->orWhereNull('end_at');
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Program Enrollment
        |--------------------------------------------------------------------------
        */

        $programEnrollments = (clone $programQuery)
            ->withCount(['applications' => $periodRecords])
            ->orderByDesc('applications_count')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Eligibility Statistics
        |--------------------------------------------------------------------------
        |
        | Recommendation table uses eligibility_status.
        |
        */

        $periodFarmerIds = (clone $farmerQuery)
            ->pluck('id');

        $recommendationQuery = $excludeArchivedBenefits(Recommendation::query())
            ->whereHas('program', fn (Builder $program) => $program->whereNull('archived_at'))
            ->whereIn('farmer_id', $periodFarmerIds);

        $eligibleFarmers = (clone $recommendationQuery)
            ->where('eligibility_status', 'eligible')
            ->distinct('farmer_id')
            ->count('farmer_id');

        $partiallyEligibleFarmers = (clone $recommendationQuery)
            ->where('eligibility_status', 'partially_eligible')
            ->distinct('farmer_id')
            ->count('farmer_id');

        $notEligibleFarmers = (clone $recommendationQuery)
            ->where('eligibility_status', 'not_eligible')
            ->distinct('farmer_id')
            ->count('farmer_id');

        $eligibilityTotal =
            $eligibleFarmers +
            $partiallyEligibleFarmers +
            $notEligibleFarmers;

        $eligibilityStatistics = collect([
            [
                'label' => 'Eligible',
                'status' => 'eligible',
                'total' => $eligibleFarmers,
            ],
            [
                'label' => 'Partially Eligible',
                'status' => 'partially_eligible',
                'total' => $partiallyEligibleFarmers,
            ],
            [
                'label' => 'Not Eligible',
                'status' => 'not_eligible',
                'total' => $notEligibleFarmers,
            ],
        ]);

        $eligibilityStatistics->transform(
            function ($item) use ($eligibilityTotal) {
                $item['percentage'] = $eligibilityTotal > 0
                    ? round(($item['total'] / $eligibilityTotal) * 100, 2)
                    : 0;

                return $item;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Program Recommendation Overview
        |--------------------------------------------------------------------------
        |
        | Shows Eligible / Partially Eligible / Not Eligible per program.
        |
        */

        $programRecommendationOverview = (clone $programQuery)
            ->get()
            ->map(function ($program) use ($recommendationQuery) {

                $recommendations = (clone $recommendationQuery)
                    ->where('program_id', $program->id)
                    ->get();

                return (object) [
                    'program' => $program,
                    'eligible' => $recommendations
                        ->where('eligibility_status', 'eligible')
                        ->count(),

                    'partially_eligible' => $recommendations
                        ->where('eligibility_status', 'partially_eligible')
                        ->count(),

                    'not_eligible' => $recommendations
                        ->where('eligibility_status', 'not_eligible')
                        ->count(),
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | Smart Eligibility Reminder
        |--------------------------------------------------------------------------
        |
        | Farmers who are partially eligible and missing only one requirement.
        |
        */

        $smartEligibilityReminders = (clone $recommendationQuery)
            ->with([
                'farmer',
                'program',
            ])
            ->where('eligibility_status', 'partially_eligible')
            ->latest()
            ->get()
            ->filter(function ($recommendation) {
                $missing = $recommendation->missing_requirements;

                return is_array($missing) && count($missing) === 1;
            })
            ->take(10)
            ->values();

        $smartEligibilityReminderCount = (clone $recommendationQuery)
            ->where('eligibility_status', 'partially_eligible')
            ->get()
            ->filter(function ($recommendation) {
                $missing = $recommendation->missing_requirements;

                return is_array($missing) && count($missing) === 1;
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 03 - BENEFIT DISTRIBUTION MONITORING
        |--------------------------------------------------------------------------
        */

        $distributionQuery = $periodRecords(Distribution::query())
            ->whereHas('program', fn (Builder $program) => $program->whereNull('archived_at'));

        $totalDistributions = (clone $distributionQuery)->count();

        $completedDistributions = (clone $distributionQuery)
            ->whereNotNull('distributed_at')
            ->count();

        $pendingDistributions = (clone $distributionQuery)
            ->whereNull('distributed_at')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Distribution Completion Rate
        |--------------------------------------------------------------------------
        */

        $distributionCompletionRate = $totalDistributions > 0
            ? round(
                ($completedDistributions / $totalDistributions) * 100,
                2
            )
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Distributions Per Program
        |--------------------------------------------------------------------------
        */

        $distributionsPerProgram = (clone $programQuery)
            ->withCount(['distributions' => $periodRecords])
            ->orderByDesc('distributions_count')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent Distribution Logs
        |--------------------------------------------------------------------------
        */

        $distributionLogs = (clone $distributionQuery)
            ->with([
                'farmer',
                'program',
            ])
            ->latest()
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Program Performance Summary
        |--------------------------------------------------------------------------
        */

        $programPerformanceSummary = (clone $programQuery)
            ->withCount([
                'applications' => $periodRecords,
                'distributions' => $periodRecords,
            ])
            ->get()
            ->map(function ($program) use ($recommendationQuery) {

                $eligible = (clone $recommendationQuery)
                    ->where('program_id', $program->id)
                    ->where('eligibility_status', 'eligible')
                    ->distinct()
                    ->count('farmer_id');

                return (object) [
                    'program' => $program,
                    'applications_count' => $program->applications_count,
                    'eligible_count' => $eligible,
                    'distributions_count' => $program->distributions_count,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | 05 - DESCRIPTIVE ANALYTICS & TRENDS
        |--------------------------------------------------------------------------
        */

        $farmerRegistrationTrend = (clone $farmerQuery)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $distributionTrend = (clone $distributionQuery)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $cropDistribution = (clone $farmerQuery)
            ->select(
                'crop_type',
                DB::raw('count(*) as total')
            )
            ->whereNotNull('crop_type')
            ->groupBy('crop_type')
            ->orderByDesc('total')
            ->get();

        $barangayDistribution = (clone $farmerQuery)
            ->select(
                'barangay',
                DB::raw('count(*) as total')
            )
            ->whereNotNull('barangay')
            ->groupBy('barangay')
            ->orderByDesc('total')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Return Analytics Dashboard
        |--------------------------------------------------------------------------
        */

        return view('admin.analytics', [

            /*
            | Farmer Profile
            */
            'totalFarmers' => $totalFarmers,
            'farmersPerBarangay' => $farmersPerBarangay,
            'farmersPerCrop' => $farmersPerCrop,

            'associationMembers' => $associationMembers,
            'nonAssociationMembers' => $nonAssociationMembers,
            'fourPsMembers' => $fourPsMembers,
            'nonFourPsMembers' => $nonFourPsMembers,

            'membershipOverview' => $membershipOverview,

            /*
            | Program Analytics
            */
            'totalPrograms' => $totalPrograms,
            'activePrograms' => $activePrograms,
            'upcomingPrograms' => $upcomingPrograms,
            'inactivePrograms' => $inactivePrograms,

            'programEnrollments' => $programEnrollments,

            /*
            | Eligibility
            */
            'eligibleFarmers' => $eligibleFarmers,
            'partiallyEligibleFarmers' => $partiallyEligibleFarmers,
            'notEligibleFarmers' => $notEligibleFarmers,
            'eligibilityTotal' => $eligibilityTotal,
            'eligibilityStatistics' => $eligibilityStatistics,

            /*
            | Recommendation Engine
            */
            'programRecommendationOverview' =>
                $programRecommendationOverview,

            'smartEligibilityReminders' =>
                $smartEligibilityReminders,

            'smartEligibilityReminderCount' =>
                $smartEligibilityReminderCount,

            /*
            | Distribution
            */
            'totalDistributions' => $totalDistributions,
            'completedDistributions' => $completedDistributions,
            'pendingDistributions' => $pendingDistributions,

            'distributionCompletionRate' =>
                $distributionCompletionRate,

            'distributionsPerProgram' =>
                $distributionsPerProgram,

            'distributionLogs' =>
                $distributionLogs,

            'programPerformanceSummary' =>
                $programPerformanceSummary,

            /*
            | Trends
            */
            'farmerRegistrationTrend' =>
                $farmerRegistrationTrend,

            'distributionTrend' =>
                $distributionTrend,

            'cropDistribution' =>
                $cropDistribution,

            'barangayDistribution' =>
                $barangayDistribution,

            /*
            | Year Filter
            */
            'reportingPeriod' => $period,
            'academicYears' =>
                $academicYears,

            'selectedAcademicYear' =>
                $selectedAcademicYear,

            'periodStart' =>
                $periodStart,

            'periodEnd' =>
                $periodEnd,
        ]);
    }
}