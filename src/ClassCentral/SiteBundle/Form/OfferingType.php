<?php

namespace ClassCentral\SiteBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class OfferingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {               
        $builder
            ->add('startDate')
            ->add('endDate')
            ->add('status','choice', array('choices'=> \ClassCentral\SiteBundle\Entity\Offering::getStatuses() ))
            ->add('course',null,array('property'=>'name'))
            //->add('name')
            ->add('shortName',null, array('required'=>false))
            //->add('initiative', null, array('required'=>false, 'empty_value' => true))    
            ->add('url')    
//            ->add('videoIntro')
//            ->add('length')
//            ->add('instructors', null, array('required'=>false, 'empty_value'=>true))
//            ->add('searchDesc')
        ;
    }

    public function getName()
    {
        return 'classcentral_sitebundle_offeringtype';
    }
}
