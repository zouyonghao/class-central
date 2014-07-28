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
            ->addArgument('type', InputArgument::REQUIRED, "directory/scheduler")
            ->addArgument('action', InputArgument::REQUIRED, "delete/create the index");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');
        $type   = $input->getArgument('type');
        $esClient = $this->getContainer()->get('es_client');

        $index = null;
        if($type == 'directory')
        {
            $index = $this->getContainer()->getParameter('es_index_name');
        }
        elseif ($type == 'scheduler')
        {
            $index = $this->getContainer()->getParameter('es_scheduler_index_name');
        }
        else
        {
            $output->writeln("Invalid index name");
            return;
        }

        $params = array();
        $params['index'] = $index;

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