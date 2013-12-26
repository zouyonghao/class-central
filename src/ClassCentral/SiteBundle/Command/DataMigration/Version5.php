<?php

namespace ClassCentral\SiteBundle\Command\DataMigration;

use ClassCentral\SiteBundle\Entity\UserCourse;

/**
 * Class Version5
 * Migrate mooc_tracker_courses to users_courses_table
 * @package ClassCentral\SiteBundle\Command\DataMigration
 */
class Version5 extends VersionAbstractInterface{

    public function migrate()
    {
        $this->output->writeln("Starting data migration version 5");

        $em = $this->container->get('Doctrine')->getManager();
        $moocTrackerCourses = $em->getRepository('ClassCentralSiteBundle:MoocTrackerCourse')->findAll();

        foreach($moocTrackerCourses as $mtc)
        {
            $uc = new UserCourse();
            $uc->setUser($mtc->getUser());
            $uc->setCourse($mtc->getCourse());
            $uc->setCreated($mtc->getCreated());
            $uc->setListId(UserCourse::LIST_TYPE_MOOC_TRACKER);
            $em->persist($uc);
        }

        $em->flush();
    }
}