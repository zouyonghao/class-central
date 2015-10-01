<?php
namespace ClassCentral\SiteBundle\Command\Network;

use ClassCentral\SiteBundle\Command\Network\NetworkAbstractInterface;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Utility\ReviewUtility;

class RedditNetwork extends NetworkAbstractInterface
{
    public static $coursesByLevel = array(
        'beginner' => array(
            442,1586,1578,1325,1046,320,1010,831,1580,303,375, 335, 1341, 441, 4891, 408, 1797, 1891, 2013, 1952, 2013,
            306, 529, 1983, 1850,1957, 2042,2175,1650,732,1857,1727,
             1349, 2298, 1651,2195, 2486, 2659,2660, 2661, 2129, 2448, 1243,
            2630, 2954, 2957,2731,3196, 2809, 2938, 3253, 3338, 3234,
            3396, 2813, 3231, 3486, 3444, 3483, 3353, 3925,4049,2525, 4239, 4256,
            4258, 4307, 4319, 4062, 4333, 4294, 2532
        ),
        'intermediate' => array(
            824,599,616,1176,1470,1188,1585,1205,462,1178,339,1478,1479,1480,328,366,323,
            324,325,364,365,457,455,592, 551, 1299, 1701, 1523, 921, 846, 1457, 1742, 1282,
            650, 417, 594, 1187, 1737, 1738, 1487,849,475, 1021, 835, 428,359,1152,487,1779,1816,1209,526,340, 724, 764,588,
            1748, 374, 1777,1875,531,1238,422,1529, 443, 1206, 1377, 1848, 1865 , 1881,
            533, 489, 1906, 451, 453, 426, 600, 558, 1724,
            2189,1715,1716,1717,1718,1719,1720,1712,1713,1714,1704,2211,2212,
            2215, 2335,2336, 2067,1053,1725,2214, 1345, 376, 2340, 342,
            1766, 2406, 745,  1240, 2427, 2658, 2503, 452,  548, 429, 2147, 1728, 2144,
            2716, 2244, 1730, 2861, 2445, 2997, 2898,2999,2998,2996, 627, 3082, 2960, 3082,
            2717, 2290, 3076, 3026, 1031, 2983, 445, 2291, 3075, 2964, 3198, 3077, 3273, 2734, 2942, 3230, 3254, 3255,
            3288, 3152, 2916, 1186, 3295, 3350, 3352, 2339, 3351, 3393, 3523, 3524, 3525, 3526, 3527, 3455, 3078,
            3536, 3459, 891, 3472, 3465, 3343, 3348, 3341, 3339, 3535, 3079, 3579, 3580, 3581,3582, 3583,3584,
            1564, 1836,1837, 3200, 2737, 3758, 3475, 3476, 3478, 3928, 2738, 3642,3717, 4013, 4050, 4071,
            4108,4109, 4174,3466, 4191, 4248, 4268, 4337, 4164, 4196, 4212,4184,4419, 4240, 4269, 4305, 4343, 4348, 4169, 4197,
            4200, 4203, 4206, 4187, 4230
        ),
        'advanced' => array(
            427,449,414,319,326,549,550, 552, 425, 1847,1848, 1849, 2018, 2458, 3000, 595, 1729, 2733,1623, 3256,1016, 1018, 1024, 1025, 1020,
            3289, 2735, 1029, 3458, 3290, 2965, 2781, 2736, 3531, 3473, 3474, 3433, 3291, 3692, 3555, 3917, 3655, 3024,  4352, 3954,
            3332
        )
    );

    public static function getCourseToLevelMap()
    {
        $map = array();
        foreach(self::$coursesByLevel as $level => $courses)
        {
            foreach($courses as $course)
            {
                $map[$course] = $level;
            }
        }
        return $map;
    }

    public function outInitiative( $name , $offeringCount)
    {
        $this->output->writeln( strtoupper($name) . "({$offeringCount})");
        $this->output->writeln('');
    }

    public function beforeOffering()
    {
        // Table header row
        $this->output->writeln("Course Name|Start Date|Length (in weeks)|Provider|Rating");
        $this->output->writeln(":--|:--:|:--:|:--:|:--:");
    }


    public function outOffering(Offering $offering)
    {
        $rs = $this->container->get('review');

        $name = '[' . $offering->getCourse()->getName(). ']' . '(' . $offering->getUrl() . ')';

        if($offering->getInitiative() == null)
        {
            $initiative = 'Others';
        }
        else
        {
            $initiative = $offering->getInitiative()->getName();
        }

        $startDate = $offering->getDisplayDate();


        $length = 'NA';
        if(  $offering->getCourse()->getLength() != 0)
        {
            $length = $offering->getCourse()->getLength() ;
        }

        // Rating
        $courseRating = round($rs->getRatings($offering->getCourse()->getId()),1);
        $courseReviews = $rs->getReviewsArray( $offering->getCourse()->getId() );
        $reviewText = '';
        if($courseRating == 0)
        {
            $courseRating = 'NA';
        }
        else
        {
            $reviewText = sprintf("(%d %s)", $courseReviews['count'], ($courseReviews['count'] == 1 ) ? 'review' : 'reviews');
        }
        $url = 'https://www.class-central.com'. $this->router->generate('ClassCentralSiteBundle_mooc', array('id' => $offering->getCourse()->getId(), 'slug' => $offering->getCourse()->getSlug()));
        $url .= '#course-all-reviews';
        $ratingStars = ReviewUtility::getRatingStars($courseRating);
        $rating = "$ratingStars [$reviewText]($url)";

        $this->output->writeln("$name|$startDate|$length|$initiative|$rating");
    }

    }


