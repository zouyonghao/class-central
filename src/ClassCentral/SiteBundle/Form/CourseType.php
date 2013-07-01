<?php

namespace ClassCentral\SiteBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class CourseType extends AbstractType {

    public function buildForm(FormBuilder $builder, array $options) {
        $builder
            ->add('name')
            ->add('description', null, array('required'=>false))
            ->add('shortName',null, array('required'=>false))
            ->add('stream') 
            ->add('initiative', null, array('required'=>false, 'empty_value' => true))  
            ->add('institutions', null, array('required'=>false, 'empty_value'=>true))
            ->add('language',null,array('required'=>false,'empty_value' => true))
            ->add('url')
            ->add('videoIntro')
            ->add('length')
            ->add('instructors', null, array('required'=>false, 'empty_value'=>true))
            ->add('searchDesc')
        ;
       
      
    }

    public function getName() {
        return 'classcentral_sitebundle_coursetype';
    }
        
}
