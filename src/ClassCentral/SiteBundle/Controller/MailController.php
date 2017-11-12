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
use ClassCentral\MOOCTrackerBundle\Job\NewUserFollowUpJob;
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
        $availableTypes = [
          'welcome-email' => 'Welcome Email',
          'new-user-followup' => 'New User Follow Up',
          'vote-best-courses-2017' => "Vote Best Courses 2017",
        ];

        $html = '<b>Template not found</b>';
        $user = $this->getUser();

        switch ($type) {
            case 'vote-best-courses-2017':
              $announcementEmailJob = new AnnouncementEmailJob();
              $announcementEmailJob->setContainer( $this->container );
              $announcementEmailESJob = new ESJob(0);
              $announcementEmailESJob->setJobType(AnnouncementEmailJob::ANNOUNCEMENT_EMAIL_JOB_TYPE);
              $announcementEmailJob->setJob($announcementEmailESJob);
              $html = $announcementEmailJob->getAnnouncementHTML($user,'vote_best_courses_2017.html.twig','vote_best_courses_2017');
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
