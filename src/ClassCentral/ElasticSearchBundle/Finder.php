<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/16/14
 * Time: 12:07 PM
 */

namespace ClassCentral\ElasticSearchBundle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Finds and retrieves courses for the course
 * listing pages
 * Class Finder
 * @package ClassCentral\ElasticSearchBundle
 */
class Finder {

    private $container;
    private $cp; // CoursePaginated - retrieve courses


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->cp = $container->get( 'es_cp');
    }

    public function byProvider( $provider, $filters= array(), $sort = array(), $page = 1 )
    {
        $query = array(
            'term' => array(
                'provider.code' => $provider
            )
        );

        return $this->cp->find( $query, $filters, $this->getFacets(), $sort,$page );

    }

    public function bySubject( $subject, $filters= array(), $sort = array(), $page = 1 )
    {
        $query = array(
            'term' => array(
                'subjects.slug' => $subject
            )
        );

        return $this->cp->find( $query, $filters, $this->getFacets(), $sort,$page );
    }

    public function byInstitution( $institution, $filters= array(), $sort = array(), $page = 1 )
    {
        $query = array(
            'term' => array(
                'institutions.slug' => strtolower($institution)
            )
        );

        return $this->cp->find( $query, $filters, $this->getFacets(), $sort,$page );
    }

    public function byLanguage( $language, $filters= array(), $sort = array(), $page = 1 )
    {
        $query = array(
            'term' => array(
                'language.slug' => $language
            )
        );

        return $this->cp->find( $query, $filters, $this->getFacets(), $sort,$page );
    }

    public function byTime( $status, $filters= array(), $sort = array(), $page = 1 )
    {
        $query = array(
            'term' => array(
                'nextSession.states' => strtolower($status)
            )
        );

        return $this->cp->find( $query, $filters, $this->getFacets(), $sort,$page );
    }

    public function byTag( $tag, $filters= array(), $sort = array(), $page = 1 )
    {
        $query = array(
            'term' => array(
                'tags' => strtolower($tag)
            )
        );

        return $this->cp->find( $query, $filters, $this->getFacets(), $sort,$page );
    }

    public function getFacetCounts($results)
    {
        $subjectIds = array();
        foreach($results['facets']['subjects']['terms'] as $term)
        {
            $subjectIds[$term['term']] = $term['count'];
        }

        $languageIds = array();
        foreach($results['facets']['language']['terms'] as $term)
        {
            $languageIds[ $term['term'] ] = $term['count'];
        }

        $sessions = array();
        foreach( $results['facets']['sessions']['terms'] as $term )
        {
            $sessions[ $term['term'] ] = $term['count'];
        }
        return array(
            'subjectIds' => $subjectIds,
            'languageIds' => $languageIds,
            'sessions'    => $sessions,
        );
    }

    private function getFacets()
    {
        $facets = array(
            "subjects" => array(
                'terms' => array(
                    'field' => 'subjects.id',
                    'size' => 40
                )
            ),
            "language" => array(
                'terms' => array(
                    'field' => 'language.id',
                    'size' => 40
                )
            ),
            "sessions" => array(
                "terms" => array(
                    'field' => 'nextSession.states',
                    'size' => 10
                )
            ),
        );

        return $facets;
    }
} 