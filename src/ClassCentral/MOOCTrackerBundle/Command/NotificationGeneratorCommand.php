<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/16/14
 * Time: 2:47 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NotificationGeneratorCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('mooctracker:notification:generate');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $esCourses = $this->getContainer()->get('es_courses');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $scheduler = $this->getContainer()->get('scheduler');

        // Get a list of all courses that are starting in the next 2 weeks.

        $start = new \DateTime();
        $start->sub(new \DateInterval('P1D'));
        $end = new \DateTime();
        $end->add(new \DateInterval('P15D'));

        $results = $esCourses->findByNextSessionStartDate($end, $end);
        $output->writeln( $results['results']['hits']['total'] . ' courses found');

        $courseIds = array();
        foreach($results['results']['hits']['hits'] as $course)
        {
            $courseIds[] = $course['_id'];
        }

        // Get a list of all users and build a user to courses map
        $qb = $em->createQueryBuilder();
        $qb->add('select', 'uc as usercourse, c.id as cid,u.id as uid, u.isverified as verified')
             ->add('from', 'ClassCentralSiteBundle:UserCourse uc')
             ->join('uc.course', 'c')
            ->join('uc.user', 'u')
            ->andWhere(    'uc.course IN (:ids)')
             ->setParameter('ids',$courseIds);
        ;
        $results = $qb->getQuery()->getArrayResult();
        $users = array();
        foreach($results as $r)
        {
            $verified = $r['verified'];
            if(!$verified)
            {
                // Don't send email to verified users
                continue;
            }
            $uid = $r['uid'];
            $cid = $r['cid'];
            $listId = $r['usercourse']['listId'];

            if( !isset($users[$uid]))
            {
                $users[$uid] = array();
            }

            $users[$uid][$listId][] = $cid;
        }

        $output->writeln( count($users) . ' users found');
        $scheduled  = 0;
        foreach($users as $uid => $courses)
        {
            $id = $scheduler->schedule(
                new \DateTime(),
                'email',
                'ClassCentral\MOOCTrackerBundle\Job\CourseStartReminderJob',
                $courses,
                $uid
            );

            if($id){
                $scheduled++;
            }
        }
        $output->writeln( $scheduled . ' jobs scheduled');
    }

} 