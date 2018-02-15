<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 9/11/17
 * Time: 5:23 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\ElasticSearchBundle\Scheduler\ESJob;
use ClassCentral\MOOCTrackerBundle\Job\AnnouncementEmailJob;
use ClassCentral\MOOCTrackerBundle\Job\CourseStartReminderJob;
use ClassCentral\MOOCTrackerBundle\Job\NewCoursesEmailJob;
use ClassCentral\MOOCTrackerBundle\Job\NewUserFollowUpJob;
use ClassCentral\MOOCTrackerBundle\Job\RecommendationEmailJob;
use ClassCentral\SiteBundle\Utility\ReviewUtility;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MailController extends Controller
{

    /**
     * Generates an email preview in the browser
     * @param Request $request
     * @param $emailType
     */
    public function previewAction(Request $request, $type = null)
    {
        $templating = $this->get('templating');
        $finder= $this->container->get('course_finder');

        $availableTypes = [
          'welcome-email' => 'Welcome Email',
          'new-user-followup' => 'New User Follow Up',
          'coursera-free-courses-2018' => "Coursera Courses Completely Free",
          'newsletter' => "Monthly MOOC Report",
          'recommendation-email' => 'Recommendation Email',
          'new-courses-email' => 'New Courses Email',
          'course-reminder-email-single-course' => 'Course Reminder Email - Single Course',
          'course-reminder-email-multiple-courses' => 'Course Reminder Email - Multiple Courses',
          'announcement-email' => 'Announcement email (use the template query param to switch templates)'
        ];

        $html = '<b>Template not found</b>';
        $user = $this->getUser();
        if(!empty($request->query->get('user-id')))
        {
            $userId = $request->query->get('user-id');
            $user = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:User')->find($userId);
        }

        switch ($type) {
            case 'announcement-email':
                  $announcementEmailJob = new AnnouncementEmailJob();
                  $announcementEmailJob->setContainer( $this->container );
                  $announcementEmailESJob = new ESJob(0);
                  $announcementEmailESJob->setJobType(AnnouncementEmailJob::ANNOUNCEMENT_EMAIL_JOB_TYPE);
                  $announcementEmailJob->setJob($announcementEmailESJob);
                  $template = ($request->query->get('template')) ? $request->query->get('template'): 'vote_best_courses_2017';
                  $html = $announcementEmailJob->getAnnouncementHTML($user,$template.".html.twig",$template);
                  break;
            case 'coursera-free-courses-2018':
                $announcementEmailJob = new AnnouncementEmailJob();
                $announcementEmailJob->setContainer( $this->container );
                $announcementEmailESJob = new ESJob(0);
                $announcementEmailESJob->setJobType(AnnouncementEmailJob::ANNOUNCEMENT_EMAIL_JOB_TYPE);
                $announcementEmailJob->setJob($announcementEmailESJob);
                $html = $announcementEmailJob->getAnnouncementHTML($user,'coursera_free_courses_2018.html.twig','coursera_free_courses_2018');
                break;
            case 'welcome-email':
                $userService = $this->get('user_service');
                $html = $userService->getWelcomeEmailHtml($user);
                break;
            case 'new-user-followup':
                $newUserJob = new NewUserFollowUpJob();
                $newUserJob->setContainer($this->container);
                $newUserESJob = new ESJob(0);
                $newUserESJob->setJobType(NewUserFollowUpJob::NEW_USER_FOLLOW_UP_JOB_TYPE);
                $newUserJob->setJob($newUserESJob);
                $html = $newUserJob->getFollowUpEmail($user);
                break;
            case 'newsletter':
                $month = ($request->query->get('month')) ? $request->query->get('month') : '_template';
                $html = $templating->renderResponse(sprintf('ClassCentralSiteBundle:Mail:%s/%s.html.twig','mooc-report',$month), array(
                  "user" => $user,
                  "baseUrl" => $this->container->getParameter('baseurl'),
                ));
                $html = $html->getContent();
                break;
            case 'recommendation-email':
                $recommendationEmailJob = new RecommendationEmailJob();
                $recommendationEmailJob->setContainer($this->container);
                $recommendationEmailESJob = new ESJob(0);
                $recommendationEmailESJob->setJobType(RecommendationEmailJob::RECOMMENDATION_EMAIL_JOB_TYPE);
                $recommendationEmailJob->setJob($recommendationEmailESJob);
                $courses =  $finder->byCourseIds([2161,3768,981,835,3314,442]);
                $html = $recommendationEmailJob->getHTML($user, $courses,'follow_course_recommendations', new \DateTime());
                break;
            case 'new-courses-email':
                $newCoursesEmailJob = new NewCoursesEmailJob();
                $newCoursesEmailJob->setContainer($this->container);
                $newCoursesEmailESJob = new ESJob(0);
                $newCoursesEmailESJob->setJobType(NewCoursesEmailJob::NEW_COURSES_EMAIL_JOB_TYPE);
                $newCoursesEmailJob->setJob($newCoursesEmailESJob);
                $courses =  $finder->byCourseIds([2161,3768,981,835,3314,442]);
                $html = $newCoursesEmailJob->getHTML($user, $courses,'follow_new_courses_notification', new \DateTime());
                break;
            case 'course-reminder-email-single-course':
                $courseReminderJob = new CourseStartReminderJob();
                $courseReminderJob->setContainer($this->container);
                $courseReminderESJob = new ESJob(0);
                $courseReminderESJob->setJobType(CourseStartReminderJob::JOB_TYPE_2_WEEKS_BEFORE);
                $courseReminderJob->setJob($courseReminderESJob);
                $course = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Course')->find( 2161 );
                $html = $courseReminderJob->getSingleCourseEmail($course, 1, $user, CourseStartReminderJob::JOB_TYPE_2_WEEKS_BEFORE, $courseReminderJob->getCounts());
                break;
            case 'course-reminder-email-multiple-courses':
                $courseReminderJob = new CourseStartReminderJob();
                $courseReminderJob->setContainer($this->container);
                $courseReminderESJob = new ESJob(0);
                $courseReminderESJob->setJobType(CourseStartReminderJob::JOB_TYPE_2_WEEKS_BEFORE);
                $courseReminderJob->setJob($courseReminderESJob);
                $courses = [];
                $rs = $this->container->get('review');

                foreach( [2161,3768,981,835,3314,442] as $courseId)
                {
                    $course =  $this->getDoctrine()->getManager()->getRepository('ClassCentralSiteBundle:Course')->find( $courseId );

                    // Get the review details
                    $courseArray = $this->getDoctrine()->getManager()->getRepository('ClassCentralSiteBundle:Course')->getCourseArray( $course );
                    $courseArray['rating'] = $rs->getRatings($course->getId());
                    $courseArray['ratingStars'] = ReviewUtility::getRatingStars( $courseArray['rating'] );
                    $rArray = $rs->getReviewsArray($course->getId());
                    $courseArray['reviewsCount'] = $rArray['count'];

                    $courses[] = array(
                        'interested' => true,
                        'id' => $courseId,
                        'course' => $courseArray
                    );
                }

                $html = $courseReminderJob-> getMultipleCouresEmail( $courses,$user,  $courseReminderJob->getCounts() );
                break;
            default:
                $html = "<b> Here all the available emails for previews </b>";
                foreach ($availableTypes as $availableType => $description)
                {
                    $url = $this->get('router')->generate('mail_preview',['type' => $availableType]);
                    $html .= "<li><a href='{$url}'>{$description}</a></li>";
                }
                break;
        }

        return new Response($html);
    }
}
