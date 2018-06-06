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

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->keenClient = $container->get('keen_io');
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

    public function getAdImpressions($timeFrame, $groups)
    {
        return $this->keenClient->count("ad_impression",[
            "group_by" => $groups,
            "timeframe" => $timeFrame
        ]);
    }

    public function getAdClicks($timeFrame, $groups)
    {
        return $this->keenClient->count("ad_click",[
            "group_by" => $groups,
            "timeframe" => $timeFrame
        ]);
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


}