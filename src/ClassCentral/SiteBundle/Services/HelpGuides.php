<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/23/18
 * Time: 6:33 PM
 */

namespace ClassCentral\SiteBundle\Services;

use ClassCentral\SiteBundle\Entity\HelpGuideArticle;
use ClassCentral\SiteBundle\Entity\HelpGuideSection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HelpGuides
{
    private $container;
    private $em;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine');
    }

    /**
     * Get a list of all articles by section
     * @param $articleSlug
     */
    public function getArticlesBySection(HelpGuideSection $section)
    {

        $articles = $this->em->getRepository('ClassCentralSiteBundle:HelpGuideArticle')->findBy(
            [
                'section' => $section,
                'status' => HelpGuideArticle::HG_ARTICLE_PUBLISHED
            ],
            [
                'orderId' => 'ASC'
            ]
        );

        return array_map(function($article){return $article->__toArray();},$articles);
    }

    public function getSideBarLinks()
    {

        $sidebar = $this->container->get('cache')->get('help_guides_sidebar',function (){
            $sidebar = []; // main sidebar
            $sidebar['divisions'] = []; // various divisions within a sidebar

            // Add Providers as a division
            $providersToInclude = ['coursera','edx','udacity','futurelearn'];
            $providerSections = [];
            foreach ($providersToInclude as $provider)
            {
                $providerSection = $this->em->getRepository('ClassCentralSiteBundle:HelpGuideSection')->findOneBySlug($provider);
                $sectionInfo = [];
                if($providerSection)
                {
                    $sectionInfo = $providerSection->__toArray();
                    $articles = $this->em->getRepository('ClassCentralSiteBundle:HelpGuideArticle')->findBy(
                        [
                            'section' => $providerSection,
                            'status' => HelpGuideArticle::HG_ARTICLE_PUBLISHED
                        ],
                        [
                            'orderId' => 'ASC'
                        ]
                    );

                    $sectionInfo['articles'] = [];
                    foreach ($articles as $article)
                    {
                        $sectionInfo['articles'][] = $article->__toArray();
                    }

                }
                $providerSections[] = $sectionInfo;
            }

            $sidebar['divisions'][] = [
                'name' => 'Provider Guides',
                'sections' => $providerSections
            ];

            return $sidebar;
        });

        return $sidebar;
    }
}
