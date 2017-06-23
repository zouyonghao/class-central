<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/22/17
 * Time: 10:45 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Types\DocumentType;

class MOOCReportArticleDocumentType extends DocumentType
{

    public function getType()
    {
        return 'mooc_report_article';
    }

    public function getId()
    {
        return $this->entity->getId();
    }

    public function getBody()
    {
        $article = $this->entity;
        $b = [];

        $b['id'] = $article->getId();
        $b['title'] = $article->getTitle();
        $b['slug'] = $article->getSlug();
        $b['status'] = $article->getStatus();
        $b['excerpt'] = $article->getExcerpt();
        $b['content'] = $article->getContent();
        $b['link'] = $article->getLink();
        $b['thumbnail'] = $article->getThumbnail();
        $b['pinned'] = $article->getPinned();

        return $b;
    }

    public function getMapping()
    {
        return array(
            "slug" => array(
                "type" => "string",
                "index" => "not_analyzed"
            )
        );
    }
}