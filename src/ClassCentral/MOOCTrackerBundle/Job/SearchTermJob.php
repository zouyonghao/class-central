<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/2/14
 * Time: 7:18 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;
use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus;

class SearchTermJob extends SchedulerJobAbstract {

    const JOB_TYPE_NEW_COURSES = 'mt_search_new_courses'; // Send notification for newly added courses
    const JOB_TYPE_RECENT_COURSES = 'mt_search_recent_courses'; // Send notifications for courses that are about to start

    public function setUp()
    {
        // TODO: Implement setUp() method.
    }

    /**
     * Must return an object of type SchedulerJobStatus
     * @param $args
     * @return SchedulerJobStatus
     */
    public function perform($args)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $userId = $this->getJob()->getUserId();
        $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneBy( array( 'id' => $userId) );
        $cache = $this->getContainer()->get('cache');
    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }
}