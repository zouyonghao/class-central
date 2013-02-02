<?php

namespace ClassCentral\SiteBundle\Command\DataMigration;

use ClassCentral\SiteBundle\Command\DataMigration\VersionAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;

/**
 *  Moving the initiative_id column from offering to courses.
 * 
 */
class Version1 extends VersionAbstractInterface {

    public function migrate() {

        $this->output->writeln("Getting Started with migration version 1");
        
        // Get all offerings
        $em = $this->container->get('Doctrine')->getEntityManager();
        $offerings = $em
            ->getRepository('ClassCentralSiteBundle:Offering')
            ->findAll();
        
        foreach ($offerings as $offering) {
            $name = $offering->getName();
            $this->output->writeln($name);
            $initiative = null;
            if ($offering->getInitiative()) {
                $initiative = $offering->getInitiative();
            }
            $stream = $offering->getCourse()->getStream();

            // Check if the course name and initiative exist
            $course = $em->getRepository('ClassCentralSiteBundle:Course')
                ->findOneBy(array('name' => $name, 'initiative' => $initiative));
            if (!$course) {
                // Course does not exist. Create the course
                //$this->output->writeln("NOT FOUND");
                $course = new Course();
                $course->setName($name);
                $course->setInitiative($initiative);
                $course->setStream($stream);
                $em->persist($course);
                $em->flush();

                $this->output->writeln($course->getName() . ' ' . $course->getId());
            }

            // Update the course id in offering
            $offering->setCourse($course);
            $em->persist($offering);
            $em->flush();
        }
        
        // Delete all the courses with course id 100
        $em->createQuery("DELETE FROM ClassCentralSiteBundle:Course c WHERE c.initiative=100")->execute() ;
        // Delete initative
        $em->createQuery("DELETE FROM ClassCentralSiteBundle:Initiative i WHERE i.id = 100")->execute();
    }

}

