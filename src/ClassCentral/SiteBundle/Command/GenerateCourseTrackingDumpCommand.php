<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 2/21/14
 * Time: 2:57 PM
 */

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Entity\UserCourse;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCourseTrackingDumpCommand extends ContainerAwareCommand{

    protected function configure()
    {
        $this
            ->setName('classcentral:recommender:generatecsvs')
            ->setDescription("Generates csvs required for generating course recommendations");
        ;
    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->generateUserTrackingCSV();

        $this->generateCoursesCSV();

        $this->generateUserCoursesCSV();

    }


    private function generateUserTrackingCSV()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $tbl = "users_courses_tracking_tmp";
        // Create temporary table from users_courses_tracking wit rows which have user
        // identifier
        $conn->exec("
            CREATE TABLE $tbl
            AS (SELECT * FROM user_courses_tracking WHERE user_identifier != '');
        ");

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('user_identifier','user_identifier');
        $rsm->addScalarResult('course_id','course_id');
        $rsm->addScalarResult('id','id');
        $id = 0;
        $fp = fopen("extras/user_course.csv", "w");
        while(true)
        {
            $results = $em->createNativeQuery("SELECT id,user_identifier,course_id FROM user_courses_tracking WHERE id > $id LIMIT 10000", $rsm)->getResult();

            if(empty($results))
            {
                break;
            }
            foreach($results as $userCourse)
            {
                $line = array(
                    $userCourse['user_identifier'],
                    $userCourse['course_id'],
                );
                $id = $userCourse['id'];

                fputcsv($fp,$line);
            }

        }
        fclose($fp);
        // Drop the temporary table
        $conn->exec("DROP TABLE $tbl");

    }

    /**
     * Generate the csv with course id, name, subject
     */
    private function generateCoursesCSV()
    {
        $courses = $this->getContainer()->get('doctrine')->getManager()
                    ->getRepository('ClassCentralSiteBundle:Course')
                    ->findAll();


        $fp = fopen("extras/courses.csv", "w");
        foreach($courses as $course)
        {
            $line = array(
                $course->getId(),
                $course->getName()
            );

            fputcsv($fp,$line);
        }
        fclose($fp);

    }

    /**
     * Generate a csv with user_id,course_id, list_id(interested, currently doing)
     */
    private function generateUserCoursesCSV()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $conn = $em->getConnection();


        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('user_id','user_id');
        $rsm->addScalarResult('list_id','list_id');
        $rsm->addScalarResult('course_id','course_id');
        $results = $em->createNativeQuery('SELECT user_id,course_id,list_id FROM users_courses', $rsm)->getResult();


        $fp = fopen("extras/user_library.csv", "w");
        foreach($results as $userCourse)
        {
            $line = array(
                $userCourse['user_id'],
                $userCourse['course_id'],
                UserCourse::$lists[$userCourse['list_id']]['slug'],
            );

            fputcsv($fp,$line);
        }
        fclose($fp);

    }
} 