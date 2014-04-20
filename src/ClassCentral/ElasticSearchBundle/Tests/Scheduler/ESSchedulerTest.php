<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/20/14
 * Time: 12:07 AM
 */

namespace ClassCentral\ElasticSearchBundle\Tests\Scheduler;


use ClassCentral\ElasticSearchBundle\Scheduler\ESJob;
use ClassCentral\ElasticSearchBundle\Scheduler\ESScheduler;

class ESSchedulerTest extends \PHPUnit_Framework_TestCase {

    public function testScheduler()
    {
        // Mock the indexer
        $indexer = $this
            ->getMockBuilder('ClassCentral\ElasticSearchBundle\Indexer', array('index'))
            ->getMock();
        $indexer
            ->expects( $this->once() )
            ->method( 'index' )
            ->with( $this->containsOnlyInstancesOf('ClassCentral\ElasticSearchBundle\Scheduler\ESJob'), $this->anything() )
            ->will( $this->returnValue(true) );


        // Mock the container
        $container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface' , array('get'))
            ->disableOriginalConstructor()
            ->getMock();

        $container
            ->expects( $this->once())
            ->method('get')
            ->with( $this->stringContains('es_indexer'))
            ->will( $this->returnValue($indexer));

        $scheduler = new ESScheduler();
        $scheduler->setContainer( $container );

        $id = $scheduler->schedule( new \DateTime(), 'email', 'ClassName', array());

        $this->assertNotEmpty( $id );
    }
} 