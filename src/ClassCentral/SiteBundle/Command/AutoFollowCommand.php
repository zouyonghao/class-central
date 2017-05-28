<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/27/17
 * Time: 1:06 AM
 */

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Entity\Item;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoFollowCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('classcentral:follows:auto');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        extract($this->buildFrequencyDistribution());
        $output->writeln(count($fd));


        // Generate follows
        $usersCount = 0;
        $userCourses = $this->getUsersWithUserCourses();
        $start = microtime(true);
        $usersFollowsBasedOnUsersCourses = array();
        $bulkFollows = array();
        foreach ($userCourses as $uc)
        {
            $course = $em->getRepository('ClassCentralSiteBundle:Course')->find($uc['cid']);
            $usersFollowsBasedOnUsersCourses[$uc['uid']][Item::ITEM_TYPE_SUBJECT][ $course->getStream()->getId()] =1;
            foreach ($course->getSubjects() as $subject)
            {
                $usersFollowsBasedOnUsersCourses[$uc['uid']][Item::ITEM_TYPE_SUBJECT][$subject->getId()] = 1;
            }

            $usersCount++;
            if($usersCount%100000 == 0)
            {
                $output->writeln("Users Processed : " . $usersCount);
                $time_elapsed_secs = microtime(true) - $start;
                echo "Took $time_elapsed_secs seconds \n";
                $start = microtime(true);
            }
        }

        foreach ($usersFollowsBasedOnUsersCourses as $userId => $userFollows)
        {
            if(count($userFollows,COUNT_RECURSIVE) < 5)
            {
                // Generate a few random follows
                $numFollowsPerUser = mt_rand(3, 8);
                while($numFollowsPerUser)
                {
                    $randNumb = mt_rand(0, count($fd) - 1);
                    $item = $fd[$randNumb];
                    if(isset($userFollows[$item->getType()][$item->getId()]))
                    {
                        continue;
                    }
                    $userFollows[$item->getType()][$item->getId()] = 1;
                    $numFollowsPerUser--;
                }

            }

            foreach ($userFollows as $itemType => $itemIds)
            {
                foreach ($itemIds as $itemId => $staticValue)
                {
                    if(empty($follows[$itemType][$itemId]))
                    {
                        $follows[$itemType][$itemId] = 0;
                    }
                    $follows[$itemType][$itemId]++;
                    $bulkFollows[] = $this->getBulkInsterFollowRow($userId, Item::getItem($itemType,$itemId));
                }
            }
        }

        $output->writeln(count($userCourses));
        $output->writeln(count($usersFollowsBasedOnUsersCourses));
        $output->writeln(count($usersFollowsBasedOnUsersCourses,COUNT_RECURSIVE));

        $users = $this->getUsersWithoutUserCourses();
        $usersCount = 0;
        $start = microtime(true);
        foreach ($users as $u)
        {
            $numFollowsPerUser = mt_rand(8, 18);
            $currentUserFollows = array();

            while($numFollowsPerUser)
            {
                $randNumb = mt_rand(0, count($fd) - 1);
                $item = $fd[$randNumb];
                if(isset($currentUserFollows[$item->getType()][$item->getId()]))
                {
                    continue;
                }

                $currentUserFollows[$item->getType()][$item->getId()] = 1;
                $follows[$item->getType()][$item->getId()]++;

                $bulkFollows[] = $this->getBulkInsterFollowRow($u['uid'], $item);
                $numFollowsPerUser--;
            }

            $usersCount++;
            if($usersCount%1000 == 0)
            {
                $output->writeln("Users Processed : " . $usersCount);
                $time_elapsed_secs = microtime(true) - $start;
                echo "Took $time_elapsed_secs seconds \n";
                $start = microtime(true);
            }

        }

        var_dump($follows);
        var_dump(count($bulkFollows));

        $bulkFollowsChunks = array_chunk($bulkFollows,1000);

        $sql = '';
        $file = fopen("/tmp/follows.sql","w");
        foreach ($bulkFollowsChunks as $chunk)
        {
            $sql = "INSERT INTO follows(user_id,item,item_id,created) VALUES " . implode(",",$chunk) . ";";
            fwrite($file,$sql);
        }
        fclose($file);

        exit();

    }

    private function buildFrequencyDistribution()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        // Build a list of follows and their counts
        $rsmFollowCounts = new ResultSetMapping();
        $rsmFollowCounts->addScalarResult("item","item");
        $rsmFollowCounts->addScalarResult("item_id","item_id");
        $rsmFollowCounts->addScalarResult("followed","followed");
        $followCountsQuery = $em->createNativeQuery("SELECT item,item_id,followed FROM follow_counts ORDER BY followed DESC LIMIT 200",$rsmFollowCounts);
        $results = $followCountsQuery->getResult();
        $fd = array();
        // To do:
        $follows = array();
        foreach ($results as $r)
        {
            $weight = round(($r['followed']/1000));
            $item = Item::getItem($r['item'],$r['item_id']);
            $follows[$r['item']][$r['item_id']] = $r['followed'];
            while($weight)
            {
                $fd[] = $item;
                $weight--;
            }
        }

        // Add Top 50 MOOCS and Ivy League MOOCS 15 times each.
        $top50Item = Item::getItem('collection',3);
        $ivyLeagueItem = Item::getItem('collection',5);
        $follows['collection']['5'] = 0;
        $timesToAdd = 10;
        while($timesToAdd)
        {
            $fd[] = $top50Item;
            $fd[] = $ivyLeagueItem;
            $timesToAdd--;
        }
        $fd[] = $ivyLeagueItem;$fd[] = $ivyLeagueItem;$fd[] = $ivyLeagueItem;

        return array("fd" => $fd, "follows" =>$follows);
    }

    private function getUsersWithoutUserCourses()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id','uid');
        $usersQueryNoFollowsNoUsersCourses = $em->createNativeQuery("SELECT u.id FROM users u WHERE NOT EXISTS (SELECT f.user_id FROM follows f WHERE f.user_id = u.id) AND u.id <= 436539 AND NOT EXISTS (SELECT uc.user_id FROM users_courses uc  WHERE uc.user_id = u.id)",$rsm);
        return $usersQueryNoFollowsNoUsersCourses->getResult();
    }

    private function getUsersWithUserCourses()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id','uid');
        $rsm->addScalarResult('course_id','cid');
        $query = $em->createNativeQuery("SELECT u.id,uc.course_id FROM users u LEFT JOIN users_courses uc on uc.user_id = u.id WHERE NOT EXISTS (SELECT f.user_id FROM follows f WHERE f.user_id = u.id) AND  u.id <= 436539 AND uc.id IS NOT NULL",$rsm);
        return $query->getResult();
    }

    private function getBulkInsterFollowRow($uid,Item $item)
    {

        $created = $this->getUsersCreatedDate($uid);
        return "({$uid},'{$item->getType()}','{$item->getId()}','$created')";
    }

    private $usersCreated = array();
    private function getUsersCreatedDate($id)
    {
        if(empty($this->usersCreated))
        {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $rsm = new ResultSetMapping();
            $rsm->addScalarResult('id','uid');
            $rsm->addScalarResult('created','created');
            $query = $em->createNativeQuery("SELECT id,created FROM users", $rsm);
            foreach ($query->getResult() as $result)
            {
                $this->usersCreated[$result['uid']] = $result['created'];
            }
        }
        return $this->usersCreated[$id];
    }
}