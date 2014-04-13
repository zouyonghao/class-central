<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/12/14
 * Time: 9:03 PM
 */

namespace ClassCentral\ElasticSearchBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticSearchIndexCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('classcentral:elasticsearch:index')
            ->addArgument('action', InputArgument::REQUIRED, "delete/create the index");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');
        $esClient = $this->getContainer()->get('es_client');

        $params = array();
        $params['index'] = $this->getContainer()->getParameter('es_index_name');

        if($action == 'delete')
        {
            $esClient->indices()->delete( $params );
            $output->writeln("Index deleted");
        }
        elseif ($action == 'create')
        {
            $esClient->indices()->create( $params );
            $output->writeln("Index created");
        }
        else
        {
            $output->writeln("Invalid action. create/delete are valid actions");
        }
    }

} 