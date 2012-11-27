<?php

namespace ClassCentral\SiteBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class OfferingType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {               
        $builder
            ->add('startDate')
            ->add('endDate')
            ->add('status','choice', array('choices'=> \ClassCentral\SiteBundle\Entity\Offering::getStatuses() ))
            ->add('course',null,array('property'=>'name'))
            ->add('name')    
            ->add('initiative', null, array('required'=>false, 'empty_value' => true))    
            ->add('url')    
            ->add('videoIntro')    
            ->add('length')
            ->add('instructors', null, array('required'=>false, 'empty_value'=>true)) 
            ->add('searchDesc')    
        ;
    }

    public function getName()
    {
        return 'classcentral_sitebundle_offeringtype';
    }
}
