<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/12/14
 * Time: 2:28 PM
 */

namespace ClassCentral\ElasticSearchBundle\Command;


use ClassCentral\ElasticSearchBundle\DocumentType\CourseDocumentType;
use ClassCentral\SiteBundle\Entity\Course;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticSearchMappingCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this->setName("classcentral:elasticsearch:mapping")
              ->setDescription("Manage deletion/creation of indexes, mapping")
              ->addOption('delete', null, InputOption::VALUE_OPTIONAL, "Delete mappping and data before recreating it");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deleteMapping = ($input->getOption('delete') == 'Yes');

        $es = $this->getContainer()->get('es_client'); // Elastic Search client

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
    }

} 