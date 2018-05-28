<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\HelpGuideArticle;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class HelpGuidesController extends Controller
{
    /**
     * Generates the main help page
     * @param Request $request
     */
    public function indexAction(Request $request)
    {

        $providerSections = $this->get('cache')->get('help_guides_index_page_provider_sections',function (){
            $em = $this->getDoctrine()->getManager();
            $providerSlugs = ['coursera','edx','udacity','futurelearn'];
            $providerSections = [];
            foreach ($providerSlugs as $slug)
            {
                $providerSection =  $em->getRepository('ClassCentralSiteBundle:HelpGuideSection')->findOneBy( ['slug'=> $slug] );

                $articles = [];
                $sectionArray = [];
                if($providerSection)
                {
                    $articles = $this->get('help_guides')->getArticlesBySection($providerSection);
                    $sectionArray = $providerSection->__toArray();
                }

                $sectionArray['articles'] = $articles;
                $providerSections[$slug] = $sectionArray;
            }
            return $providerSections;
        });

        return $this->render(
           'ClassCentralSiteBundle:HelpGuides:help_guide_index.html.twig', [
                'providerSections' => $providerSections
            ]
        );
    }

    /**
     * Generates the page for a particular section
     * @param Request $request
     */
    public function sectionAction(Request $request, $slug)
    {
        $details = $this->get('cache')->get('help_guides_section_articles_' . $slug,function ($slug){
            $em = $this->getDoctrine()->getManager();
            $section = $em->getRepository('ClassCentralSiteBundle:HelpGuideSection')->findOneBy( ['slug'=> $slug] );
            if(!$section)
            {
                throw $this->createNotFoundException('Page does not exist');
            }

            $articles = $this->get('help_guides')->getArticlesBySection($section);
            return [
                'section' => $section->__toArray(),
                'articles' => $articles,
            ];

        },[$slug]);


        return $this->render(
            'ClassCentralSiteBundle:HelpGuides:help_guide_section.html.twig', [
                'slug' => $slug,
                'section' => $details['section'],
                'sectionArticles' => $details['articles'],
                'page' => 'help_guides_section'
            ]
        );
    }

    public function articleAction(Request $request, $slug)
    {

        $cache = $this->get('cache');

        $article = $cache->get('help_guides_article_' . $slug,function($slug) {
            $em = $this->getDoctrine()->getManager();
            $article = $em->getRepository('ClassCentralSiteBundle:HelpGuideArticle')->findOneBy( ['slug'=> $slug] );
            if(!$article)
            {
                throw $this->createNotFoundException('The article does not exist');
            }



            return $article->__toArray();
        },[$slug]);

        if($article['status'] != HelpGuideArticle::HG_ARTICLE_PUBLISHED && !$this->get('security.context')->isGranted('ROLE_ADMIN') )
        {
            throw $this->createNotFoundException('The article does not exist');
        }
        return $this->render('ClassCentralSiteBundle:HelpGuides:help_guide_article.html.twig',
            [
                'article' => $article,
                'page' => 'help_guides_article',
                'current_section' => $article['section']['slug']
            ]
        );
    }
}