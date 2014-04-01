<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/30/14
 * Time: 8:07 PM
 */

namespace ClassCentral\ElasticSearchBundle\Command;


use ClassCentral\ElasticSearchBundle\Indexer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticSearchIndexerCommand extends ContainerAwareCommand{

    protected function configure()
    {
        $this->setName("classcentral:elasticsearch:index")
             ->setDescription("Indexes documents into elastic search");
    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexer = $this->getContainer()->get('es_indexer');
        $indexer->setContainer($this->getContainer());

        $courses = $this->getContainer()->get('doctrine')->getManager()
                    ->getRepository('ClassCentralSiteBundle:Course')->findAll();

        foreach($courses as $course)
        {
            $indexer->index($course);
        }
    }
} 