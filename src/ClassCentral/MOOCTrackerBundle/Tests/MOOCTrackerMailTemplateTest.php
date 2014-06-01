<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/28/14
 * Time: 12:24 AM
 */

namespace ClassCentral\MOOCTrackerBundle\Tests;

use ClassCentral\MOOCTrackerBundle\Job\CourseStartReminderJob;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserPreference;
use ClassCentral\SiteBundle\Utility\CryptUtility;

require_once dirname(__DIR__).'/../../../app/AppKernel.php';
/**
 * Checks whether the templates are rendering properly
 * Class MOOCTrackerMailTemplateTest
 * @package ClassCentral\MOOCTrackerBundle\Tests
 */
class MOOCTrackerMailTemplateT extends \PHPUnit_Framework_TestCase {

    private $kernel;
    private $container;

    /**
     * Initialize a kernel to retrieve values
     */
    public function setUp()
    {
        $this->kernel = new \AppKernel('test', true);
        $this->kernel->boot();

        $this->container = $this->kernel->getContainer();

        parent::setUp();
    }

    public function testTemplatesAreBeingRendered()
    {
        $t = $this->container->get('templating');

        $u = new User();
        $u->setName('Dummy User');
        $u->setEmail('fake@email.com');

        $courses = array();
        $course = array(
            'id' => 5,
            'name' => 'Dummy Course',
            'shortDesc' => 'Dummy Course',
            'initiative' => array(
                'name' => 'Independent',
                'code' => 'Independent'
            ),
            'slug' =>'fake_slug',
            'nextOffering' => array(
                'displayDate' => '2014-05-05',
                'url' => 'http://example.com'
            ),
            'institutions' => array()
        );
        $courses[] = array(
            'interested' => false,
            'id' => 5,
            'course' => $course,

        );
        $counts = array(
            'offeringCount' => array(
                'recent' => '50',
                'selfpaced' => '100'
            )
        );

        $html = $t->renderResponse('ClassCentralMOOCTrackerBundle:Reminder:multiple.courses.inlined.html', array(
            'courses' => $courses,
            'baseUrl' => $this->container->getParameter('baseurl'),
            'user' => $u,
            'jobType' => CourseStartReminderJob::JOB_TYPE_1_DAY_BEFORE,
            'counts' => $counts,
            'unsubscribeToken' => CryptUtility::getUnsubscribeToken( $u,
                    UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES,
                    $this->container->getParameter('secret')
                )
        ))->getContent();

        $this->assertNotEmpty( $html );

        $html = $t->renderResponse('ClassCentralMOOCTrackerBundle:Reminder:single.course.inlined.html', array(
            'course' => $course,
            'baseUrl' => $this->container->getParameter('baseurl'),
            'interested' => false,
            'user' => $u,
            'counts' => $counts,
            'jobType' => CourseStartReminderJob::JOB_TYPE_1_DAY_BEFORE,
            'unsubscribeToken' => CryptUtility::getUnsubscribeToken( $u,
                    UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES,
                    $this->container->getParameter('secret')
                )
        ))->getContent();

        $this->assertNotEmpty( $html );
    }
} 