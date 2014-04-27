<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/24/14
 * Time: 8:32 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;
use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus;
use ClassCentral\SiteBundle\Entity\UserCourse;

class CourseStartReminderJob extends SchedulerJobAbstract{

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
        $user = $this->getRepository('ClassCentralSiteBundle:User')->findOneBy( $userId );

        if(!$user)
        {
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "User with id $userId not found"
            );
        }

        // TODO: Implement this

        $numCourses = count($args[UserCourse::LIST_TYPE_INTERESTED]) + count( $args[UserCourse::LIST_TYPE_ENROLLED] );
        if( $numCourses == 1)
        {
            // Just one course
        }
        else
        {
            // Multiple courses
        }


        /*
        Different scenarios
        1. 1 interested course
        2. 1 enrolled course
        3. Multiple interested course
        4. Multiple enrolled course
        5. Mix of interested and enrolled
        6. Start today or Starting Soon
        */

        return SchedulerJobStatus::getStatusObject(SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS, "Email sent");

    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }
}