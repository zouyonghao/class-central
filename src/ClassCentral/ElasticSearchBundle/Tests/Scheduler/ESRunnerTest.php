<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/22/14
 * Time: 8:51 PM
 */

namespace ClassCentral\ElasticSearchBundle\Tests\Scheduler;


use ClassCentral\ElasticSearchBundle\Scheduler\ESJob;
use ClassCentral\ElasticSearchBundle\Scheduler\ESRunner;
use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus;

class ESRunnerTest extends \PHPUnit_Framework_TestCase  {

    private function getContainerMock()
    {
        // Mock the logger

        $logger = $this
            ->getMockBuilder( 'Monolog\Logger', array('info', 'error') )
            ->disableOriginalConstructor()
            ->getMock();
        $logger
            ->expects( $this->any() )
            ->method( "info" )
            ->will( $this->returnValue(true));
        $logger
            ->expects( $this->any() )
            ->method( "error" )
            ->will( $this->returnValue(true));

        // Mock the container
        $container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface' , array('get'))
            ->disableOriginalConstructor()
            ->getMock();

        $container
            ->expects( $this->at(0) )
            ->method('get')
            ->with( 'monolog.logger.scheduler' )
            ->will( $this->returnValue($logger));

        return $container;
    }

    public function testRun()
    {
        $container = $this->getContainerMock();
        $runner = new ESRunner();
        $runner->setContainer( $container );
        $job = $this->getJob();

        // Valid class
        $status = $runner->run( $job );
        $this->assertEquals( SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS, $status->getStatus() );

        // Invalid class
        $job->setClass('ClassDoesNotExit');
        $status = $runner->run( $job );
        $this->assertEquals( SchedulerJobStatus::SCHEDULERJOB_STATUS_CLASS_NOT_FOUND, $status->getStatus() );
    }

    public function testRunById()
    {
        $job = $this->getJob();
        $container = $this->getContainerMock();


        // Mock the indexer
        $indexer = $this
            ->getMockBuilder('ClassCentral\ElasticSearchBundle\Indexer', array('index'))
            ->disableOriginalConstructor()
            ->getMock();

        $indexer
            ->expects( $this->exactly(1) )
            ->method('index')
            ->with( $this->containsOnlyInstancesOf('ClassCentral\ElasticSearchBundle\Scheduler\ESJobLog') )
            ->will( $this->returnValue(true) );

        $container
            ->expects( $this->at(2)  )
            ->method('get')
            ->with( 'es_indexer' )
            ->will( $this->returnValue( $indexer ));

        // Mock Elastic Search Schedule retrieve calls
        $es_scheduler = $this
            ->getMockBuilder('ClassCentral\ElasticSearchBundle\API\Scheduler', array('findJobById'))
            ->disableOriginalConstructor()
            ->getMock();

        $es_scheduler
            ->expects( $this->exactly(1) )
            ->method('findJobById')
            ->with( $job->getId() )
            ->will( $this->returnValue( $this->getESResultArray($job)) );


        $container
            ->expects( $this->at(1) )
            ->method('get')
            ->with( 'es_scheduler' )
            ->will( $this->returnValue( $es_scheduler ));



        $runner = new ESRunner();
        $runner->setContainer( $container );
        $status = $runner->runById( $job->getId() );

        $this->assertEquals( SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS, $status->getStatus());
    }

    public  function testRunByDate()
    {
        $job = $this->getJob();
        $dt = new \DateTime();
        $container = $this->getContainerMock();


        // Mock the indexer
        $indexer = $this
            ->getMockBuilder('ClassCentral\ElasticSearchBundle\Indexer', array('index'))
            ->disableOriginalConstructor()
            ->getMock();

        $indexer
            ->expects( $this->exactly(1) )
            ->method('index')
            ->with( $this->containsOnlyInstancesOf('ClassCentral\ElasticSearchBundle\Scheduler\ESJobLog') )
            ->will( $this->returnValue(true) );

        $container
            ->expects( $this->at(2)  )
            ->method('get')
            ->with( 'es_indexer' )
            ->will( $this->returnValue( $indexer ));

        // Mock Elastic Search Schedule retrieve calls
        $es_scheduler = $this
            ->getMockBuilder('ClassCentral\ElasticSearchBundle\API\Scheduler', array('findJobsByDateAndType'))
            ->disableOriginalConstructor()
            ->getMock();

        $results = array(
            'hits' => array(
                'total' => 1,
                'hits' => array(
                    $this->getESResultArray($job)
                )
            ),
        );

        $es_scheduler
            ->expects( $this->exactly(1) )
            ->method('findJobsByDateAndType')
            ->with( $dt->format('Y-m-d'), $job->getJobType()  )
            ->will( $this->returnValue( $results ) );

        $container
            ->expects( $this->at(1) )
            ->method('get')
            ->with( 'es_scheduler' )
            ->will( $this->returnValue( $es_scheduler ));

        $runner = new ESRunner();
        $runner->setContainer( $container);
        $runner->runByDate( $dt, $job->getJobType() );

    }

    private function getJob()
    {
        $job = new ESJob( 'job_id' );
        // Using the dummy job
        $job->setClass( 'ClassCentral\ElasticSearchBundle\Scheduler\DummyJob' );
        $job->setRunDate( new \DateTime());
        $job->setCreated( new \DateTime() );
        $job->setJobType('email');
        $job->setArgs( array('user_id' => 5) );

        return $job;
    }

    private function getESResultArray( ESJob $job)
    {
        return array(
            '_source' => ESJob::getArrayFromObj( $job ),
            '_id' => $job->getId()
        );
    }

}