<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/22/17
 * Time: 10:50 PM
 */

namespace ClassCentral\ElasticSearchBundle;


class MOOCReportArticleEntity
{
    private $id;

    private $slug;

    private $title;

    private $status;

    private $excerpt;

    private $content;

    private $link;

    private $thumbnail;

    private $publishedDate;

    private $pinned;

    public static function getMOOCReportArticleObj($wpPost = [])
    {
        $obj = new MOOCReportArticleEntity();
        $obj->setId($wpPost['id']);
        $obj->setSlug($wpPost['slug']);
        $obj->setTitle($wpPost['title']['rendered']);
        $obj->setStatus($wpPost['status']);
        $obj->setExcerpt($wpPost['excerpt']['rendered']);
        $obj->setContent($wpPost['content']['rendered']);
        $obj->setLink($wpPost['link']);
        $obj->setThumbnail($wpPost['acf']['thumbnail']['url']);
        $obj->setPublishedDate(new \DateTime($wpPost['date']));
        $obj->setPinned($wpPost['acf']['pinned']);

        return $obj;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getExcerpt()
    {
        return $this->excerpt;
    }

    /**
     * @param mixed $excerpt
     */
    public function setExcerpt($excerpt)
    {
        $this->excerpt = $excerpt;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param mixed $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return mixed
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * @param mixed $thumbnail
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * @return mixed
     */
    public function getPublishedDate()
    {
        return $this->publishedDate;
    }

    /**
     * @param mixed $publishedDate
     */
    public function setPublishedDate(\DateTime $publishedDate)
    {
        $this->publishedDate = $publishedDate;
    }

    /**
     * @return mixed
     */
    public function getPinned()
    {
        return $this->pinned;
    }

    /**
     * @param mixed $pinned
     */
    public function setPinned($pinned)
    {
        $this->pinned = $pinned;
    }


}