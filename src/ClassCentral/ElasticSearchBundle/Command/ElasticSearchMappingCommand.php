<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/12/14
 * Time: 2:28 PM
 */

namespace ClassCentral\ElasticSearchBundle\Command;


use ClassCentral\ElasticSearchBundle\DocumentType\CourseDocumentType;
use ClassCentral\ElasticSearchBundle\DocumentType\ESJobDocumentType;
use ClassCentral\ElasticSearchBundle\DocumentType\ESJobLogDocumentType;
use ClassCentral\ElasticSearchBundle\DocumentType\SuggestDocumentType;
use ClassCentral\ElasticSearchBundle\Scheduler\ESJob;
use ClassCentral\ElasticSearchBundle\Scheduler\ESJobLog;
use ClassCentral\ElasticSearchBundle\Types\DocumentType;
use ClassCentral\SiteBundle\Entity\Course;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticSearchMappingCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this->setName("classcentral:elasticsearch:mapping")
              ->setDescription("Manage deletion/creation of indexes, mapping")
              ->addArgument('type', InputArgument::REQUIRED, "directory/scheduler")
              ->addOption('delete', null, InputOption::VALUE_OPTIONAL, "Delete mappping and data before recreating it");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deleteMapping = ($input->getOption('delete') == 'Yes');
        $type = $input->getArgument('type');


        if($type == 'directory')
        {
            $output->writeln("Updating mapping for directory");
            $this->updateDirectoryMapping( $deleteMapping );
        }
        elseif ($type == 'scheduler')
        {
            $output->writeln("Updating mapping for scheduler");
            $this->updateSchedulerMapping( $deleteMapping);
        }
        else
        {
            $output->writeln("Invalid type. Type should one of the following - directory, scheduler");
        }



    }

    private function updateDirectoryMapping( $deleteMapping)
    {
        $es = $this->getContainer()->get('es_client'); // Elastic Search client

        // Do it for the course mapping
        $params = array();
        $params['index'] = $this->getContainer()->getParameter('es_index_name');
        $params['type']  = 'course';

        if( $deleteMapping )
        {
            $es->indices()->deleteMapping($params);
        }

        // Get the mapping
        $cDoc = new CourseDocumentType(new Course(), $this->getContainer());

        $mapping = array(
            '_source' => array(
                'enabled' => true
            ),
            'properties' => $cDoc->getMapping()

        );
        $params['body']['course'] = $mapping;

        $es->indices()->putMapping($params);

        // Suggest Document mapping
        $params = array();
        $params['index'] = $this->getContainer()->getParameter('es_index_name');
        $params['type']  = 'suggest';

        if( $deleteMapping )
        {
            $es->indices()->deleteMapping($params);
        }

        // Get the mapping
        $sDoc = new SuggestDocumentType(new Course(), $this->getContainer());
        $mapping = array(
            '_source' => array(
                'enabled' => true
            ),
            'properties' => $sDoc->getMapping()

        );
        $params['body']['suggest'] = $mapping;
        $es->indices()->putMapping($params);
    }

    private function updateSchedulerMapping( $deleteMapping )
    {

        $jobDoc = new ESJobDocumentType( new ESJob( 'fake_id' ), $this->getContainer());

        $this->createDocMapping(
            $jobDoc,
            $this->getContainer()->getParameter('es_scheduler_index_name'),
            $deleteMapping
        );

        // put job log mapping
        $jobLogDoc = new ESJobLogDocumentType( new ESJobLog(), $this->getContainer() );
        $this->createDocMapping(
            $jobLogDoc,
            $this->getContainer()->getParameter('es_scheduler_index_name'),
            $deleteMapping
        );


    }


    private function createDocMapping ( DocumentType $doc, $index, $deleteMapping)
    {
        $es = $this->getContainer()->get('es_client'); // Elastic Search client
        $params = array();
        $params['index'] = $index;
        $params['type']  = $doc->getType();

        if( $deleteMapping )
        {
            $es->indices()->deleteMapping( $params );
        }

        $mapping = array(
            '_source' => array(
                'enabled' => true
            ),
            'properties' => $doc->getMapping()
        );

        $params['body'][ $doc->getType() ] = $mapping;

        $es->indices()->putMapping( $params );
    }

} 