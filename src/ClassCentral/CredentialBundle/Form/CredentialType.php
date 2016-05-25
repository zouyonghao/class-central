<?php

namespace ClassCentral\CredentialBundle\Form;

use ClassCentral\CredentialBundle\Entity\Credential;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CredentialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('status','choice',array('choices' => Credential::getStatuses()))
            ->add('subject','choice',array('choices' => Credential::$SUBJECTS,'label' => 'Subject (credentials page filters)' ))
            ->add('stream', 'entity', array(
                'class' => 'ClassCentralSiteBundle:Stream',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC');
                },
                'label' => 'Subject (from the subject taxonomy)',
                'empty_value'=>true,
                'required'=>false,
            ))
            ->add('name')
            ->add('slug')
            ->add('oneLiner')
            ->add('subTitle')
            ->add('description')
            ->add('syllabus')
            ->add('price')
            ->add('pricePeriod','choice', array('choices'=> Credential::$CREDENTIAL_PRICE_PERIODS))
            ->add('durationMin')
            ->add('durationMax')
            ->add('workloadMin')
            ->add('workloadMax')
            ->add('enrollmentStart')
            ->add('enrollmentEnd')
            ->add('startDate')
            ->add('endDate')
            ->add('sponsored')
            ->add('workloadType','choice', array('choices'=> Credential::$CREDENTIAL_WORKLOAD))
            ->add('url')
            ->add('initiative')
            ->add('institutions', null, array('required' => false))
            ->add('courses',null, array('required' => false))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ClassCentral\CredentialBundle\Entity\Credential'
        ));
    }

    public function getName()
    {
        return 'classcentral_credentialbundle_credentialtype';
    }
}
