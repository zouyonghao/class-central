<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/4/18
 * Time: 1:28 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AdvertisingController extends Controller
{
    private $adUnits = [
        'Course Page Text Ad', 'PageHeader Image Ad', 'Sidebar Text Ad', 'Table Text Ad'
    ];

    /**
     * Show a list of all the ads for all Advertiser. Show CTRS for the advertiser and all individual ads
     * @param Request $request
     * @param $advertiser
     */
    public function statsAction(Request $request)
    {
        $keenClient = $this->get('keen');
        $timeFrame = "this_28_days";
        if($request->query->get('relative'))
        {
            $timeFrame = $request->query->get('relative');
        }

        if($request->query->get('start') && $request->query->get('end'))
        {
            $timeFrame = [
                'start' => $request->query->get('start'),
                'end' => $request->query->get('end')
            ];
        }


        $adStats = $keenClient->getAdStatsGroupedByAds($timeFrame);
        $adStatsByAdvertisers = $keenClient->getAdStatsGroupedByAdvertiser($timeFrame);

        return $this->render('ClassCentralSiteBundle:Advertising:advertiser_stats_summary.html.twig', [
            'adStats' => $adStats,
            'adStatsByAdvertisers' => $adStatsByAdvertisers,
            'timeFrames' => $this->generateMonthlyTimeFrames(),
            'timeFrame' => $timeFrame
        ]);
    }

    /**
     * Show ad stats for a particular advertiser
     * @param Request $request
     * @param $advertiser
     */
    public function statsByAdvertiserAction(Request $request, $advertiser)
    {
        $keenClient = $this->get('keen');
        $adStatsByMonth = $keenClient->getAdStatsGroupedByAdvertiserAndMonthly();


        /*
        $adStatsByUnit = [];
        foreach ($this->generateMonthlyTimeFrames() as $month => $timeFrame)
        {
            $monthlyStats = $keenClient->getAdStatsGroupedByAdvertiserAndUnit($timeFrame);
            if(isset($monthlyStats[$advertiser]))
            {
                foreach ($this->adUnits as $adUnit)
                {
                    $adStatsByUnit[$adUnit][$month] = [
                        'clicks' => 0,
                        'impressions' => 0,
                        'unit' => $adUnit
                    ];
                }

                foreach ($monthlyStats[$advertiser] as $msAdunit => $msAdStats)
                {
                    $adStatsByUnit[$msAdunit][$month] = $msAdStats;
                }
            }
        }
        */
        $adStatsByUnit = $keenClient->getAdStatsGroupedByAdvertiserAndUnit();

        return $this->render('ClassCentralSiteBundle:Advertising:stats_by_advertiser.html.twig', [
            'adStatsByMonth' => $adStatsByMonth,
            'advertiser' => $advertiser,
            'adStatsByUnit' => $adStatsByUnit
        ]);
    }


    private function generateMonthlyTimeFrames()
    {
        $timeFrames = [];

        $startYear = 2017;

        $today = new \DateTime();
        $currentYear = $today->format('Y');
        $currentMonth = $today->format('m');

        for($year = $startYear; $year <= $currentYear; $year++)
        {
            for($month = 1; $month <= 12; $month++)
            {
                $start = new \DateTime("{$year}-{$month}-01");
                $lastDay = $start->format('t');
                $end = new \DateTime("{$year}-{$month}-{$lastDay}");
                $timeFrames[$start->format("M, Y")] = [
                    'start' => $start->format(\DateTime::ISO8601),
                    'end' => $end->format(\DateTime::ISO8601)
                ];

                if($year == $currentYear && $month == $currentMonth)
                {
                    break;
                }
            }
        }

        return array_reverse($timeFrames, true);
    }
}