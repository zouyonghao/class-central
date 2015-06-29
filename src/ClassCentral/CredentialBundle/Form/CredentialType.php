<?php

namespace ClassCentral\CredentialBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CredentialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('oneLiner')
            ->add('price')
            ->add('pricePeriod')
            ->add('durationMin')
            ->add('durationMax')
            ->add('workloadMin')
            ->add('workloadMax')
            ->add('url')
            ->add('description')
            ->add('initiative')
            ->add('institutions')
            ->add('courses')
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
