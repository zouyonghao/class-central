<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 11/26/13
 * Time: 4:19 PM
 */

namespace ClassCentral\SiteBundle\Utility\PageHeader;


use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Institution;
use ClassCentral\SiteBundle\Entity\Stream;

class PageHeaderFactory {

    public static function  get($entity)
    {
        if($entity instanceof Initiative)
        {
            return self::getFromInitiative($entity);
        }

        if($entity instanceof Stream)
        {
            return self::getFromStream($entity);
        }

        if($entity instanceof Institution)
        {
            return self::getFromInstitution($entity);
        }

        // Should not reach here
        throw new \Exception('$enity should be a type of Initiative, Stream, Institution');
    }

    private static function getFromInitiative(Initiative $entity)
    {
        $info = new PageHeaderInfo("Initiative");
        $info->setName($entity->getName());
        $info->setUrl($entity->getUrl());
        $info->setDescription($entity->getDescription());
        if($entity->getImageUrl())
        {
            $info->setImageUrl($entity->getImageDir(). $entity->getImageUrl());
        }
        return $info;
    }

    private static function getFromStream(Stream $entity)
    {
        $info = new PageHeaderInfo("Stream");
        $info->setName($entity->getName());
        $info->setDescription($entity->getDescription());
        if($entity->getImageUrl())
        {
            $info->setImageUrl($entity->getImageDir(). $entity->getImageUrl());
        }
        return $info;
    }

    private static function getFromInstitution(Institution $entity)
    {
        $info = new PageHeaderInfo("Institution");
        $info->setName($entity->getName());
        $info->setUrl($entity->getUrl());
        $info->setDescription($entity->getDescription());
        if($entity->getImageUrl())
        {
            $info->setImageUrl($entity->getImageDir(). $entity->getImageUrl());
        }
        return $info;
    }

} 