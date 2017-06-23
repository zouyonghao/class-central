<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/30/14
 * Time: 8:07 PM
 */

namespace ClassCentral\ElasticSearchBundle\Command;


use ClassCentral\ElasticSearchBundle\Indexer;
use ClassCentral\ElasticSearchBundle\MOOCReportArticleEntity;
use ClassCentral\SiteBundle\Controller\InitiativeController;
use ClassCentral\SiteBundle\Controller\InstitutionController;
use ClassCentral\SiteBundle\Controller\LanguageController;
use ClassCentral\SiteBundle\Controller\StreamController;
use ClassCentral\SiteBundle\Entity\Initiative;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticSearchIndexerCommand extends ContainerAwareCommand{

    protected function configure()
    {
        $this->setName("classcentral:elasticsearch:indexer")
             ->setDescription("Indexes documents into elastic search")
             ->addOption('courses', null,InputOption::VALUE_OPTIONAL,"Yes/No - Index courses vs Index the rest. Defaults to yes")
             ->addOption('offset',null, InputOption::VALUE_OPTIONAL,"Offset id for courses. Course ids between offset and offset+1000 are Index ")
        ;
    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexer = $this->getContainer()->get('es_indexer');
        $indexer->setContainer($this->getContainer());
        $em = $this->getContainer()->get('doctrine')->getManager();
        $qb = $em->createQueryBuilder();
        $cache = $this->getContainer()->get('cache');

        $indexCourses = true;
        if( $input->getOption('courses') == 'No' )
        {
            $indexCourses = false;
        }

        $offset= 1;
        if(!empty($input->getOption('offset')))
        {
            $offset = intval( $input->getOption('offset') );
        }

        /****
         * Index courses
         */

        if($indexCourses)
        {
            $count = 0;

            unset($courses);
            $query = $qb
                ->select('c')
                ->from('ClassCentralSiteBundle:Course','c')
                ->andwhere( $qb->expr()->gt('c.id', $offset) )
                ->andwhere(  $qb->expr()->lt('c.id', $offset + 1000) )
                ->getQuery()
            ;

            $courses = $query->getResult();
            foreach($courses as $course)
            {
                $indexer->index($course);
                $count++;
            }
            $output->writeLn("$count courses indexed");
        }

        else
        {

            /****
             * Index Credentials
             */
            $credentials = $this->getContainer()->get('doctrine')->getManager()
                ->getRepository('ClassCentralCredentialBundle:Credential')->findAll();

            foreach($credentials as $credential)
            {
                $indexer->index($credential);
            }
            $output->writeln("All Credentials indexed");


            /***
             * Index languages
             */
            $langController = new LanguageController();
            $languages = $cache->get('language_list_count', array($langController, 'getLanguagesList'),array($this->getContainer()));
            foreach($languages as $language)
            {
                $indexer->index($language);
            }
            $output->writeln("All Languages indexed");

            /***
             * Index universities/institutions
             */
            $insController = new InstitutionController();
            // Get institutions
            $data = $insController->getInstitutions( $this->getContainer(), false);
            $institutions = $data['institutions'];
            // Get Universities
            $data = $insController->getInstitutions( $this->getContainer(), true);
            $universities = $data['institutions'];
            $all = array_merge( $institutions, $universities);
            foreach($all as $ins)
            {
                if( $ins['count'] > 0)
                {
                    $i = $em->getRepository('ClassCentralSiteBundle:Institution')->findOneBy( array('slug' => $ins['slug']));
                    $i->setCount( $ins['count'] );
                    $indexer->index($i);
                }

            }
            $output->writeln("All Institutions indexed");

            /****
             * Index providers
             */
            $providerController = new InitiativeController();
            $data = $providerController->getProvidersList( $this->getContainer() );
            foreach($data['providers'] as $provider)
            {
                if($provider['count'] > 0)
                {
                    if($provider['code'] == 'independent')
                    {
                        $p = new Initiative();
                        $p->setCode('independent');
                        $p->setName( 'Independent' );
                    }
                    else
                    {
                        $p = $em->getRepository('ClassCentralSiteBundle:Initiative')->findOneBy( array('code' => $provider['code']));
                    }

                    $p->setCount( $provider['count'] );
                    $indexer->index($p);
                }
            }
            $output->writeln("All Providers indexed");

            /****
             * Index subjects
             */
            $subjectRepository = $em->getRepository('ClassCentralSiteBundle:Stream');
            $subjects = $cache->get('stream_list_count',
                array( new StreamController(), 'getSubjectsList'),
                array( $this->getContainer() )
            );
            foreach($subjects['parent'] as $subject)
            {
                $indexer->index( $subjectRepository->find($subject['id']) );
            }

            foreach($subjects['children'] as $childSubjects)
            {
                foreach( $childSubjects as $subject)
                {
                    $indexer->index( $subjectRepository->find($subject['id']) );
                }
            }

            $output->writeln("All subjects indexed");

            /****
             * Index MOOC Report Articles
             */
            $moocReport = $this->getContainer()->get('mooc_report');
            $pageNo = 1;
            $postCount = 0;
            $posts = $moocReport->getPosts($pageNo);
            while($posts)
            {

                foreach ($posts as $post)
                {
                    $moocReportArticle = MOOCReportArticleEntity::getMOOCReportArticleObj($post);
                    $output->writeln("Indexing Article: ". $moocReportArticle->getTitle());
                    $indexer->index( $moocReportArticle );
                    $postCount++;
                }

                $pageNo++;
                $posts = $moocReport->getPosts($pageNo);
            }
            $output->writeln("$postCount posts indexed");

        }

    }
} 