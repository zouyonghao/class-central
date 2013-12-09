<?php

namespace ClassCentral\SiteBundle\Services;

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
} 