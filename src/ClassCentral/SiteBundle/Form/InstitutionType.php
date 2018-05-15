<?php

namespace ClassCentral\SiteBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstitutionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('url')
            ->add('slug', null, array(
                'read_only' => $options['is_edit'],
                'attr' => array('style' => 'color: #a8a8a8')
            ))
            ->add('isUniversity', null, array('required' => false))
            ->add('description')
            ->add('imageUrl')
            ->add('country', null, array('required' => false))
            ->add('continent', null, array('required' => false))
        ;
    }

    public function getName()
    {
        return 'classcentral_sitebundle_institutiontype';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'is_edit' => false
        ));
    }
}
