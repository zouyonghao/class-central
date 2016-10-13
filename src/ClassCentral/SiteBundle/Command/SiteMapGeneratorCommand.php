<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 10/12/16
 * Time: 11:49 PM
 */

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Entity\CourseStatus;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Genearates sitemap.txt which contains a list of all urls
 * Class SiteMapGeneratorCommand
 * @package ClassCentral\SiteBundle\Command
 */
class SiteMapGeneratorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('classcentral:sitemap:generate')
            ->setDescription("Generate Sitemap");
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $router = $this->getContainer()->get('router');
        $baseUrl = $this->getContainer()->getParameter('baseurl');

        $sitemap = fopen("web/sitemap.txt", "w");
        // List all the courses first
        $courses = $em->getRepository('ClassCentralSiteBundle:Course')->findAll();
        foreach ($courses as $course)
        {
            // The course is valid
            if($course->getStatus() < CourseStatus::COURSE_NOT_SHOWN_LOWER_BOUND)
            {
                $coursePageUrl = $baseUrl . $router->generate(
                      'ClassCentralSiteBundle_mooc',
                       array('id' => $course->getId(),'slug'=>$course->getSlug())
                    );
                fwrite($sitemap,$coursePageUrl."\n");
            }
        }

        fclose($sitemap);
    }
}