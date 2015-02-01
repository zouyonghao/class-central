<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/31/15
 * Time: 7:33 PM
 */

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Services\Review;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SummarizeReviewCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('classcentral:reviews:summarize')
            ->setDescription('Summarize a review or all the reviews for a course')
            ->addArgument('type', InputArgument::REQUIRED,"course or review")
            ->addArgument('id',InputArgument::REQUIRED,"course id or review id")
        ;
    }


    protected function execute( InputInterface $input, OutputInterface $output)
    {
        $reviewService = $this->getContainer()->get('review');
        $type = strtolower($input->getArgument('type'));
        $id   =  intval( $input->getArgument('id') );

        if( !in_array( $type, array('course','review')) )
        {
            $output->writeln( "<error>type should be either review or course</error>" );
            return;
        }

        if( $type == 'course' )
        {
            $course = $this->getContainer()
                ->get('doctrine')->getManager()
                ->getRepository('ClassCentralSiteBundle:Course')
                ->find( $id );
            if (!$course)
            {
                // Course does not exist
                $output->writeln( "<error>Invalid course id $id</error>" );
            }
            else
            {
                // Create reviews
                $output->writeln("<info>Summarizing reviews for course - " . $course->getName() ."</info>");
                $response = $reviewService->summarizeReviewsForACourse($course);
                $output->writeln( "Number of Reviews Summarized: $response");
            }

        }
        else
        {
            // type is review
            $review = $this->getContainer()
                ->get('doctrine')->getManager()
                ->getRepository('ClassCentralSiteBundle:Review')
                ->find( $id );

            if (!$review)
            {
                // Course does not exist
                $output->writeln( "<error>Invalid review id $id</error>" );
            }
            else
            {
                $response = $reviewService->summarizeReview( $review );
                switch ($response)
                {
                    case Review::REVIEW_ALREADY_SUMMARIZED_OR_EMPTY_TEXT:
                        $output->writeln( "Review already summarized");
                        break;
                    case Review::REVIEW_SUMMARY_FAILED:
                        $output->writeln("Review summary failed");
                        break;
                    default:
                        $output->writeln("$response Summaries saved for review with id $id ");
                }
            }
        }
    }
} 