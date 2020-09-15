<?php

namespace Platform\Controllers\App;

use App\User;
use App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class AnalyticsController extends \App\Http\Controllers\Controller
{
    /*
    |--------------------------------------------------------------------------
    | Analytics Controller
    |--------------------------------------------------------------------------
    */

    /**
     * Get earning analytics.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getUserEarningAnalytics(Request $request) {
        // Period
        $start = $request->start ?? Carbon::now()->addDays(-14)->format('Y-m-d');
        $end = $request->end ?? Carbon::now()->format('Y-m-d');

        $selectedCampaigns = $request->campaigns;
        $selectedBusinesses = $request->businesses;
        $selectedSegments = $request->segments;
        $selectedStaff = $request->staff;

        // Get onboarding step
        $totals = auth()->user()->getUserTotals();

        // Filters
        $campaigns = auth()->user()->campaigns->pluck('name', 'id');
        $businesses = auth()->user()->businesses->pluck('name', 'id');
        $segments = auth()->user()->segments->pluck('name', 'id');
        $staff = auth()->user()->staff->pluck('name', 'id');
        $customers = auth()->user()->customers->pluck('name', 'id');

        // Period
        $period = new \DatePeriod( new \DateTime($start . ' 00:00:00'), new \DateInterval('P1D'), new \DateTime($end . ' 23:59:59'));

        $table = null;

        $table = auth()->user()->history()
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->where('points', '>', 0);

        if (is_array($selectedCampaigns) && count($selectedCampaigns) > 0) {
            $table = $table->whereHas('campaign', function($query) use ($selectedCampaigns) {
                $query->whereIn('campaign_id', $selectedCampaigns);
            });
        }

        if (is_array($selectedBusinesses) && count($selectedBusinesses) > 0) {
            $table = $table->whereHas('campaign', function($query) use ($selectedBusinesses) {
                $query->whereHas('business', function ($q) use ($selectedBusinesses){
                    $q->whereIn('business_id', $selectedBusinesses);
                });
            });
        }

        if (is_array($selectedSegments) && count($selectedSegments) > 0) {
            foreach ($selectedSegments as $segment_id) {
                $table = $table->whereHas('segments', function($query) use ($segment_id) {
                       $query->where('segment_id', $segment_id);
                });
            }
        }
        /*
        if (is_array($selectedSegments) && count($selectedSegments) > 0) {
            $table = $table->whereHas('segments', function($query) use ($selectedSegments) {
                $query->whereIn('segment_id', $selectedSegments);
            });
        }*/

        if (is_array($selectedStaff) && count($selectedStaff) > 0) {
            $table = $table->whereHas('staff', function($query) use ($selectedStaff) {
                $query->whereIn('staff_id', $selectedStaff);
            });
        }

        $table = $table->orderBy('created_at', 'asc')->get();

        $table = $table->map(function ($record) use ($campaigns, $staff, $customers) {
            $record->created_at = $record->created_at->timezone(auth()->user()->getTimezone());
            $record->customer_name = $customers[$record->customer_id];
            $record->campaign_name = $campaigns[$record->campaign_id];
            $record->staff_name = ($record->staff_id === null) ? $record->staff_name : $staff[$record->staff_id];
            if ($record->staff_name === null) $record->staff_name = '-';

            return collect($record)->only('uuid', 'staff_name', 'customer_name', 'campaign_name', 'description', 'icon', 'created_at', 'points');
        });

        // Table headers
        $tableHeaders = [
            ['text' => __('Campaign'), 'value' => 'campaign_name'],
            ['text' => __('Customer'), 'value' => 'customer_name'],
            ['text' => __('Event'), 'value' => 'description'],
            ['text' => __('Staff member'), 'value' => 'staff_name'],
            ['text' => __('Points'), 'value' => 'points', 'align' => 'right'],
            ['text' => __('Date'), 'value' => 'created_at', 'align' => 'right']
        ];

        $range = [];
        foreach($period as $date){
            $range[$date->format("Y-m-d")] = 0;
        }

        $data = auth()->user()->history()
            ->select([
              DB::raw('DATE(`created_at`) as `date`'),
              DB::raw('SUM(points) as `count`')
            ])
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->where('points', '>', 0);

        if (is_array($selectedCampaigns) && count($selectedCampaigns) > 0) {
            $data = $data->whereHas('campaign', function($query) use ($selectedCampaigns) {
                $query->whereIn('campaign_id', $selectedCampaigns);
            });
        }

        if (is_array($selectedBusinesses) && count($selectedBusinesses) > 0) {
            $data = $data->whereHas('campaign', function($query) use ($selectedBusinesses) {
                $query->whereHas('business', function ($q) use ($selectedBusinesses){
                    $q->whereIn('business_id', $selectedBusinesses);
                });
            });
        }

        if (is_array($selectedSegments) && count($selectedSegments) > 0) {
            foreach ($selectedSegments as $segment_id) {
                $data = $data->whereHas('segments', function($query) use ($segment_id) {
                       $query->where('segment_id', $segment_id);
                });
            }
        }
/*
        if (is_array($selectedSegments) && count($selectedSegments) > 0) {
            $data = $data->whereHas('segments', function($query) use ($selectedSegments) {
                $query->whereIn('segment_id', $selectedSegments);
            });
        }
*/
        if (is_array($selectedStaff) && count($selectedStaff) > 0) {
            $data = $data->whereHas('staff', function($query) use ($selectedStaff) {
                $query->whereIn('staff_id', $selectedStaff);
            });
        }

        $data = $data->groupBy('date')
            ->get()
            ->pluck('count', 'date');

        $dbData = [];
        $total = 0;
        if ($data !== null) {
            foreach($data as $date => $count) {
                $dbData[$date] = (int) $count;
                $total += $count;
            }
        }

        $chartData = array_replace($range, $dbData);

        $chart = [];
        $chart[] = ["Date", "Points earned"];
        foreach ($chartData as $date => $count) {
            $chart[] = [$date, $count];
        }

        $analytics = [
            'start' => $start,
            'end' => $end,
            'total' => [
                'onboardingStep' => $totals['onboardingStep'],
                'chart' => $total
            ],
            'campaigns' => $campaigns,
            'businesses' => $businesses,
            'segments' => $segments,
            'staff' => $staff,
            'chart' => $chart,
            'table' => $table,
            'tableHeaders' => $tableHeaders
        ];

        return response()->json($analytics, 200);
    }

    /**
     * Get spending analytics.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getUserSpendingAnalytics(Request $request) {
        // Period
        $start = $request->start ?? Carbon::now()->addDays(-14)->format('Y-m-d');
        $end = $request->end ?? Carbon::now()->format('Y-m-d');

        $selectedCampaigns = $request->campaigns;
        $selectedBusinesses = $request->businesses;
        $selectedSegments = $request->segments;
        $selectedStaff = $request->staff;

        // Get onboarding step
        $totals = auth()->user()->getUserTotals();

        // Filters
        $campaigns = auth()->user()->campaigns->pluck('name', 'id');
        $businesses = auth()->user()->businesses->pluck('name', 'id');
        $segments = auth()->user()->segments->pluck('name', 'id');
        $staff = auth()->user()->staff->pluck('name', 'id');
        $customers = auth()->user()->customers->pluck('name', 'id');

        // Period
        $period = new \DatePeriod( new \DateTime($start . ' 00:00:00'), new \DateInterval('P1D'), new \DateTime($end . ' 23:59:59'));

        $table = null;

        $table = auth()->user()->history()
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->where('points', '<', 0);

        if (is_array($selectedCampaigns) && count($selectedCampaigns) > 0) {
            $table = $table->whereHas('campaign', function($query) use ($selectedCampaigns) {
                $query->whereIn('campaign_id', $selectedCampaigns);
            });
        }

        if (is_array($selectedBusinesses) && count($selectedBusinesses) > 0) {
            $table = $table->whereHas('campaign', function($query) use ($selectedBusinesses) {
                $query->whereHas('business', function ($q) use ($selectedBusinesses){
                    $q->whereIn('business_id', $selectedBusinesses);
                });
            });
        }

        if (is_array($selectedSegments) && count($selectedSegments) > 0) {
            foreach ($selectedSegments as $segment_id) {
                $table = $table->whereHas('segments', function($query) use ($segment_id) {
                       $query->where('segment_id', $segment_id);
                });
            }
        }
        /*
        if (is_array($selectedSegments) && count($selectedSegments) > 0) {
            $table = $table->whereHas('segments', function($query) use ($selectedSegments) {
                $query->whereIn('segment_id', $selectedSegments);
            });
        }*/

        if (is_array($selectedStaff) && count($selectedStaff) > 0) {
            $table = $table->whereHas('staff', function($query) use ($selectedStaff) {
                $query->whereIn('staff_id', $selectedStaff);
            });
        }

        $table = $table->orderBy('created_at', 'asc')->get();

        $table = $table->map(function ($record) use ($campaigns, $staff, $customers) {
            $record->created_at = $record->created_at->timezone(auth()->user()->getTimezone());
            $record->customer_name = $customers[$record->customer_id];
            $record->campaign_name = $campaigns[$record->campaign_id];
            $record->points = abs($record->points);
            $record->staff_name = ($record->staff_id === null) ? $record->staff_name : $staff[$record->staff_id];
            if ($record->staff_name === null) $record->staff_name = '-';

            return collect($record)->only('uuid', 'staff_name', 'customer_name', 'campaign_name', 'reward_title', 'description', 'icon', 'created_at', 'points');
        });

        // Table headers
        $tableHeaders = [
            ['text' => __('Campaign'), 'value' => 'campaign_name'],
            ['text' => __('Customer'), 'value' => 'customer_name'],
            ['text' => __('Reward'), 'value' => 'reward_title'],
            ['text' => __('Cost'), 'value' => 'points', 'align' => 'right'],
            ['text' => __('Event'), 'value' => 'description'],
            ['text' => __('Staff member'), 'value' => 'staff_name'],
            ['text' => __('Date'), 'value' => 'created_at', 'align' => 'right']
        ];

        $range = [];
        foreach($period as $date){
            $range[$date->format("Y-m-d")] = 0;
        }

        $data = auth()->user()->history()
            ->select([
              DB::raw('DATE(`created_at`) as `date`'),
              DB::raw('SUM(ABS(points)) as `count`')
            ])
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->where('points', '<', 0);

        if (is_array($selectedCampaigns) && count($selectedCampaigns) > 0) {
            $data = $data->whereHas('campaign', function($query) use ($selectedCampaigns) {
                $query->whereIn('campaign_id', $selectedCampaigns);
            });
        }

        if (is_array($selectedBusinesses) && count($selectedBusinesses) > 0) {
            $data = $data->whereHas('campaign', function($query) use ($selectedBusinesses) {
                $query->whereHas('business', function ($q) use ($selectedBusinesses){
                    $q->whereIn('business_id', $selectedBusinesses);
                });
            });
        }

        if (is_array($selectedSegments) && count($selectedSegments) > 0) {
            foreach ($selectedSegments as $segment_id) {
                $data = $data->whereHas('segments', function($query) use ($segment_id) {
                       $query->where('segment_id', $segment_id);
                });
            }
        }
