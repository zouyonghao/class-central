<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 12/16/15
 * Time: 2:15 PM
 */

namespace ClassCentral\SiteBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Keen
{
    private $container;
    private $keenClient;

    private $adAnalyticsStartMonth = 7;
    private $adAnalyticsStartYear = 2017;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->keenClient = $container->get('keen_io');
    }

    public function getClient()
    {
        return $this->keenClient;
    }

    public function recordLogins(\ClassCentral\SiteBundle\Entity\User $user, $type)
    {
        try
        {
            $this->keenClient->addEvent('logins', array(
                'user_id' => $user->getAnonId(),
                'type' => $type
            ));
        } catch(\Exception $e) {

        }
    }

    /**
     * @param User $user
     * @param null $src
     */
    public function recordSignups(\ClassCentral\SiteBundle\Entity\User $user,  $src = null)
    {
        try {
            $this->keenClient->addEvent('signups', array(
                'user_id' => $user->getAnonId(),
                'type' => $user->getSignupTypeString(),
                'src' => $src
            ));
        } catch(\Exception $e) {

        }
    }

    public function getAdImpressions($timeFrame, $groups, $interval = null)
    {
        $params = [
            "group_by" => $groups,
            "timeframe" => $timeFrame,
        ];

        if($interval)
        {
            $params['interval'] = $interval;
        }

        return $this->keenClient->count("ad_impression", $params);
    }

    public function getAdClicks($timeFrame, $groups,$interval = null)
    {
        $params = [
            "group_by" => $groups,
            "timeframe" => $timeFrame,
        ];

        if($interval)
        {
            $params['interval'] = $interval;
        }

        return $this->keenClient->count("ad_click",$params);
    }

    public function getAdStatsGroupedByAds($timeFrame)
    {

        $groups = ["ad.provider","ad.title"];
        $adImpressions = $this->getAdImpressions($timeFrame, $groups);
        $adClicks = $this->getAdClicks($timeFrame, $groups);
        $adStats = [];
        foreach ($adImpressions['result'] as $result)
        {
            $advName = $result['ad.provider'];
            $adTitle = $result['ad.title'];
            if(!isset($adStats[$advName]))
            {
                $adStats[$advName] = [];
            }

            $adStats[$advName][$adTitle] = [
                'title' => $adTitle,
                'impressions' => $result['result'],
                'clicks' => 0
            ];
        }

        foreach ($adClicks['result'] as $result)
        {
            $advName = $result['ad.provider'];
            $adTitle = $result['ad.title'];

            if(!isset($adStats[$advName][$adTitle]))
            {
                $adStats[$advName][$adTitle] = [
                    'title' => $adTitle,
                    'impressions' => 0
                ];
            }

            $adStats[$advName][$adTitle]['clicks'] = $result['result'];
        }

        return $adStats;
    }

    public function getAdStatsGroupedByAdvertiser($timeFrame)
    {
        $groups = ["ad.provider"];

        $adImpressions = $this->getAdImpressions($timeFrame, $groups);
        $adClicks = $this->getAdClicks($timeFrame, $groups);
        $adStats = [];
        foreach ($adImpressions['result'] as $result)
        {
            $advName = $result['ad.provider'];
            $adStats[$advName] = [
                'name' => $advName,
                'impressions' => $result['result'],
                'clicks' => 0
            ];
        }

        foreach ($adClicks['result'] as $result)
        {
            $advName = $result['ad.provider'];


            if(!isset($adStats[$advName]))
            {
                $adStats[$advName] = [
                    'name' => $advName,
                    'impressions' => 0
                ];
            }

            $adStats[$advName]['clicks'] = $result['result'];
        }
        return $adStats;
    }

    public function getAdStatsGroupedByAdvertiserAndMonthly()
    {
        $groups = ["ad.provider"];
        $startDate = new \DateTime("{$this->adAnalyticsStartYear}-{$this->adAnalyticsStartMonth}-1");
        $endDate = new \DateTime();
        $timeFrame = [
            'start' => $startDate->format(\DateTime::ISO8601),
            'end' => $endDate->format(\DateTime::ISO8601)
        ];

        $interval = 'monthly';
        $adImpressions = $this->getAdImpressions($timeFrame, $groups, $interval);
        $adClicks = $this->getAdClicks($timeFrame, $groups, $interval);

        $adStats = [];

        foreach ($adClicks['result'] as $result)
        {
            $timeFrameStart = new \DateTime($result['timeframe']['start']);
            $monthYear = $timeFrameStart->format("M, Y");

            foreach ($result['value'] as $value)
            {
                $advertiser = $value['ad.provider'];
                if(!isset($adStats[$advertiser]))
                {
                    $adStats[$advertiser] = [];
                }

                $adStats[$advertiser][$monthYear] = [
                    'clicks' => $value['result'],
                    'impressions' => 0
                ];
            }
        }

        foreach ($adImpressions['result'] as $result)
        {
            $timeFrameStart = new \DateTime($result['timeframe']['start']);
            $monthYear = $timeFrameStart->format("M, Y");

            foreach ($result['value'] as $value)
            {
                $advertiser = $value['ad.provider'];
                if(!isset($adStats[$advertiser][$monthYear]))
                {
                    $adStats[$advertiser][$monthYear] = [
                        'clicks' => 0
                    ];
                }
                $adStats[$advertiser][$monthYear]['impressions'] = $value['result'];

            }
        }

        return $adStats;
    }



    public function getAdStatsGroupedByAdvertiserAndUnit()
    {
        $groups = ["ad.provider","ad.unit"];

        $startDate = new \DateTime("{$this->adAnalyticsStartYear}-{$this->adAnalyticsStartMonth}-1");
        $endDate = new \DateTime();
        $timeFrame = [
            'start' => $startDate->format(\DateTime::ISO8601),
            'end' => $endDate->format(\DateTime::ISO8601)
        ];

        $interval = 'monthly';
        $adImpressions = $this->getAdImpressions($timeFrame, $groups, $interval);
        $adClicks = $this->getAdClicks($timeFrame, $groups, $interval);

        $adStats = [];

        foreach ($adClicks['result'] as $result)
        {
            $timeFrameStart = new \DateTime($result['timeframe']['start']);
            $monthYear = $timeFrameStart->format("M, Y");

            foreach ($result['value'] as $value)
            {
                $advertiser = $value['ad.provider'];
                $unit = $value['ad.unit'];
                if(!isset($adStats[$advertiser][$unit]))
                {
                    $adStats[$advertiser][$unit] = [];
                }

                $adStats[$advertiser][$unit][$monthYear] = [
                    'clicks' => $value['result'],
                    'impressions' => 0
                ];
            }
        }

        foreach ($adImpressions['result'] as $result)
        {
            $timeFrameStart = new \DateTime($result['timeframe']['start']);
            $monthYear = $timeFrameStart->format("M, Y");

            foreach ($result['value'] as $value)
            {
                $advertiser = $value['ad.provider'];
                $unit = $value['ad.unit'];
                if(!isset($adStats[$advertiser][$unit][$monthYear]))
                {
                    $adStats[$advertiser][$unit][$monthYear] = [
                        'clicks' => 0
                    ];
                }
                $adStats[$advertiser][$unit][$monthYear]['impressions'] = $value['result'];

            }
        }


        return $adStats;
    }

}