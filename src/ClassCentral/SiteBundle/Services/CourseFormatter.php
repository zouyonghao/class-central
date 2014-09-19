<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 9/16/14
 * Time: 7:16 PM
 */

namespace ClassCentral\SiteBundle\Services;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Utility\CourseUtility;
use ClassCentral\SiteBundle\Utility\ReviewUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Formats a course for different outputs
 * Class CourseFormatter
 * @package ClassCentral\SiteBundle\Services
 */
class CourseFormatter {

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * HTML format for the blog
     * @param Course $course
     */
    public function blogFormat( Course $course )
    {
        $router = $this->container->get('router');
        $rs = $this->container->get('review');

        $line1 = '';  // Course name
        $line2 = '';  // Institution name
        $line3 = '';  // Next Session


        $ratings = $rs->getRatings( $course->getId() );

        // LINE 1
        $url = $router->generate('ClassCentralSiteBundle_mooc', array('id' => $course->getId(), 'slug' => $course->getSlug()));
        $name = $course->getName();
        $line1 = "<a href='$url'><b>$name</b></a>";

        // LINE 2
        if($course->getInstitutions()->count() > 0)
        {
            $ins = $course->getInstitutions()->first();
            $insName = $ins->getName();
            $line2 = "<i>via $insName</i>";
        }

        // LINE 3
        $nextOffering = CourseUtility::getNextSession( $course);
        if( $nextOffering )
        {
            $displayDate = $nextOffering->getDisplayDate();
            $directUrl = $nextOffering->getUrl();
            $states = CourseUtility::getStates( $nextOffering );
            if( in_array('past',$states) )
            {
                $displayDate = 'TBA';
            }
            if( in_array('selfpaced',$states) )
            {
                $displayDate = 'Self Paced';
            }

            $line3 = "<b> <a href='$url' target='_blank'>Go To Class</a> | Next Session : $displayDate </b>";
        }

        return $line1 . '<br/>' . $line2 . '<br/>' . $line3 . '<br/>';

    }
} 