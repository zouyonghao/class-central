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
        // Get a list of all courses that are starting in the next 2 weeks.

        $start = new \DateTime();
        $start->sub(new \DateInterval('P1D'));
        $end = new \DateTime();
        $end->add(new \DateInterval('P14D'));

        $results = $esCourses->findByNextSessionStartDate($start, $end);
        $output->writeln( $results['results']['hits']['total'] . ' courses found');

        $courseIds = array();
        foreach($results['results']['hits']['hits'] as $course)
        {
            $courseIds[] = $course['_id'];
        }

        // Get a list of all users and build a user to courses map
        $qb = $em->createQueryBuilder();
        $qb->add('select', 'uc as usercourse, c.id as cid,u.id as uid')
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
            $uid = $r['uid'];
            $cid = $r['cid'];
            $listId = $r['usercourse']['listId'];

            if( !isset($users[$uid]))
            {
                $users[$uid] = array();
            }

            $users[$uid][$listId][] = $cid;
        }
    }

} 