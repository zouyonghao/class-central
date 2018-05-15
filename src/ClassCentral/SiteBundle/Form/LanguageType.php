<?php

namespace ClassCentral\SiteBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('slug', null, array(
                'read_only' => $options['is_edit'],
                'attr' => array('style' => 'color: #a8a8a8')
            ))
            ->add('code')
            ->add('color')
            ->add('displayOrder')
        ;
    }

    public function getName()
    {
        return 'classcentral_sitebundle_languagetype';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'is_edit' => false
        ));
    }
}
