<?php

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Entity\FollowCounts;
use ClassCentral\SiteBundle\Entity\Item;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateFollowCountsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('classcentral:follows:calculatecount');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $fs = $this->getContainer()->get('follow');

        $institutions = $em->getRepository('ClassCentralSiteBundle:Institution')->findAll();
        foreach ($institutions as $institution)
        {
            $item = Item::getItemFromObject($institution);
            $numFollowers = $fs->calculateNumFollowers($item);

            $followCountObj = $fs->getFollowCountsObjectFromItem($item);
            if(!$followCountObj)
            {
                $followCountObj = new FollowCounts();
                $followCountObj->setItem($item->getType());
                $followCountObj->setItemId($item->getId());
            }
            $followCountObj->setFollowed($numFollowers);
            $em->persist($followCountObj);
            $em->flush();

            // Save the count
            $output->writeln($institution->getName(). " - " . $numFollowers);
        }
    }
}