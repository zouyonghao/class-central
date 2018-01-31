<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 10/12/16
 * Time: 11:49 PM
 */

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\CredentialBundle\Entity\Credential;
use ClassCentral\SiteBundle\Controller\InitiativeController;
use ClassCentral\SiteBundle\Controller\InstitutionController;
use ClassCentral\SiteBundle\Controller\LanguageController;
use ClassCentral\SiteBundle\Controller\StreamController;
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

        // List all the courses first
        $coursesSitemap = fopen("web/courses_sitemap.txt", "w");
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
                fwrite($coursesSitemap,$coursePageUrl."\n");
            }
        }
        fclose($coursesSitemap);


        // CREDENTIALS
        $credentialsSitemap = fopen("web/credentials_sitemap.txt", "w");
        fwrite($credentialsSitemap,$baseUrl. $router->generate('credentials')."\n");
        $credentials = $em->getRepository('ClassCentralCredentialBundle:Credential')->findAll();
        foreach($credentials as $credential)
        {
            if($credential->getStatus() < Credential::CREDENTIAL_NOT_SHOWN_LOWER_BOUND)
            {
                $credentialPageUrl = $baseUrl . $router->generate(
                        'credential_page',
                        array('slug'=>$credential->getSlug())
                    );
                fwrite($credentialsSitemap,$credentialPageUrl."\n");
            }
        }
        fclose($credentialsSitemap);

        // Subjects
        $subjectsSitemap = fopen("web/subjects_sitemap.txt", "w");
        fwrite($subjectsSitemap,$baseUrl. $router->generate('subjects')."\n");
        $streamController = new StreamController();
        $subjects = $streamController->getSubjectsList($this->getContainer());
        foreach($subjects['parent'] as $subject)
        {
            $subjectPageUrl =
                $baseUrl . $router->generate(
                    'ClassCentralSiteBundle_stream',
                    array('slug'=>$subject['slug'])
                );
            fwrite($subjectsSitemap,$subjectPageUrl."\n");
        }
        foreach($subjects['children'] as $childSubjects)
        {
            foreach( $childSubjects as $subject)
            {
                $subjectPageUrl =
                    $baseUrl . $router->generate(
                        'ClassCentralSiteBundle_stream',
                        array('slug'=>$subject['slug'])
                    );
                fwrite($subjectsSitemap,$subjectPageUrl."\n");
            }
        }
        fclose($subjectsSitemap);


        // Providers
        $providersSitemap = fopen("web/providers_sitemap.txt", "w");
        fwrite($providersSitemap,$baseUrl. $router->generate('providers')."\n");
        $providerController = new InitiativeController();
        $providers = $providerController->getProvidersList($this->getContainer());
        foreach($providers['providers'] as $provider)
        {
            if($provider['count'] > 0)
            {
                $providerPageUrl =
                    $baseUrl . $router->generate(
                        'ClassCentralSiteBundle_initiative',
                        array('type'=>$provider['code'])
                    );
                fwrite($providersSitemap,$providerPageUrl."\n");
            }
        }
        fclose($providersSitemap);


        // Universities/Institutions
        $institutionsSitemap = fopen("web/institutions_sitemap.txt", "w");
        fwrite($institutionsSitemap,$baseUrl. $router->generate('institutions')."\n");
        $insController = new InstitutionController();
        $institutions = $insController->getInstitutions( $this->getContainer(), false);
        $institutions = $institutions['institutions'];
        foreach($institutions as $institution)
        {
            if($institution['count'] > 0)
            {
                $insPageUrl =
                    $baseUrl . $router->generate(
                        'ClassCentralSiteBundle_institution',
                        array('slug'=>$institution['slug'])
                    );
                fwrite($institutionsSitemap,$insPageUrl."\n");
            }

        }
        // Get Universities
        fwrite($institutionsSitemap,$baseUrl. $router->generate('universities')."\n");
        $universities = $insController->getInstitutions( $this->getContainer(), true);
        $universities = $universities['institutions'];
        foreach($universities as $university)
        {
            if($university['count']>0)
            {
                $uniPageUrl =
                    $baseUrl . $router->generate(
                        'ClassCentralSiteBundle_university',
                        array('slug'=>$university['slug'])
                    );
                fwrite($institutionsSitemap,$uniPageUrl."\n");
            }

        }
        fclose($institutionsSitemap);


        // Languages
        $languagesSitemap = fopen("web/languages_sitemap.txt", "w");
        fwrite($languagesSitemap,$baseUrl. $router->generate('languages')."\n");
        $langController = new LanguageController();
        $languages = $langController->getLanguagesList($this->getContainer());
        foreach($languages as $language)
        {
            $langPageUrl =
                $baseUrl . $router->generate(
                    'lang',
                    array('slug'=>$language->getSlug())
                );
            fwrite($languagesSitemap,$langPageUrl."\n");
        }

        // Tags
        $tagsSitemap = fopen("web/tags_sitemap.txt", "w");
        $tags = $em->getRepository('ClassCentralSiteBundle:Tag')->findAll();
        foreach ($tags as $tag)
        {
            if(empty($tag->getName())) continue;
            $tagPageUrl =
                $baseUrl . $router->generate(
                    'tag_courses',
                    array('tag'=>urlencode($tag->getName()))
                );
            fwrite($tagsSitemap,$tagPageUrl."\n");
        }

        fclose($tagsSitemap);

        // Index MOOC report articles
        $moocReport = $this->getContainer()->get('mooc_report');
        $pageNo = 1;
        $posts = $moocReport->getPosts($pageNo);
        $moocReportSiteMap= fopen("web/mooc_report_sitemap.txt", "w");
        while($posts)
        {

            foreach ($posts as $post)
            {
                fwrite($moocReportSiteMap,$post['link']."\n");
            }

            $pageNo++;
            $posts = $moocReport->getPosts($pageNo);
        }
        fclose($moocReportSiteMap);

    }
}