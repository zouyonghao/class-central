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

    public function byProvider( $provider )
    {
        $query = array(
            'term' => array(
                'provider.code' => $provider
            )
        );

        return $this->cp->find( $query );

    }
} 