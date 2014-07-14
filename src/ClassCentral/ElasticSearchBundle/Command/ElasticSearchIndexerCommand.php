<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/30/14
 * Time: 8:07 PM
 */

namespace ClassCentral\ElasticSearchBundle\Command;


use ClassCentral\ElasticSearchBundle\Indexer;
use ClassCentral\SiteBundle\Controller\StreamController;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticSearchIndexerCommand extends ContainerAwareCommand{

    protected function configure()
    {
        $this->setName("classcentral:elasticsearch:indexer")
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
        $em = $this->getContainer()->get('doctrine')->getManager();
        $cache = $this->getContainer()->get('cache');


        $subjects = $cache->get('stream_list_count',
            array( new StreamController(), 'getSubjectsList'),
            array( $this->getContainer() )
        );
        foreach($subjects['parent'] as $subject)
        {
            $indexer->index($subject);
        }
        foreach($subjects['children'] as $childSubjects)
        {
            foreach( $childSubjects as $subject)
            {
                $indexer->index($subject);
            }
        }

        $courses = $this->getContainer()->get('doctrine')->getManager()
                    ->getRepository('ClassCentralSiteBundle:Course')->findAll();

        foreach($courses as $course)
        {
            $indexer->index($course);
        }


    }
} 