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

    public function byCourseIds( $ids = array(), $filters= array(), $sort = array(), $page = 1 )
    {
        $query = array(
            'terms' => array(
                'id' => $ids
            )
        );

        return $this->cp->find( $query, $filters, $this->getFacets(), $sort, $page );
    }

    public function byFollows($institutionIds,$subjectIds, $providerIds,$filters= array(), $sort = array(), $page = 1)
    {

        $query = array(
            'bool' => array(
                'should' => array(
                    array(
                        'terms' => array(
                            'subjects.id' => $subjectIds
                        )),
                    array(
                        'terms' => array(
                            'institutions.id' => $institutionIds,
                        )),
                    array(
                        'terms' => array(
                            'provider.id' => $providerIds
                        )),

                ),
                'minimum_should_match' => "50%"
            )
        );

        return $this->cp->find( $query, $filters, $this->getFacets(), $sort, $page );
    }

    public function search( $keyword, $filters= array(), $sort = array(), $page = 1 )
    {
        $query = array(
            "multi_match" => array(
                "query" => $keyword,
                "type" => "best_fields",
                "fields" => array(
                    'name^2',
                    'description',
                    'longDescription',
                    'syllabus',
                    'institutions.name',
                    'institutions.slug',
                    'instructors',
                    'provider.slug',
                    'provider.name',
                    "subjects.name",
                    "subject.slug",
                    "searchDesc"
                ),
                "tie_breaker" => 0.1,
                "minimum_should_match" => "2<75%"

            ),
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