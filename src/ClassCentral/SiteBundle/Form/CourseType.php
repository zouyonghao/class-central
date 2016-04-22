<?php

namespace ClassCentral\SiteBundle\Form;

use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\CourseStatus;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CourseType extends AbstractType {

    /**
     * If true does not show fields like instructors, institutions, tags, to load faster
     * @var bool
     */
    private $lite;
    public function __construct($lite = false)
    {
        $this->lite = $lite;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->add('name')
            ->add('isMOOC','checkbox', array('label' => 'IS MOOC','required'=>false))
            ->add('description', null, array('required'=>false))
            ->add('longDescription', null, array('required'=>false))
            ->add('syllabus', null, array('required'=>false))
            ->add('shortName',null, array('required'=>false))
            ->add('status','choice',array('choices' => CourseStatus::getStatuses()))
            ->add('stream', 'entity', array(
                'class' => 'ClassCentralSiteBundle:Stream',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC');
                },
            ))

            ->add('initiative', 'entity', array(
                'required'=>false,
                'empty_value' => true,
                'class' => 'ClassCentralSiteBundle:Initiative',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('i')->orderBy('i.name','ASC');
                }
             ));
        if(!$this->lite)
        {
            $builder->add('institutions', null, array(
                'required'=>false,
                'empty_value'=>true,
                'class' => 'ClassCentralSiteBundle:Institution',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('i')->orderBy('i.name','ASC');
                }
            ))
                ->add('instructors', null, array(
                'required'=>false,
                'empty_value'=>true,
                'class' => 'ClassCentralSiteBundle:Instructor',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('i')->orderBy('i.id','DESC');
                }
            ))
                ->add('careers', null, array(
                    'required'=>false,
                    'empty_value'=>true,
                    'class' => 'ClassCentralSiteBundle:Career',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('i')->orderBy('i.name','DESC');
                    }
                ))
            ;
        }

        $builder->add('language',null,array('required'=>false,'empty_value' => true))
            ->add('url')
            ->add('videoIntro')
            ->add('price')
            ->add('pricePeriod','choice', array('choices'=> Course::$PRICE_PERIODS))
            ->add('certificate')
            ->add('certificatePrice')
            ->add('workloadType','choice', array('choices'=> Course::$WORKLOAD))
            ->add('workloadMin',null,array('label'=>'Min Effort Required (in hours)'))
            ->add('workloadMax',null,array('label'=>'Max Effort Required (in hours)'))
            ->add('durationMin',null,array('label' => 'Min Duration Min (in Weeks)' ))
            ->add('durationMax',null,array('label' => 'Max Duration Max (in Weeks)' ))
            //->add('searchDesc')
            ->add('one_liner',null,array('required' => false))
            ->add('thumbnail')
            ->add('interview')
        ;
       
      
    }

    public function getName() {
        return 'classcentral_sitebundle_coursetype';
    }
        
}
