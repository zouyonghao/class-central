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
            $numFollowers = $this->saveAndUpdateCount($item);
            $output->writeln($institution->getName(). " - " . $numFollowers);
        }

        $providers = $em->getRepository('ClassCentralSiteBundle:Initiative')->findAll();
        foreach ($providers as $provider)
        {
            $item = Item::getItemFromObject($provider);
            $numFollowers = $this->saveAndUpdateCount($item);
            $output->writeln($provider->getName(). " - " . $numFollowers);
        }

        $subjects = $em->getRepository('ClassCentralSiteBundle:Stream')->findAll();
        foreach ($subjects as $subject)
        {
            $item = Item::getItemFromObject($subject);
            $numFollowers = $this->saveAndUpdateCount($item);
            $output->writeln($subject->getName(). " - " . $numFollowers);
        }

        $tags = $em->getRepository('ClassCentralSiteBundle:Tag')->findAll();
        foreach ($tags as $tag)
        {
            $item = Item::getItemFromObject($tag);
            $numFollowers = $this->saveAndUpdateCount($item);
            $output->writeln($tag->getName(). " - " . $numFollowers);
        }
    }

    public function saveAndUpdateCount(Item $item)
    {
        $fs = $this->getContainer()->get('follow');
        $em = $this->getContainer()->get('doctrine')->getManager();

        $yesterday = new \DateTime();
        $yesterday->sub(new \DateInterval('P1D'));

        $followCountObj = $fs->getFollowCountsObjectFromItem($item);
        if($followCountObj && $followCountObj->getModified() >= $yesterday)
        {
            return $followCountObj->getFollowed();
        }

        $followCountObj = $fs->getFollowCountsObjectFromItem($item);
        if(!$followCountObj)
        {
            $followCountObj = new FollowCounts();
            $followCountObj->setItem($item->getType());
            $followCountObj->setItemId($item->getId());
        }

        $numFollowers = $fs->calculateNumFollowers($item);
        $followCountObj->setFollowed($numFollowers);

        $em->persist($followCountObj);
        $em->flush();

        return $numFollowers;
    }
}