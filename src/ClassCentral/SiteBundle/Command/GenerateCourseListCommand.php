<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/20/18
 * Time: 11:51 AM
 */

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Entity\CourseStatus;
use ClassCentral\SiteBundle\Utility\CourseUtility;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCourseListCommand extends ContainerAwareCommand
{
    private $types = ['standard', 'biz-tech-english'];

    protected function configure()
    {
        $this
            ->setName('classcentral:csv:courses')
            ->setDescription("Generates course csv")
            ->addArgument('type', InputArgument::OPTIONAL,"Which type i.e. " . implode(', ', $this->types))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = ($input->getArgument('type')) ? $input->getArgument('type') : 'standard';
        if(!in_array($type,$this->types))
        {
            $type = 'standard';
        }

        $today = new \DateTime();
        $todayString = $today->format('j_M_Y');
        $fileName = 'classcentral_' .$type .'_' . $todayString . '.csv';

        $this->logToSlack("Generating $fileName");

        if($type == 'standard')
        {
            $this->generateCourseListStandard($fileName);
        }

        if($type == 'biz-tech-english')
        {
            $this->generateCourseListBizTechEnglish($fileName);
        }

        $this->logToSlack("$fileName generated");

    }

    private function generateCourseListBizTechEnglish($fileName)
    {
        $courses = $this->getContainer()->get('doctrine')->getManager()
            ->getRepository('ClassCentralSiteBundle:Course')
            ->findAll();

        $subjects = ['cs','programming-and-software-development','business','data-science','engineering'];
        $fp = fopen("tmp/$fileName", "w");

        // Add a title line to the CSV
        $title = array(
            'Course Id',
            'Course Name',
            'Provider',
            'Universities/Institutions',
            'Parent Subject',
            'Child Subject',
            'Category',
            'Url',
            'Next Session Date',
            'Duration',
            'Effort',
            'Language',
            'Video(Url)',
            'Course Description',
            'Credential Name',
            'Created',
            'Status'
        );
        fputcsv($fp,$title);
        $dt = new \DateTime('2016-07-31');
        foreach($courses as $course)
        {
            $formatter = $course->getFormatter();
            if($course->getStatus() != CourseStatus::AVAILABLE )
            {
                continue;
            }
            if($course->getCreated() > $dt)
            {
                //continue;
            }

            if(!$course->getIsMooc())
            {
                continue;
            }

            if(!$course->getLanguage())
            {
                continue;
            }

            if($course->getLanguage()->getName() != 'English')
            {
                continue;
            }
            $provider = $course->getInitiative() ? $course->getInitiative()->getName() : "Independent" ;
            $ins = array();
            foreach($course->getInstitutions() as $institution)
            {
                $ins[] = $institution->getName();
            }

            $nextSession = $course->getNextOffering();
            $date = "";
            $url = $course->getUrl();
            if($nextSession)
            {
                $url = $nextSession->getUrl();
                $date = $nextSession->getDisplayDate();
            }

            $subject = $course->getStream();
            $subjectSlugs = [];
            $subjectSlugs[] = $subject->getSlug();
            if($subject->getParentStream())
            {
                $subjectSlugs[] = $subject->getParentStream()->getSlug();
                $parent = $subject->getParentStream()->getName();
                $subject = $subject->getName();

            }
            else
            {
                $parent = $subject->getName();
                $subject = "";
            }

            foreach ($course->getSubjects() as $sub)
            {
                $subjectSlugs[] = $sub->getSlug();
                if($sub->getParentStream())
                {
                    $subjectSlugs[] = $sub->getParentStream()->getSlug();
                }
            }
            $result = array_intersect($subjectSlugs, $subjects);

            if(empty($result))
            {
                continue;
            }


            $language = 'English';
            if($course->getLanguage())
            {
                $language = $course->getLanguage()->getName();
            }

            $credential = '';
            if ( !$course->getCredentials()->isEmpty() )
            {
                $cred = $course->getCredentials()->first();
                $credential = $cred->getName();
            }

            $created = null;
            if ($course->getCreated())
            {
                $created = $course->getCreated()->format('Y-m-d');
            }

            $description = $course->getLongDescription();
            if(!$description)
            {
                $description = $course->getDescription();
            }

            $status = '';
            if( $course->getNextOffering() )
            {
                $states = array_intersect( array('past','ongoing','selfpaced','upcoming'), CourseUtility::getStates( $course->getNextOffering() ));
                if(!empty($states))
                {
                    $status = array_pop($states);
                }
            }

            $line = array(
                $course->getId(),
                $course->getName(),
                $provider,
                implode($ins,"|||"),
                $parent,
                $subject,
                $course->getStream()->getName(),
                $url,
                $date,
                $formatter->getDuration(),
                $formatter->getWorkload(),
                $language,
                $course->getVideoIntro(),
                $course->getDescription(),
                $credential,
                $created,
                $status
            );

            fputcsv($fp,$line);
        }
        fclose($fp);
    }

    private function generateCourseListStandard($fileName)
    {
        $courses = $this->getContainer()->get('doctrine')->getManager()
            ->getRepository('ClassCentralSiteBundle:Course')
            ->findAll();


        $fp = fopen("tmp/$fileName", "w");
        
        // Add a title line to the CSV
        $title = array(
            'Course Id',
            'Course Name',
            'Provider',
            'Universities/Institutions',
            'Parent Subject',
            'Child Subject',
            'Category',
            'Url',
            'Next Session Date',
            'Length',
            'Language',
            'Video(Url)',
            'Course Description',
            'Credential Name',
            'Created',
            'Status'
        );
        fputcsv($fp,$title);
        $dt = new \DateTime('2016-07-31');
        foreach($courses as $course)
        {
            if($course->getStatus() != CourseStatus::AVAILABLE )
            {
                continue;
            }
            if($course->getCreated() > $dt)
            {
                //continue;
            }

            if(!$course->getIsMooc())
            {
                continue;
            }
            $provider = $course->getInitiative() ? $course->getInitiative()->getName() : "Independent" ;
            $ins = array();
            foreach($course->getInstitutions() as $institution)
            {
                $ins[] = $institution->getName();
            }

            $nextSession = $course->getNextOffering();
            $date = "";
            $url = $course->getUrl();
            if($nextSession)
            {
                $url = $nextSession->getUrl();
                $date = $nextSession->getDisplayDate();
            }

            $subject = $course->getStream();
            if($subject->getParentStream())
            {
                $parent = $subject->getParentStream()->getName();
                $subject = $subject->getName();
            }
            else
            {
                $parent = $subject->getName();
                $subject = "";
            }

            $language = 'English';
            if($course->getLanguage())
            {
                $language = $course->getLanguage()->getName();
            }

            $credential = '';
            if ( !$course->getCredentials()->isEmpty() )
            {
                $cred = $course->getCredentials()->first();
                $credential = $cred->getName();
            }

            $created = null;
            if ($course->getCreated())
            {
                $created = $course->getCreated()->format('Y-m-d');
            }

            $description = $course->getLongDescription();
            if(!$description)
            {
                $description = $course->getDescription();
            }

            $status = '';
            if( $course->getNextOffering() )
            {
                $states = array_intersect( array('past','ongoing','selfpaced','upcoming'), CourseUtility::getStates( $course->getNextOffering() ));
                if(!empty($states))
                {
                    $status = array_pop($states);
                }
            }

            $line = array(
                $course->getId(),
                $course->getName(),
                $provider,
                implode($ins,"|||"),
                $parent,
                $subject,
                $course->getStream()->getName(),
                $url,
                $date,
                $course->getLength(),
                $language,
                $course->getVideoIntro(),
                $course->getDescription(),
                $credential,
                $created,
                $status
            );

            fputcsv($fp,$line);
        }
        fclose($fp);
    }

    public function logToSlack( $message )
    {
        $channel = '#cc-activity-data';
        if($this->getContainer()->getParameter('kernel.environment') != 'prod')
        {
            $channel = $this->getContainer()->getParameter('slack_review_channel');
        }

        try
        {
            $this->getContainer()
                ->get('slack_client')
                ->to($channel)
                ->from( "classcentral:csv:courses" )
                ->send( $message );
        }
        catch(\Exception $e)
        {
            echo $e->getMessage();
        }
    }

}