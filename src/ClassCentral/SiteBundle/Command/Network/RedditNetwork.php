<?php
namespace ClassCentral\SiteBundle\Command\Network;

use ClassCentral\SiteBundle\Command\Network\NetworkAbstractInterface;
use ClassCentral\SiteBundle\Entity\Offering;

class RedditNetwork extends NetworkAbstractInterface
{
    public static $coursesByLevel = array(
        'beginner' => array(
            442,1586,1578,1325,1046,1481,320,1010,904,831,1580,303,375,640,590, 335, 1341, 441, 4891
        ),
        'intermediate' => array(
            824,599,616,1176,1470,1188,1585,1205,462,1178,339,1478,1479,1480,328,366,323,
            324,325,364,365,457,455,592, 551, 1299, 1701, 1523, 921, 846, 1457, 1742, 1282,
            650, 417, 594, 1187, 1737, 1738,1646, 1487,849
        ),
        'advanced' => array(
            427,449,414,465,319,326,549,550, 552, 425
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
        $this->output->writeln("Course Name|Start Date|Length");
        $this->output->writeln(":--|:--:|:--:");
    }


    public function outOffering(Offering $offering)
    {
        $name = '[' . $offering->getName(). ']' . '(' . $offering->getUrl() . ')';

        if($offering->getInitiative() == null)
        {
            $initiative = 'Others';
        }
        else
        {
            $initiative = $offering->getInitiative()->getName();
        }

        $startDate = 'NA';
        if($offering->getStatus() == Offering::START_DATES_KNOWN)
        {
            $startDate = $offering->getStartDate()->format('M jS');
        }
        else if ( $offering->getStatus() == Offering::COURSE_OPEN)
        {
            $startDate = 'Self Paced';
        }

        $length = 'NA';
        if(  $offering->getLength() != 0)
        {
            $length = $offering->getLength() . ' weeks';
        }

        $this->output->writeln("$name|$startDate|$length|$initiative");
    } 

}


