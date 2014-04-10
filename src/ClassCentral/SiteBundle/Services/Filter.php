<?php

namespace ClassCentral\SiteBundle\Services;

use ClassCentral\SiteBundle\Entity\Offering;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Filter {

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Given an array of offerings, it returns the subject tree containing only the subjects
     * from the offerings
     * @param $offerings
     */
    public function getOfferingSubjects($offerings)
    {
        $cache = $this->container->get('cache');

        // Get all the subjects for this offering and build a map
        $offSubjects = array();
        foreach($offerings as $section => $sectionOfferings)
        {
            foreach($sectionOfferings as $offering)
            {
                $sub = $offering['stream']['slug'];
                if(!isset($offSubjects[$sub]))
                {
                    $offSubjects[$sub] = true;
                }
            }
        }

        // Get the entire subject tree
        $allSubjects = $cache->get('allSubjects', array($this,'getSubjectsTree'));

        // Filter out the subjects that are not mentioned in these offerings
        foreach($allSubjects as $parent)
        {
            $hasChild = false;
            foreach($parent['children'] as $child)
            {
                if(!isset($offSubjects[$child['slug']]))
                {
                    unset($allSubjects[$parent['slug']]['children'][$child['slug']]);
                }
                else
                {
                    $hasChild = true;
                }
            }

            if(!$hasChild && !isset($offSubjects[$parent['slug']]))
            {
                unset($allSubjects[$parent['slug']]);
            }
        }

        return $allSubjects;
    }

    public function getCourseSubjects( $subjectIds = array())
    {
        $cache = $this->container->get('cache');
        // Get the entire subject tree
        $allSubjects = $cache->get('allSubjects', array($this,'getSubjectsTree'));

        // Filter out the subjects that are not mentioned in these offerings
        foreach($allSubjects as $parent)
        {
            $hasChild = false;
            foreach($parent['children'] as $child)
            {
                if(!in_array($child['id'],$subjectIds))
                {
                    unset($allSubjects[$parent['slug']]['children'][$child['slug']]);
                }
                else
                {
                    $hasChild = true;
                }
            }

            if(!$hasChild && !in_array($parent['id'],$subjectIds))
            {
                unset($allSubjects[$parent['slug']]);
            }
        }

        return $allSubjects;
    }

    /**
     * Builds a subject tree
     * @return array
     */
    public function getSubjectsTree()
    {
        $em = $this->container->get('Doctrine')->getManager();
        $allSubjects = $em->getRepository('ClassCentralSiteBundle:Stream')->findAll();
        $subjects = array();
        foreach($allSubjects as $subject)
        {
            if($subject->getParentStream())
            {
                $childSubjects[$subject->getParentStream()->getId()][] = $subject;
            }
            else
            {
                $subjects[$subject->getSlug()] = array(
                    'name' => $subject->getName(),
                    'id' => $subject->getId(),
                    'slug'=> $subject->getSlug()
                );

                $children = array();
                foreach($subject->getChildren() as $childSub)
                {
                    $children[$childSub->getSlug()] = array(
                        'name' => $childSub->getName(),
                        'id' => $childSub->getId(),
                        'slug'=> $childSub->getSlug()
                    );

                }
                $subjects[$subject->getSlug()]['children'] = $children;
            }
        }

        return $subjects;
    }

    public function getOfferingLanguages($offerings)
    {
        $cache = $this->container->get('cache');

        // Get all the languages for this offering and build a map
        $offLang = array();
        foreach($offerings as $section => $sectionOfferings)
        {
            foreach($sectionOfferings as $offering)
            {
                $lang = $offering['language']['name'];
                if(!isset($offLang[$lang]))
                {
                    $offLang[$lang] = true;
                }
            }
        }

        // Get language info
        $allLanguages = $cache->get('allLanguages', array($this,'getLanguages'));
        foreach($allLanguages as $lang)
        {
            $name = $lang['name'];
            if(!isset($offLang[$name]))
            {
                unset($allLanguages[$name]);
            }
        }

        return $allLanguages;
    }

    public function getCourseLanguages($languageIds = array())
    {
        $cache = $this->container->get('cache');

        // Get language info
        $allLanguages = $cache->get('allLanguages', array($this,'getLanguages'));
        foreach($allLanguages as $lang)
        {
            $name = $lang['name'];
            if( !in_array($lang['id'],$languageIds) )
            {
                unset($allLanguages[$name]);
            }
        }

        return $allLanguages;
    }
    public function getLanguages()
    {
        $em = $this->container->get('Doctrine')->getManager();

        $languages = array();
        foreach($em->getRepository('ClassCentralSiteBundle:Language')->findAll() as $lang)
        {
            $languages[$lang->getName()] = array(
                'name' => $lang->getName(),
                'id' => $lang->getId()
            );
        }

        return $languages;
    }

    public function getCourseSessions ($sessions = array())
    {
        $s = array();
        $allSessions = Offering::$types;
        foreach($allSessions as $key => $value)
        {
            if ( in_array($key,$sessions) )
            {
                $s[$key] = $value;
            }
        }

        return $s;
    }
} 