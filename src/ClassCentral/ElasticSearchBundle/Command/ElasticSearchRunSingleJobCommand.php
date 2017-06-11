<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/10/17
 * Time: 4:12 PM
 */

namespace ClassCentral\ElasticSearchBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticSearchRunSingleJobCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName( 'classcentral:estest' )
            ->addArgument('job_id', InputArgument::REQUIRED, "Rob id of the job to be run")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobId = $input->getArgument('job_id');
        $runner    = $this->getContainer()->get('es_runner');

        // This is for testing only. Don't run the job in prod environment
        if($this->getContainer()->getParameter('kernel.environment') == 'prod')
        {
            $output->writeln("<error>Cannot run this job in prod env</error>");
            return;
        }

        $status = $runner->runById( $jobId );
        $output->writeln($status);
    }
}