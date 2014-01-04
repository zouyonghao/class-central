<?php

namespace ClassCentral\SiteBundle\Services;

use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Entity\UserPreference;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class User {

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function signup(\ClassCentral\SiteBundle\Entity\User $user, $emailVerification = true)
    {
        $em = $this->container->get('doctrine')->getManager();
        $templating = $this->container->get('templating');
        $mailgun = $this->container->get('mailgun');
        $verifyTokenService = $this->container->get('verification_token');

        $user->setEmail(strtolower($user->getEmail())); // Normalize the email
        $password = $user->getPassword();
        $user->setPassword($user->getHashedPassword($password));

        // If the email has subscriptions to different newsletters, transfer it over to this user
        $emailEntity = $em->getRepository('ClassCentralSiteBundle:Email')->findOneByEmail($user->getEmail());
        if($emailEntity)
        {
            foreach($emailEntity->getNewsletters() as $newsletter)
            {
                $user->addNewsletter($newsletter);
            }
        }

        $em->persist($user);
        $em->flush();

        // Create user prefrences for the user
        $this->initPreferences($user);

        // Login the user
        $token = new UsernamePasswordToken($user, $password,'secured_area',$user->getRoles());
        $this->container->get('security.context')->setToken($token);

        // Send a welcome email but not in the test environment
        if ($this->container->getParameter('kernel.environment') != 'test')
        {
            $html = $templating->renderResponse('ClassCentralSiteBundle:Mail:welcome.html.twig')->getContent();
            $mailgunResponse = $mailgun->sendIntroEmail($user->getEmail(),"'Dhawal Shah'<dhawal@class-central.com>","Welcome to Class Central's MOOC Tracker",$html);

            if($emailVerification)
            {
               // Send an email for verification
                $value = array(
                    'verify' => 1,
                    'email' => $user->getEmail()
                );
                $tokenEntity = $verifyTokenService->create($value,\ClassCentral\SiteBundle\Entity\VerificationToken::EXPIRY_1_YEAR);
                $html = $templating->renderResponse('ClassCentralSiteBundle:Mail:confirm.email.html.twig',array('token' => $tokenEntity->getToken()))->getContent();
                $mailgunResponse = $mailgun->sendSimpleText($user->getEmail(),"no-reply@class-central.com","Please confirm your email",$html);
            }
        }

        return $user;
    }

    /**
     * Adds a course to the users interested list
     * @param \ClassCentral\SiteBundle\Entity\User $user
     * @param Course $course
     * @param $listId
     */
    public function addCourse(\ClassCentral\SiteBundle\Entity\User $user, Course $course, $listId)
    {
        $em = $this->container->get('doctrine')->getManager();
        // Check if the list id is valid
        if(!array_key_exists($listId,UserCourse::$lists))
        {
            throw new \Exception("List id $listId is not valid");
        }

        // Validate the course is not already added
        $userCourseId = $this->getUserCourseId($user,$course,$listId);
        if($userCourseId)
        {
            return false;
        }

        //Save it if it does not exist
        $uc = new UserCourse();
        $uc->setCourse($course);
        $uc->setUser($user);
        $uc->setListId($listId);

        // Add course to user
        $user->addUserCourse($uc);
        $em->persist($uc);
        $em->flush();

        return $uc;
    }

    /**
     * Given a list id and a course removes it from the users listings
     * @param \ClassCentral\SiteBundle\Entity\User $user
     * @param Course $course
     * @param $listId
     */
    public function removeCourse(\ClassCentral\SiteBundle\Entity\User $user, Course $course, $listId)
    {
        $em = $this->container->get('doctrine')->getManager();
        $userCourseId = $this->getUserCourseId($user,$course,$listId);
        if($userCourseId)
        {
            $uc = $em->find('ClassCentralSiteBundle:UserCourse', $userCourseId);
            $em->remove($uc);
            $em->flush();

            return true;
        }

        // Course was not added before
        return false;

    }

    /**
     * Retrives the
     * @param \ClassCentral\SiteBundle\Entity\User $user
     * @param Course $course
     * @param $listId
     */
    private function getUserCourseId(\ClassCentral\SiteBundle\Entity\User $user, Course $course, $listId)
    {
        $em = $this->container->get('doctrine')->getManager();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $query = $em->createNativeQuery("SELECT id FROM users_courses WHERE user_id = ? AND course_id = ? and list_id = ?",$rsm);
        $query->setParameter('1', $user->getId());
        $query->setParameter('2', $course->getId());
        $query->setParameter('3', $listId);
        $result = $query->getResult();

        if(empty($result))
        {
            return null;
        }
        else
        {
            return $result[0]["id"];
        }

    }

    /**
     * Updates or creates a iser preference if it does not exist
     * @param \ClassCentral\SiteBundle\Entity\User $user
     * @param $type
     * @param $value
     */
    public function updatePreference(\ClassCentral\SiteBundle\Entity\User $user, $type, $value)
    {
        $em = $this->container->get('doctrine')->getManager();

        if(!in_array($type, UserPreference::$validPrefs))
        {
            throw new \Exception("Preference $type is not a valid preference");
        }

        $prefMap = $user->getUserPreferencesByTypeMap();
        if(in_array($type,array_keys($prefMap)))
        {
            // Update the preferences
            $up = $prefMap[$type];
            $up->setValue($value);
            $em->persist($up);
        }
        else
        {
            // Create the preferences
            $up = new UserPreference();
            $up->setUser($user);
            $up->setType($type);
            $up->setValue($value);
            $em->persist($up);
        }

        $em->flush();

        return true;
    }

    /**
     * Initializes preferences for a particular user
     * @param \ClassCentral\SiteBundle\Entity\User $user
     * @param array $prefs
     */
    public function initPreferences(\ClassCentral\SiteBundle\Entity\User $user, $prefs = array())
    {
        $em = $this->container->get('doctrine')->getManager();

        // MOOC Tracker courses
        $upCourses = new UserPreference();
        $upCourses->setUser($user);
        $upCourses->setType(UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES);
        $value = 1;
        if(in_array(UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES, $prefs))
        {
            $value = $prefs[UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES];
        }
        $upCourses->setValue($value);
        $em->persist($upCourses);

        // MOOC Tracker search terms
        $upSearchTerms = new UserPreference();
        $upSearchTerms->setUser($user);
        $upSearchTerms->setType(UserPreference::USER_PREFERENCE_MOOC_TRACKER_SEARCH_TERM);
        $value = 1;
        if(in_array(UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES, $prefs))
        {
            $value = $prefs[UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES];
        }
        $upSearchTerms->setValue($value);
        $em->persist($upSearchTerms);

        $em->flush();

    }

} 