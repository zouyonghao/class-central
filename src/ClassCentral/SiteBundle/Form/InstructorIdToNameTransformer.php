<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 10/27/16
 * Time: 12:49 PM
 */

namespace ClassCentral\SiteBundle\Form;


use Doctrine\Common\Persistence\ObjectManager;

class InstructorIdToNameTransformer
{
    private $manager;

    public function  __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    public function transform($instructor)
    {
        if(null == $instructor)
        {
            return '';
        }

        return $instructor->getId();
    }


    public function reverseTransform($id)
    {
        if( !$id )
        {
            return ;
        }

        $instructor = $this->manager->getRepository('ClassCentralSiteBundle:Instructor')->find($id);

        if(null === $instructor)
        {
            throw new TransformationFailedException(
                "Instructor with $id does not exit"
            );
        }

        return $instructor;
    }

}