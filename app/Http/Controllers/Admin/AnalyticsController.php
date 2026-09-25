<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use App\Models\Recommendation;
use App\Models\WeatherPrediction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        /*
        |--------------------------------------------------------------------------
        | Academic / Program Year
        |--------------------------------------------------------------------------
        |
        | Example:
        | 2026-2027 = June 1, 2026 to May 31, 2027
        |
        */

        $currentYear = now()->month >= 6
            ? now()->year
            : now()->year - 1;

        $selectedAcademicYear = $request->input(
            'academic_year',
            $currentYear . '-' . ($currentYear + 1)
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Academic Year Format
        |--------------------------------------------------------------------------
        */

        if (! preg_match('/^\d{4}-\d{4}$/', $selectedAcademicYear)) {
            $selectedAcademicYear = $currentYear . '-' . ($currentYear + 1);
        }

        [$startYear, $endYear] = array_map(
            'intval',
            explode('-', $selectedAcademicYear)
        );

        /*
        |--------------------------------------------------------------------------
        | Make Sure Year Sequence Is Valid
        |--------------------------------------------------------------------------
        */

        if ($endYear !== $startYear + 1) {
            $startYear = $currentYear;
            $endYear = $currentYear + 1;

            $selectedAcademicYear = $startYear . '-' . $endYear;
        }

        $periodStart = Carbon::create($startYear, 6, 1)->startOfDay();
        $periodEnd = Carbon::create($endYear, 5, 31)->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Available Academic Years
        |--------------------------------------------------------------------------
        */

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

        $totalPrograms = Program::count();

        $now = now();

        /*
        |--------------------------------------------------------------------------
        | Automatic Program Status
        |--------------------------------------------------------------------------
        */

        $activePrograms = Program::query()
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->count();

        $upcomingPrograms = Program::query()
            ->whereNotNull('start_at')
            ->where('start_at', '>', $now)
            ->count();

        $inactivePrograms = Program::query()
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

        $programEnrollments = Program::query()
            ->withCount([
                'applications' => function ($query) use ($periodStart, $periodEnd) {
                    $query->whereBetween(
                        'created_at',
                        [$periodStart, $periodEnd]
                    );
                },
            ])
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

        $eligibleFarmers = Recommendation::query()
            ->whereIn('farmer_id', $periodFarmerIds)
            ->where('eligibility_status', 'eligible')
            ->distinct('farmer_id')
            ->count('farmer_id');

        $partiallyEligibleFarmers = Recommendation::query()
            ->whereIn('farmer_id', $periodFarmerIds)
            ->where('eligibility_status', 'partially_eligible')
            ->distinct('farmer_id')
            ->count('farmer_id');

        $notEligibleFarmers = Recommendation::query()
            ->whereIn('farmer_id', $periodFarmerIds)
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

        $programRecommendationOverview = Program::query()
            ->get()
            ->map(function ($program) use ($periodFarmerIds) {

                $recommendations = Recommendation::query()
                    ->where('program_id', $program->id)
                    ->whereIn('farmer_id', $periodFarmerIds)
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

        $smartEligibilityReminders = Recommendation::query()
            ->with([
                'farmer',
                'program',
            ])
            ->whereIn('farmer_id', $periodFarmerIds)
            ->where('eligibility_status', 'partially_eligible')
            ->latest()
            ->get()
            ->filter(function ($recommendation) {
                $missing = $recommendation->missing_requirements;

                return is_array($missing) && count($missing) === 1;
            })
            ->take(10)
            ->values();

        $smartEligibilityReminderCount = Recommendation::query()
            ->whereIn('farmer_id', $periodFarmerIds)
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

        $distributionQuery = Distribution::query()
            ->whereBetween(
                'created_at',
                [$periodStart, $periodEnd]
            );

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

        $distributionsPerProgram = Program::query()
            ->withCount([
                'distributions' => function ($query) use (
                    $periodStart,
                    $periodEnd
                ) {
                    $query->whereBetween(
                        'created_at',
                        [$periodStart, $periodEnd]
                    );
                },
            ])
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

        $programPerformanceSummary = Program::query()
            ->withCount([
                'applications' => function ($query) use (
                    $periodStart,
                    $periodEnd
                ) {
                    $query->whereBetween(
                        'created_at',
                        [$periodStart, $periodEnd]
                    );
                },

                'distributions' => function ($query) use (
                    $periodStart,
                    $periodEnd
                ) {
                    $query->whereBetween(
                        'created_at',
                        [$periodStart, $periodEnd]
                    );
                },
            ])
            ->get()
            ->map(function ($program) use ($periodFarmerIds) {

                $eligible = Recommendation::query()
                    ->where('program_id', $program->id)
                    ->whereIn('farmer_id', $periodFarmerIds)
                    ->where('eligibility_status', 'eligible')
                    ->count();

                return (object) [
                    'program' => $program,
                    'applications_count' => $program->applications_count,
                    'eligible_count' => $eligible,
                    'distributions_count' => $program->distributions_count,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | 04 - PREDICTIVE FARMER NEEDS / WEATHER
        |--------------------------------------------------------------------------
        */

        $weatherQuery = WeatherPrediction::query()
            ->whereBetween(
                'fetched_at',
                [$periodStart, $periodEnd]
            );

        /*
        |--------------------------------------------------------------------------
        | Weather Alerts
        |--------------------------------------------------------------------------
        */

        $totalWeatherAlerts = (clone $weatherQuery)
            ->where('alert_level', '!=', 'Normal')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Weather Alert Levels + Percentage
        |--------------------------------------------------------------------------
        */

        $weatherAlertsPerLevel = (clone $weatherQuery)
            ->select(
                'alert_level',
                DB::raw('count(*) as total')
            )
            ->groupBy('alert_level')
            ->orderByDesc('total')
            ->get();

        $totalWeatherRecords = (clone $weatherQuery)->count();

        $weatherAlertsPerLevel->transform(function ($item) use (
            $totalWeatherRecords
        ) {
            $item->percentage = $totalWeatherRecords > 0
                ? round(
                    ($item->total / $totalWeatherRecords) * 100,
                    2
                )
                : 0;

            return $item;
        });

        /*
        |--------------------------------------------------------------------------
        | Latest Weather For Selected Period
        |--------------------------------------------------------------------------
        */

        $latestWeather = (clone $weatherQuery)
            ->latest('fetched_at')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Farmers At Risk
        |--------------------------------------------------------------------------
        |
        | Uses the same crop-based rules as AtRiskFarmerService.
        |
        */

        $farmersAtRisk = collect();

        if ($latestWeather && $latestWeather->alert_level !== 'Normal') {

            $alert = $latestWeather->alert_level;

            $riskQuery = Farmer::query()
                ->whereBetween(
                    'created_at',
                    [$periodStart, $periodEnd]
                );

            if ($alert === 'Rain Alert') {

                $riskQuery->whereIn(
                    'crop_type',
                    ['Rice', 'Vegetables']
                );

            } elseif ($alert === 'Storm Warning') {

                // All farmers are at risk.

            } elseif ($alert === 'Extreme Heat') {

                $riskQuery->whereIn(
                    'crop_type',
                    ['Vegetables', 'Corn']
                );

            } elseif ($alert === 'Strong Wind') {

                $riskQuery->whereIn(
                    'crop_type',
                    ['Corn', 'Banana']
                );

            } else {

                $riskQuery->whereRaw('1 = 0');
            }

            $farmersAtRisk = $riskQuery
                ->latest()
                ->get();
        }

        $farmersAtRiskCount = $farmersAtRisk->count();

        /*
        |--------------------------------------------------------------------------
        | Weather History
        |--------------------------------------------------------------------------
        */

        $weatherHistory = (clone $weatherQuery)
            ->latest('fetched_at')
            ->limit(10)
            ->get();

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
            | Weather
            */
            'totalWeatherAlerts' =>
                $totalWeatherAlerts,

            'weatherAlertsPerLevel' =>
                $weatherAlertsPerLevel,

            'farmersAtRisk' =>
                $farmersAtRisk,

            'farmersAtRiskCount' =>
                $farmersAtRiskCount,

            'latestWeather' =>
                $latestWeather,

            'weatherHistory' =>
                $weatherHistory,

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