<?php

namespace ClassCentral\SiteBundle\Form;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class OfferingType extends AbstractType
{

    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {               
        $builder
            ->add('startDate')
            ->add('endDate')
            ->add('status','choice', array('choices'=> \ClassCentral\SiteBundle\Entity\Offering::getStatuses() ))
            ->add('course','text',array(
                'invalid_message' => 'That is not a valid course id',
            ))
            //->add('name')
            ->add('shortName',null, array('required'=>false))
            //->add('initiative', null, array('required'=>false, 'empty_value' => true))    
            ->add('url')    
//            ->add('videoIntro')
//            ->add('length')
//            ->add('instructors', null, array('required'=>false, 'empty_value'=>true))
//            ->add('searchDesc')
        ;

        $builder->get('course')
            ->addModelTransformer(new CourseIdToNameTransformer($this->manager));
    }

    public function getName()
    {
        return 'classcentral_sitebundle_offeringtype';
    }
}
