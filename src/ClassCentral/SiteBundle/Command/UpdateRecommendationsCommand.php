<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 2/24/14
 * Time: 4:13 PM
 */

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Entity\CourseRecommendation;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates the recommendations tables
 * Class UpdateRecommendationsCommand
 * @package ClassCentral\SiteBundle\Command
 */
class UpdateRecommendationsCommand extends ContainerAwareCommand {

    private $courses = array();
    const RECOMMENDATIONS_SCORE = 0.5;

    protected function configure()
    {
        $this
            ->setName('classcentral:recommender:update')
            ->setDescription('Updates the recommendations from extras/recommendations.csv');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')
            ->getManager();

       // Truncate the recommendations table
        $conn = $em->getConnection()
                ->exec("TRUNCATE courses_recommendations");

        // Build a course map
        $this->buildCoursesMap();

        if (($handle = fopen("extras/recommendations.csv", "r")) !== FALSE) {
            fgetcsv($handle, 1000, ","); // Skip the first row
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
                if($num != 5)
                {
                    // No course recommendations found
                    continue;
                }

                $course_id = $data[1];
                $recommendations = explode(',', $data[3]);
                $recommendations_score = explode(',', $data[2]);

                $this->updateRecommendations($course_id,$recommendations,$recommendations_score);
                //echo "$course_id ||| " . print_r($recommendations, true) . " ||| " . print_r($recommendations_score, true) . "\n";
            }
            fclose($handle);
        }

        $em->flush();
    }

    private function updateRecommendations($courseId, array $recommendations, array $score)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        // Get the course
        $course = $this->courses[$courseId];

        for($i = 0; $i < count($recommendations); $i++)
        {
            $rc = $this->courses[trim($recommendations[$i])]; // Recommended course
            $rcScore = trim($score[$i]);

            // Ignore courses with less than a score of 15. Since the recommendations
            // are in order, any other score after this would be also less than 15
            if($rcScore < self::RECOMMENDATIONS_SCORE)
            {
                break;
            }

            $r = new CourseRecommendation();
            $r->setCourse($course);
            $r->setRecommendedCourse($rc);
            $r->setPosition($i+1); // So as to start with 1,2,3

            $em->persist($r);
        }

        return;

    }

    private function buildCoursesMap()
    {
        $allCourses = $this->getContainer()->get('doctrine')
            ->getManager()
            ->getRepository('ClassCentralSiteBundle:Course')
            ->findAll();

        foreach($allCourses as $course)
        {
            $this->courses[$course->getId()] = $course;
        }
    }
} 