<?php

/**
 * Contains a bunch of utility functions to save and retrieve information
 * from the database
 */
namespace ClassCentral\ScraperBundle\Utility;


use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Institution;
use ClassCentral\SiteBundle\Entity\Instructor;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Stream;
use Doctrine\ORM\EntityManager;

class DBHelper
{

    private $scraper;

    public function setScraper(ScraperAbstractInterface $scraper)
    {
        $this->scraper = $scraper;
    }

    /**
     * Creates a course if it does not exist
     * @param $name Name of the course
     * @param Initiative $initiative
     * @param Institution $ins
     * @return Course
     */
    public function createCourseIfNotExists($name, Initiative $initiative, Institution $ins = null, Stream $stream)
    {
        // Check if course exists
        $em = $this->scraper->getManager();
        $courseRepository = $em->getRepository('ClassCentralSiteBundle:Course');
        $course = $courseRepository->findOneBy(array(
            'name' => $name,
            'initiative' => $initiative->getId(),
        ));

        // Course exists
        if ($course)
        {
            return $course;
        }

        $course = new Course();
        $course->setName($name);
        $course->setInitiative($initiative);
        if ($ins)
        {
            $course->addInstitution($ins);
        }

        $course->setStream($stream);

        // Check if course is to be created
        if ($this->scraper->doModify() && $this->scraper->doCreate())
        {
            $em->persist($course);
            $em->flush();

            $this->scraper->out("COURSE $name created for initiative " . $initiative->getName());
        }

        return $course;

    }

    public function createInstructorIfNotExists($name)
    {
        $em = $this->scraper->getManager();
        $instructor = $em->getRepository('ClassCentralSiteBundle:Instructor')->findOneBy(
            array('name' => $name)
        );
        if ($instructor)
        {
            return $instructor;
        }

        $instructor = new Instructor();
        $instructor->setName($name);
        if($this->scraper->doCreate())
        {
            $this->scraper->out("NEW INSTRUCTOR $name");
            if ($this->scraper->doModify())
            {
                $em->persist($instructor);
                $em->flush();
            }
        }

        return $instructor;
    }

    public function createInstitutionIfNotExists(Institution $institution)
    {
        $em = $this->scraper->getManager();
        $ins = $em->getRepository('ClassCentralSiteBundle:Institution')->findOneBy(array(
            'slug' => $institution->getSlug(),
        ));

        if($ins)
        {
            // Institution exists
            return $ins;
        }

        if ($this->scraper->doCreate())
        {
            $this->scraper->out("NEW INSTITUTION:  {$institution->getName()}");
            if ($this->scraper->doModify() )
            {
                $em->persist($institution);
                $em->flush();

            }
        }
        return $institution;
    }

    public function getStreamBySlug($slug = 'cs')
    {
        $em = $this->scraper->getManager();
        $stream = $em->getRepository('ClassCentralSiteBundle:Stream')->findOneBy(array(
            'slug' => $slug,
        ));
        return $stream;
    }

    /**
     * A map between language_name => ClassCentralSiteBundle:Language
     */
    public function getLanguageMap()
    {
        $em = $this->scraper->getManager();
        $languages = $em->getRepository('ClassCentralSiteBundle:Language')->findAll();
        $languageMap = array();
        foreach($languages as $language)
        {
            $languageMap[$language->getName()] = $language;
        }

        return $languageMap;
    }

    public function getOfferingByShortName($shortName)
    {
        $em = $this->scraper->getManager();
        $offering = $em->getRepository('ClassCentralSiteBundle:Offering')->findOneBy(array(
                        'shortName' => $shortName
                    ));
        return $offering;
    }

    public function getCourseByShortName($shortName)
    {
        $em = $this->scraper->getManager();
        $course = $em->getRepository('ClassCentralSiteBundle:Course')->findOneBy(array(
            'shortName' => $shortName
        ));
        return $course;
    }

    public  function findCourseByName ($title, Initiative $initiative)
    {
        $em = $this->scraper->getManager();
        $result = $em->getRepository('ClassCentralSiteBundle:Course')->createQueryBuilder('c')
            ->where('c.initiative = :initiative' )
            ->andWhere('c.name LIKE :title')
            ->setParameter('initiative', $initiative)
            ->setParameter('title', '%'.$title)
            ->getQuery()
            ->getResult()
        ;
        if ( count($result) == 1)
        {
            return $result[0];
        }

        return null;
    }

}