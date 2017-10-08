<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/1/14
 * Time: 12:54 AM
 */

namespace ClassCentral\ElasticSearchBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticSearchJobRunnerCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('classcentral:elasticsearch:runjobs')
            ->setDescription('Given a type and date, runs all jobs for that date')
            ->addArgument('type', InputArgument::REQUIRED, "type of jobs")
            ->addArgument("date", InputArgument::REQUIRED, "Date for which the jobs should be run eg. Y-m-d")
            ->addArgument('split_id',InputArgument::OPTIONAL,"The split id for this instance. i.e if split is 4, it can be 0,1,2,3");
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runner    = $this->getContainer()->get('es_runner');
        $now = new \DateTime();
        $output->writeln( "<comment>Job runner started on {$now->format('Y-m-d H:i:s')}</comment>");

        // Parse the input arguments
        $type = $input->getArgument('type');
        $date = $input->getArgument('date'); // The date at which the job is to be run
        $dateParts = explode('-', $date);
        if( !checkdate( $dateParts[1], $dateParts[2], $dateParts[0] ) )
        {
            $output->writeLn("<error>Invalid date or format. Correct format is Y-m-d</error>");
            return;
        }

        $splitId = ($input->getArgument('split_id')) ? (int)$input->getArgument('split_id') : 0;

        $output->writeln( "<comment>Running jobs for $type - $date - $splitId</comment>" );
        echo $splitId;

        $result = $runner->runByDate(new \DateTime($date), $type, $splitId);

        $output->writeln("<info>Ran {$result['total']} jobs</info>");
    }

} 