<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 12/31/15
 * Time: 5:47 PM
 */

namespace ClassCentral\SiteBundle\Entity;

use ClassCentral\CredentialBundle\Entity\Credential;

/**
 * Class Item
 * A class to different items at Class Central
 *i.e Credential, Subject, institution
 * @package ClassCentral\SiteBundle\Entity
 */
class Item
{

    private $type;

    private $id;

    const ITEM_TYPE_CREDENTIAL = 'credential';
    const ITEM_TYPE_SUBJECT = 'subject';

    public static $items = array(
        self::ITEM_TYPE_CREDENTIAL, self::ITEM_TYPE_SUBJECT
    );

    private function __construct()
    {

    }

    /**
     * @param Item $item
     */
    public static function getItemInfo(Item $item)
    {
        $repository = null;

        switch ($item->getType() )
        {
            case self::ITEM_TYPE_CREDENTIAL:
                $repository = 'ClassCentralCredentialBundle:Credential';
                break;
            case self::ITEM_TYPE_SUBJECT:
                $repository = 'ClassCentralSiteBundle:Stream';
                break;
            default:
                throw new \Exception("Item does not exist");
        }

        return array(
            'repository' => $repository
        );
    }

    public static function getItemFromObject($obj)
    {
        $item = new Item();
        $item->setId( $obj->getId() );

        switch(true) {
            case $obj instanceof Credential:
                $item->setType(self::ITEM_TYPE_CREDENTIAL);
                break;
            case $obj instanceof Stream:
                $item->setType(self::ITEM_TYPE_SUBJECT);
                break;
            default:
                throw new \Exception("Item does not exist");
        }

        return $item;
    }

    public static function getItem($type,$itemId)
    {
        if( in_array($type,self::$items) )
        {
            $item = new Item();
            $item->setType($type);
            $item->setId($itemId);
            return $item;
        }
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
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



}