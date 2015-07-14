<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\CourseStatus;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Form\SignupType;
use ClassCentral\SiteBundle\Services\Kuber;
use ClassCentral\SiteBundle\Utility\Breadcrumb;
use ClassCentral\SiteBundle\Utility\ReviewUtility;
use ClassCentral\SiteBundle\Utility\UniversalHelper;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Form\CourseType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\HttpFoundation\Request;
/**
 * Course controller.
 *
 */
class CourseController extends Controller
{
    /**
     * Lists all Course entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:Course')->findAll();

        return $this->render('ClassCentralSiteBundle:Course:index.html.twig', array(
            'entities' => $entities
        ));
    }
    
    /**
     *  List all Course entities filtered by intiative
     */
    
    public function initiativeAction($initiative)
    {
        $em = $this->getDoctrine()->getManager();
        $initiative = $em->getRepository('ClassCentralSiteBundle:Initiative')->findOneByCode($initiative);
        
        $entities = $em->getRepository('ClassCentralSiteBundle:Course')->findByInitiative($initiative->getId());

        return $this->render('ClassCentralSiteBundle:Course:index.html.twig', array(
            'entities' => $entities
        ));
        
    }

    /**
     * Finds and displays a Course entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }
        
        $offerings = $em->getRepository('ClassCentralSiteBundle:Offering')->findByCourse($id);

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Course:show.html.twig', array(
            'entity'      => $entity,
            'offerings' => $offerings,
            'delete_form' => $deleteForm->createView(),

        ));
    }

    /**
     * Displays a form to create a new Course entity.
     *
     */
    public function newAction()
    {
        $ts = $this->get('tag'); // tag service
        $entity = new Course();
        $form   = $this->createForm(new CourseType(), $entity);

        return $this->render('ClassCentralSiteBundle:Course:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'tags' => $ts->getAllTags()
        ));
    }

    /**
     * Creates a new Course entity.
     *
     */
    public function createAction()
    {
        $ts = $this->get('tag'); // tag service
        $entity  = new Course();
        $request = $this->getRequest();
        $form    = $this->createForm(new CourseType(), $entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity->setLongDescription( $this->replaceHtmlTags( $entity->getLongDescription() ));
            $entity->setSyllabus( $this->replaceHtmlTags( $entity->getSyllabus()) );

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $courseTags =  explode(',', $request->request->get('course-tags'));
            $ts->saveCourseTags($entity,$courseTags);

            // Send it to Slack
            $coursePageUrl = $this->container->getParameter('baseurl'). $this->container->get('router')->generate('ClassCentralSiteBundle_mooc',
                    array('id' => $entity->getId(), 'slug' => $entity->getSlug() ));
            $message ="[New Course] *{$entity->getName()}*\n" .$coursePageUrl ;
            $user = $this->getUser();
            $this->container
                ->get('slack_client')
                ->to('#cc-activity-data')
                ->from( $user->getName() )
                ->withIcon( $this->get('user_service')->getProfilePic( $user->getId() ) )
                ->send( $message );

            return $this->redirect($this->generateUrl('course_show', array('id' => $entity->getId())));
        }

        return $this->render('ClassCentralSiteBundle:Course:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Replaces h1...h6 tags with headers <strong></strong>
     * @param $text
     */
    private function replaceHtmlTags($text)
    {
        $search = array();
        $replace = array();
        for($i = 1; $i <=6; $i++)
        {
            $search[] = "h$i";
            $replace[] = 'strong';

        }

       return str_replace( $search, $replace, $text, $count);
    }

    /**
     * Displays a form to edit an existing Course entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ts = $this->get('tag'); // tag service


        $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        // Get the tags
        $ct = array();
        foreach($entity->getTags() as $tag)
        {
            $ct[] = $tag->getName();
        }

        $editForm = $this->createForm(new CourseType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Course:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'tags' => $ts->getAllTags(),
            'course_tags' => implode(',',$ct)
        ));
    }

    /**
     * Edits an existing Course entity.
     *
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ts = $this->get('tag'); // tag service

        $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        $editForm   = $this->createForm(new CourseType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->handleRequest($request);

        if ($editForm->isValid())
        {
            $entity->setLongDescription( $this->replaceHtmlTags( $entity->getLongDescription() ));
            $entity->setSyllabus( $this->replaceHtmlTags( $entity->getSyllabus()) );
            $em->persist($entity);
            $em->flush();

            $courseTags =  explode(',', $request->request->get('course-tags'));
            $ts->saveCourseTags($entity,$courseTags);

            // invalidate the cache
            $this->get('cache')->deleteCache( 'course_'.$id );
            return $this->redirect($this->generateUrl('course_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:Course:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Course entity.
     *
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Course entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('course'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    /**
     *
     * @param $id Row id for the course
     * @param $slug descriptive url for the course
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function moocAction(Request $request,$id, $slug)
    {
        // Autologin if a token exists
        $this->get('user_service')->autoLogin($request);

       $em = $this->getDoctrine()->getManager();
       $rs = $this->get('review'); // Review service
       $cache = $this->get('Cache');

       $courseId = intval($id);
       $course = $cache->get( 'course_' . $courseId, array($this,'getCourseDetails'), array($courseId,$em) );
       if(!$course)
       {
           // TODO: render a error page
          return;
       }
       
       // Check if the course is a duplicate
       if( isset($course['duplicate']) )
       {
            // Exists - redirect to the original course
            return $this->redirect(
            $this->get('router')->generate('ClassCentralSiteBundle_mooc', array('id' => $course['duplicate']['id'],'slug' => $course['duplicate']['slug'])),
            301
            );
       }


       // If the slug is not the same, then redirect to the correct url

        if( $course['slug'] !== $slug)
        {
            $url = $this->container->getParameter('baseurl') . $this->get('router')->generate('ClassCentralSiteBundle_mooc', array('id' => $course['id'],'slug' => $course['slug']));
            return $this->redirect($url,301);
        }

        if ( $course['status'] == 100 )
        {
            throw new NotFoundHttpException("Course not found");
        }

        // Get query params
        $redirect = $request->query->get('direct');
        if( !empty($redirect) )
        {
            // Get the url of next session
            return $this->redirect( $course['nextOffering']['url'] );
        }


        // Save the course and user tracking for generating recommendations later on
       if($this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
       {
           $user = $this->get('security.context')->getToken()->getUser();
           $sessionId = $user->getId();
       }
       else
       {
           $session = $this->getRequest()->getSession();
           if(!$session->isStarted())
           {
               // Start the session if its not already started
               $session->start();
           }
           $sessionId = $session->getId();
       }
       $em->getConnection()->executeUpdate("INSERT INTO user_courses_tracking(user_identifier,course_id)
                                VALUES ('$sessionId', $courseId)");

       // Recently viewed
       $userSession = $this->get('user_session');
       $recentlyViewedCourseIds = $userSession->getRecentlyViewed();
       $recentlyViewedCourses = array();
       if(!empty($recentlyViewedCourseIds))
       {
           foreach($recentlyViewedCourseIds as $id)
           {
               $recentlyViewedCourses[] = $this->get('Cache')->get( 'course_' . $id, array($this,'getCourseDetails'), array($id,$em) );
           }
       }
       $userSession->saveRecentlyViewed($courseId);

       // URL of the current page
       $course['pageUrl'] = $this->container->getParameter('baseurl') . $this->get('router')->generate('ClassCentralSiteBundle_mooc', array('id' => $course['id'],'slug' => $course['slug']));

       // Page Title/Twitter card title
        $titlePrefix = '';
        if(!empty($course['initiative']['name']))
        {
            $titlePrefix = ' from ' . $course['initiative']['name'];
        }
        $course['pageTitle'] = $course['name'] . $titlePrefix;

        // Figure out if there is course in the future.
        $nextSession = null;
        $nextSessionStart ='';
        if(count($course['offerings']['upcoming']) > 0)
        {
            $nextSession = $course['offerings']['upcoming'][0];
            $nextSessionStart = $nextSession['displayDate'];
        }

       // Get reviews and ratings
        $rating = $rs->getRatings($courseId);
        $reviews = $rs->getReviews($courseId);

        // Breadcrumbs
        $breadcrumbs = array();
        if(!empty($course['initiative']['name']))
        {
            $breadcrumbs[] = Breadcrumb::getBreadCrumb(
                $course['initiative']['name'],
                $this->generateUrl('ClassCentralSiteBundle_initiative',array('type' => $course['initiative']['code'] ))
            );
        }
        else
        {
            $breadcrumbs[] = Breadcrumb::getBreadCrumb(
                'Others',
                $this->generateUrl('ClassCentralSiteBundle_initiative',array('type' => 'others'))
            );
        }

        $breadcrumbs[] = Breadcrumb::getBreadCrumb(
            $course['name']
        );


        $recommendations = $this->get('Cache')->get('course_recommendation_'. $courseId, array($this,'getCourseRecommendations'), array($courseId));
        $interestedUsers = $em->getRepository('ClassCentralSiteBundle:Course')->getInterestedUsers( $courseId );

        return $this->render(
           'ClassCentralSiteBundle:Course:mooc.html.twig',
           array('page' => 'course',
                 'course'=>$course,
                 'offeringTypes' => Offering::$types,
                 'offeringTypesOrder' => array('upcoming','ongoing','selfpaced','past'),
                 'nextSession' => $nextSession,
                 'nextSessionStart' => $nextSessionStart,
                 'recentlyViewedCourses' => $recentlyViewedCourses,
                 'listTypes' => UserCourse::$lists,
                 'rating' => $rating,
                 'reviews' => $reviews,
                 'breadcrumbs' => $breadcrumbs,
                 'recommendations' => $recommendations,
                 'providersWithLogos' => Course::$providersWithFavicons,
                 'isYoutube' => $this->isYouTubeVideo( $course['videoIntro'] ),
                 'courseImage' => $this->getCourseImage( $courseId),
                 'ratingStars' => ReviewUtility::getRatingStars( $rating ),
                 'interestedUsers' => $interestedUsers,
       ));
    }

    /**
     * Checks whether if the video is a youtube video
     */
    private function isYouTubeVideo( $videoUrl )
    {
        return !empty($videoUrl) && strpos( $videoUrl, 'youtube');
    }

    public function getCourseRecommendations($courseId)
    {
        $recommendations = array();
        $em = $this->getDoctrine()->getManager();

        // Get the course recommendations
        $recs = $em->getRepository('ClassCentralSiteBundle:CourseRecommendation')->findBy(array(
            'course' => $em->getRepository('ClassCentralSiteBundle:Course')->find($courseId)
        ));

        if( !empty($recs) )
        {
            $count = 0;
            foreach($recs as $rec)
            {
                $recCourse = $rec->getRecommendedCourse();
                if($recCourse->getStatus() < CourseStatus::COURSE_NOT_SHOWN_LOWER_BOUND)
                {
                    $recommendations[] = $this->get('Cache')->get( 'course_' . $recCourse->getId(), array($this,'getCourseDetails'), array($recCourse->getId(),$em) );
                    $count++;
                }

                if($count == 5)
                {
                    break; // Show top recommendations
                }
            }
        }

        return $recommendations;
    }

    public function shareAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $mailgun = $this->get('mailgun');
        $validator = $this->get('validator');
        $request = $this->getRequest();

        // Get the request params
        $to = $request->request->get('to');
        $name = $request->request->get('name');
        $from = $request->request->get('from');

        $courseId = intval($id);
        $course = $this->get('Cache')->get( 'course_' . $courseId, array($this,'getCourseDetails'), array($courseId,$em) );

        $errors = array();
        if(!$course)
        {
            $errors[] = 'Course does not exist';
        }

        // Check if $from, $to fields are valid
        $emailConstraint = new Email();
        $emailConstraint->message = 'Invalid email address';
        $toErrorList = $validator->validateValue($to,$emailConstraint);
        $fromErrorList = $validator->validateValue($from,$emailConstraint);
        if(count($toErrorList) != 0 )
        {
            $errors[] = 'Invalid TO email address';
        }
        if(count($fromErrorList) != 0) {
            $errors[] = 'Invalid FROM email address';
        }

        if(empty($name))
        {
            $errors[] = 'Name is a required field';
        }

        if(!empty($errors))
        {
            $response = new Response(json_encode(array('errors' => $errors,'success'=>false)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $subject = $course['name'];
        if(!empty($course['initiative']) && !empty($course['initiative']['name']))
        {
            $subject = $course['initiative']['name'] . ' - ' .  $course['name'];
        }

        $mailgunResponse = $mailgun->sendSimpleText($to,"{$name}<{$from}>", $subject,$this->formatCourseEmailMessage($course,$name));
        $mailgunResponseArray = json_decode($mailgunResponse,true);

        $responseArray = array();
        if(!isset($mailgunResponse['id']))
        {
           $responseArray['errors'][] = "Some error occurred. Please try again";
           $responseArray['success'] = false;
        } else {
            $responseArray['success'] = true;
        }

        $response = new Response(json_encode($responseArray));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


    private function formatCourseEmailMessage($course, $name)
    {
        $url = $this->container->getParameter('baseurl') . $this->get('router')->generate('ClassCentralSiteBundle_mooc', array('id' => $course['id'],'slug' => $course['slug']));

        $text = <<< EOD
$name shared this free online course/MOOC "{$course['name']}" with you via Class Central.

COURSE DESCRIPTION:
{$course['desc']}


EOD;
        if(count($course['offerings']['upcoming']) > 0)
        {
            $nextSession = $course['offerings']['upcoming'][0];
            $nextSessionStart = $nextSession['displayDate'];
            $text = $text . 'Next session start date: '. $nextSession['displayDate'];

        }

        $text = <<< EOD
$text

Find more details about the course at $url

---
For a complete list of courses please visit Class Central at https://www.class-central.com.
EOD;

        return $text;
    }

     /**
     * Retrieves the course details and offerings
     * @param $courseId
     */
    public function getCourseDetails($courseId, $em)
    {
        // Get the course first
        $courseEntity = $em->getRepository('ClassCentralSiteBundle:Course')->findOneById($courseId);
        if(!$courseEntity)
        {
            // Invalid course
            return null;
        }
        $courseDetails = $em->getRepository('ClassCentralSiteBundle:Course')->getCourseArray($courseEntity);
        // Course exists get all the offerings
        $courseDetails['offerings'] = $em->getRepository('ClassCentralSiteBundle:Offering')->findAllByCourseIds(array($courseId));

        // Flip the past courses to show the newest ones first
        // TODO: Sort these courses correctly
        foreach($courseDetails['offerings'] as $type => $courses)
        {
            $courseDetails['offerings'][$type] =  array_reverse($courses);
        }

        // set the interview image
        if( !empty( $courseDetails['interview'] ))
        {
            $courseDetails['interview']['image'] =
                $this->get('image_service')->getInterviewImage( $courseDetails['interview']['instructorPhoto'], $courseDetails['interview']['id'] );
        }

        return $courseDetails;
    }

    /**
     * Shows a list of courses that need to be reviewed
     * @param Request $request
     */
    public function reviewAction()
    {
        $em = $this->getDoctrine()->getManager();
        $courses = $em->getRepository('ClassCentralSiteBundle:Course')->findByStatus(CourseStatus::TO_BE_REVIEWED);
        return $this->render('ClassCentralSiteBundle:Course:review.html.twig', array(
                'courses' => $courses
        ));
    }


    public function bulkEditAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $postFields = $request->request->all();
        if(isset($postFields["subject"]))
        {
            // Form has been posted. Update the subject
            $subject = $em->getRepository('ClassCentralSiteBundle:Stream')->findOneBy(array('id' => $postFields['subject']));
            if($subject)
            {
                // Update all the courses
                foreach($postFields['courses'] as $courseId)
                {
                    $course = $em->getRepository('ClassCentralSiteBundle:Course')->findOneBy(array('id'=>$courseId));
                    if($course)
                    {
                        $course->setStream($subject);
                        $em->persist($course);
                    }
                }

                $em->flush();
            }
        }



        $entities = $em->getRepository('ClassCentralSiteBundle:Course')->findAll();
        $subjects = $em->getRepository('ClassCentralSiteBundle:Stream')->findAll();

        return $this->render('ClassCentralSiteBundle:Course:bulkEdit.html.twig', array(
                'courses' => $entities,
                'subjects' => $subjects
            ));
    }

    /**
     * A button on the course page to quickly approve
     * @param Request $request
     * @param $id
     */
    public function quickApproveAction(Request $request, $courseId)
    {
        $em = $this->getDoctrine()->getManager();
        $course = $em->getRepository('ClassCentralSiteBundle:Course')->find( $courseId );
        if(!$course)
        {
           return;
        }

        $course->setStatus(CourseStatus::AVAILABLE);
        $em->persist( $course );
        $em->flush( $course );


        // invalidate the cache
        $this->get('cache')->deleteCache( 'course_'.$courseId );

        return $this->redirect($this->generateUrl( 'ClassCentralSiteBundle_mooc', array( 'id' => $course->getId(), 'slug' => $course->getSlug() ) ) );
    }


    /**
     * Shows a page with the top 10 courses.
     * @param Request $request
     * @param $year
     * @param $month -> march, april, etc
     */
    public function top10Action(Request $request, $month, $year)
    {
        return $this->render("ClassCentralSiteBundle:Course:top10/{$month}{$year}.html.twig", array(
            'month' => $month,
            'year'  => $year,
            'page' => 'top10'
        ));
    }

    public function moocReportAction(Request $request, $month, $year)
    {
        return $this->render("ClassCentralSiteBundle:Course:moocReport/{$month}{$year}.html.twig", array(
            'month' => $month,
            'year'  => $year,
            'count' => 99,
            'page' => 'mooc-report'
        ));
    }
    
    public function tagAction(Request $request, $tag)
    {
        $cl = $this->get('course_listing');
        $data = $cl->byTag($tag,$request);

        return $this->render('ClassCentralSiteBundle:Course:tag.html.twig',
            array(
                'page'=>'tag',
                'results' => $data['courses'],
                'listTypes' => UserCourse::$lists,
                'allSubjects' => $data['allSubjects'],
                'allLanguages' => $data['allLanguages'],
                'allSessions' => $data['allSessions'],
                'tag' => $tag,
                'tagDisplayName' => ucfirst(strtolower( $tag) ),
                'sortField' => $data['sortField'],
                'sortClass' => $data['sortClass'],
                'pageNo' => $data['pageNo'],
                'showHeader' => true
            ));
    }

    /**
     * Random
     * @param Request $request
     */
    public function randomAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $query =$em->createQueryBuilder();

        $query
            ->add('select', 'MAX(c.id)')
            ->add('from','ClassCentralSiteBundle:Course c')
            ;
        $max = $query->getQuery()->getSingleScalarResult();
        $id = rand(300, $max);
        $course = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

        if( $course && $course->getStatus() == CourseStatus::AVAILABLE)
        {
            return $this->redirect($this->generateUrl('ClassCentralSiteBundle_mooc', array(
                'id' => $id,
                'slug' => $course->getSlug()
            )));
        }
        else
        {
            return $this->randomAction($request);
        }
    }

    /**
     * Shows courses for which mooc tracker notifications are being sent
     * @param Request $request
     * @param $type - 2weeks/1day
     * @param $dt
     */
    public function moocTrackerCoursesAction(Request $request, $type, $date)
    {
        $esCourses = $this->container->get('es_courses');

        $dateParts = explode('-', $date);
        if( !checkdate( $dateParts[1], $dateParts[2], $dateParts[0] ) )
        {
            return null; // Invalid date
        }

        $dt = new \DateTime( $date );
        if($type == '2weeks')
        {
            // Find courses starting 2 weeks (14 days after the current date)
            $dt->add( new \DateInterval('P14D') );
        }
        else
        {
            // Find courses starting 1 day later
            //$dt->add( new \DateInterval('P1D') );
        }

        $response = $esCourses->findByNextSessionStartDate($dt, $dt);
        $filter =$this->get('filter');
        $allSubjects = $filter->getCourseSubjects( $response['subjectIds'] );
        $allLanguages = $filter->getCourseLanguages( $response['languageIds'] );
        $allSessions  = $filter->getCourseSessions( $response['sessions'] );

        return $this->render('ClassCentralSiteBundle:Course:mtcourses.html.twig',array(
            'results' => $response['results'],
            'listTypes' => UserCourse::$lists,
            'allSubjects' => $allSubjects,
            'allLanguages' => $allLanguages,
            'allSessions' => $allSessions ,
            'page' => 'moocTrackerCourses',
        ));
    }

    /**
     * Renders the merge course form
     * @param Request $request
     */
    public function mergeCoursesFormAction(Request $request)
    {
        return $this->render('ClassCentralSiteBundle:Course:mergecourses.html.twig',array());
    }

    public function mergeCoursesAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $userService = $this->get('user_service');
        $type = $request->request->get('type');

        $orig = $em->getRepository('ClassCentralSiteBundle:Course')->find( $request->request->get('orig') );
        $dup = $em->getRepository('ClassCentralSiteBundle:Course')->find( $request->request->get('dup') );

        if( !$orig || !$dup)
        {
            echo "Invalid course"; exit();
        }

        // get all the UserCourses for the duplicate course
        $userCourses = $em->getRepository('ClassCentralSiteBundle:UserCourse')->findBy( array('course'=> $dup) );
        foreach($userCourses as $uc)
        {
             $userService->addCourse($uc->getUser(), $orig, $uc->getListId() );
             $userService->removeCourse($uc->getUser(), $dup, $uc->getListId() );

        }
        
        // Merge the reviews too
        foreach( $dup->getReviews() as $review )
        {
            $review->setCourse( $orig );
            $em->persist( $review );
        }

        // Move the offerings
        if($type == 2)
        {
            foreach($dup->getOfferings() as $o )
            {
                $o->setCourse( $orig );
                $em->persist( $o );
            }
        }
        
        // Rename the duplicate course
        $dup->setName( '***DUPLICATE*** ' . $dup->getName()  );
        
        $dup->setDuplicateCourse( $orig );
        $dup->setStatus( CourseStatus::NOT_AVAILABLE );
        $em->persist( $dup );
        $em->flush();

        echo "Completed";
        return;

    }

    public function interestedAction(Request $request, $id, $slug)
    {
        $em = $this->getDoctrine()->getManager();
        $rs = $this->get('review'); // Review service
        $cache = $this->get('Cache');

        $courseId = intval($id);
        $course = $cache->get( 'course_' . $courseId, array($this,'getCourseDetails'), array($courseId,$em) );
        if(!$course)
        {
            // TODO: render a error page
            return;
        }


        // If the slug is not the same, then redirect to the correct url

        if( $course['slug'] !== $slug)
        {
            $url =  $this->get('router')->generate('mooc_interested', array('id' => $course['id'],'slug' => $course['slug']));
            return $this->redirect($url,301);
        }

        if ( $course['status'] == 100 )
        {
            throw new NotFoundHttpException("Course not found");
        }

        // Get the list of interested users
        $users = $em->getRepository('ClassCentralSiteBundle:Course')->getInterestedUsers( $id );

        // Breadcrumbs
        // Breadcrumbs
        $breadcrumbs = array();
        if(!empty($course['initiative']['name']))
        {
            $breadcrumbs[] = Breadcrumb::getBreadCrumb(
                $course['initiative']['name'],
                $this->generateUrl('ClassCentralSiteBundle_initiative',array('type' => $course['initiative']['code'] ))
            );
        }
        else
        {
            $breadcrumbs[] = Breadcrumb::getBreadCrumb(
                'Others',
                $this->generateUrl('ClassCentralSiteBundle_initiative',array('type' => 'others'))
            );
        }

        $breadcrumbs[] = Breadcrumb::getBreadCrumb(
            $course['name'],
            $this->generateUrl('ClassCentralSiteBundle_mooc',array('id' => $id, 'slug'=>$slug ))
        );
        $breadcrumbs[] = Breadcrumb::getBreadCrumb(
            'Interested Users'
        );



        return $this->render(
            'ClassCentralSiteBundle:Course:interested.html.twig', array(
                'course' => $course,
                'users' => $users,
                'breadcrumbs' => $breadcrumbs
            )
        );
    }

    /**
     * Returns a json object containing the course details. Originally designed for
     * wordpres short code
     * @param Request $request
     * @param $courseId
     */
    public function courseDetailsAction(Request $request, $courseId)
    {
        $em = $this->getDoctrine()->getManager();
        $cache = $this->get('Cache');

        $courseId = intval($courseId);
        $course = $cache->get( 'course_' . $courseId, array($this,'getCourseDetails'), array($courseId,$em) );

        if(!$course ||   $course['status'] == 100 )
        {
            return UniversalHelper::getAjaxResponse( false );
        }

        $data = array(
            'name' => $course['name'],
            'displayDate' => $course['nextOffering']['displayDate'],
            'url' => $course['nextOffering']['url'],

        );

        return UniversalHelper::getAjaxResponse( true, $data );
    }

    private function getCourseImage( $courseId )
    {
        $cache =$this->container->get('cache');

        $url = $cache->get( 'course_image_'. $courseId,function( $cid ){
            $kuber = $this->container->get('kuber');
            $url = $kuber->getUrl( Kuber::KUBER_ENTITY_COURSE ,Kuber::KUBER_TYPE_COURSE_IMAGE, $cid );
            return $url;
        }, array($courseId));

        return $url;
    }

    /**
     * Shows trending courses
     * @param Request $request
     */
    public function trendingAction( Request $request )
    {

        $cache = $this->get('Cache');

        // Get Top 10 courses based on recent reviews.
        $data = $cache->get('trending_courses', function(){
            $cl = $this->get('course_listing');
            return $cl->trending();
        });

        return $this->render('ClassCentralSiteBundle:Course:trending.html.twig',
            array(
                'offeringType' => 'recent',
                'page'=>'courses',
                'results' => $data['courses'],
                'listTypes' => UserCourse::$lists,
                'allSubjects' => array(),
                'allLanguages' => array(),
                'offeringTypes' => Offering::$types,
                'showHeader' => true
            ));
    }

}
