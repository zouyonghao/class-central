<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dhawal
 * Date: 3/23/13
 * Time: 11:02 PM
 * To change this template use File | Settings | File Templates.
 */

namespace ClassCentral\ScraperBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ClassCentral\ScraperBundle\Scraper\ScraperFactory;

class ScraperCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("classcentral:scrape")
            ->setDescription("Scrapes courses")
            ->addArgument('initiative',InputArgument::REQUIRED,"Initiative code")
            ->addOption('simulate',null,InputOption::VALUE_OPTIONAL,"N if database needs to be modified. Defaults to Y") // value is Y or N
            ->addOption('type',null,InputOption::VALUE_OPTIONAL,"'add' - create offerings. 'update' - update already created offerings. Defaults to update");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $initiativeCode = $input->getArgument('initiative');
        // Check if initiative exists
        $initiative = $this->getContainer()
                        ->get('doctrine')
                        ->getManager()
                        ->getRepository('ClassCentralSiteBundle:Initiative')
                        ->findOneBy(array('code' => $initiativeCode));
        if($initiative == null)
        {
            $output->writeln("Invalid initiative code $initiativeCode");
            return;
        }

        $simulate = $input->getOption("simulate");
        if(empty($simulate) || $simulate != 'N')
        {
            $simulate = 'Y';
        }

        $type = $input->getOption("type");
        if(empty($type) || $type != 'add')
        {
            $type = 'update';
        }

        // Initiate the factory
        $scraperFactory = new ScraperFactory($initiative);
        $scraperFactory->setSimulate($simulate);
        $scraperFactory->setType($type);
        $scraperFactory->setOutputInterface($output);
        $scraperFactory->setContainer($this->getContainer());
        $scraperFactory->setDomParser($this->getContainer()->get('dom_parser'));

        $scraper = $scraperFactory->getScraper();
        $offerings = $scraper->scrape();
        $offeringCount = count($offerings);
        $output->writeln("<info>{$type} {$offeringCount} courses for {$initiative->getName()}</info>");
        foreach($offerings as $offering)
        {
            $output->writeln($offering->getName());
        }

    }

}