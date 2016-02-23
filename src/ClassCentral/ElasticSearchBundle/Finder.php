<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/16/14
 * Time: 12:07 PM
 */

namespace ClassCentral\ElasticSearchBundle;
use ClassCentral\SiteBundle\Entity\CourseStatus;
use ClassCentral\SiteBundle\Entity\Item;
use ClassCentral\SiteBundle\Entity\Offering;
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

    public function byFollows($follows,$filters= array(), $sort = array(), $page = 1,$must = array(),$mustNot =array())
    {
        $institutionIds = $follows[Item::ITEM_TYPE_INSTITUTION];
        $providerIds = $follows[Item::ITEM_TYPE_PROVIDER];
        $subjectIds = $follows[Item::ITEM_TYPE_SUBJECT];

        $startingSoonScoreMultiplier = 1;
        if( isset ($must['terms']['subjects.id']) )
        {
            $startingSoonScoreMultiplier = 1000;
        }

        $mn =  array(
            array(
                "term" => array(
                    'course.nextSession.status' => Offering::START_DATES_UNKNOWN,
                )
            )
        );

        

        if( !empty($mustNot) )
        {
            $mn[] = $mustNot;
        }


        $query = array(
             "function_score" => array(
                 'query' => array(
                    'bool' => array(
                        'should' => array(
                            array(
                                'terms' => array(
                                    'subjects.id' => $subjectIds,
                                    'boost' => 3
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
                        // Remove sessions where the start date is unknown
                        'must_not' => $mustNot
                    )
                 ),
                 "script_score" => array(
                    "script" =>  "
                        rating = doc['ratingSort'].value;
                        ratingCount = doc['reviewsCount'].value;
                        followed =  doc['followed'].value;
                        startingSoon = doc['startingSoon'].value;
                        newCourse = doc['new'].value ;
                        numSessions =  doc['numSessions'].value;

                        // Calculate boost for ratings
                        ratingScore = 0;
                        if(rating > 4) {
                            ratingScore = rating*25;
                        } else if (rating >= 3) {
                            ratingScore = rating*10;
                        }
                        ratingScore += (int)ratingCount/2;

                        // Calculate the boost for course popularity
                        followedScore = (int) followed/10;

                        // Calculate score based on newness
                        newScore = newCourse*500;

                        // Starting soon score
                        startingSoonScore = startingSoon*{$startingSoonScoreMultiplier};

                        // Number of sessions

                        numSessionsScore = numSessions*20;
                        return _score*(ratingScore + followedScore + startingSoonScore + newScore - numSessionsScore  +  1);
                    "
                 )
        ));

        return $this->cp->find( $query, $filters, $this->getFacets(), $sort, $page,$must );
    }

    public function search( $keyword, $filters= array(), $sort = array(), $page = 1 )
    {
        if(empty($keyword)) return false;

        $query = array(
            "function_score" => array(
                "query" => array(
                    "multi_match" => array(
                        "query" => $keyword,
                        "type" => "best_fields",
                        "fields" => array(
                            'name^3',
                            'description',
                            'longDescription',
                            'syllabus',
                            'institutions.name',
                            'institutions.slug',
                            'instructors',
                            'provider.slug',
                            'provider.name',
                            "subjects.name^2",
                            "subject.slug",
                            "tags"
                        ),
                        "tie_breaker" => 0.1,
                        "minimum_should_match" => "2<75%"
                    )
                ),
                "script_score" => array(
                    "script" =>  "
                        rating = doc['ratingSort'].value;
                        followed =  doc['followed'].value;
                        startingSoon = doc['startingSoon'].value;
                        newCourse = doc['new'].value ;

                        return _score*( rating* 25 + followed/15 + startingSoon*150 + 1);
                    "
                )
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