/*
        if (is_array($selectedSegments) && count($selectedSegments) > 0) {
            $data = $data->whereHas('segments', function($query) use ($selectedSegments) {
                $query->whereIn('segment_id', $selectedSegments);
            });
        }*/

        if (is_array($selectedStaff) && count($selectedStaff) > 0) {
            $data = $data->whereHas('staff', function($query) use ($selectedStaff) {
                $query->whereIn('staff_id', $selectedStaff);
            });
        }

        $data = $data->groupBy('date')
            ->get()
            ->pluck('count', 'date');

        $dbData = [];
        $total = 0;
        if ($data !== null) {
            foreach($data as $date => $count) {
                $dbData[$date] = (int) abs($count);
                $total += abs($count);
            }
        }

        $chartData = array_replace($range, $dbData);

        $chart = [];
        $chart[] = ["Date", "Points earned"];
        foreach ($chartData as $date => $count) {
            $chart[] = [$date, $count];
        }

        $analytics = [
            'start' => $start,
            'end' => $end,
            'total' => [
                'onboardingStep' => $totals['onboardingStep'],
                'chart' => $total
            ],
            'campaigns' => $campaigns,
            'businesses' => $businesses,
            'segments' => $segments,
            'staff' => $staff,
            'chart' => $chart,
            'table' => $table,
            'tableHeaders' => $tableHeaders
        ];

        return response()->json($analytics, 200);
    }
}