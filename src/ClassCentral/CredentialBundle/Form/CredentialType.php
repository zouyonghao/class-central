<?php

namespace ClassCentral\CredentialBundle\Form;

use ClassCentral\CredentialBundle\Entity\Credential;
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
            ->add('description')
            ->add('price')
            ->add('pricePeriod','choice', array('choices'=> Credential::$CREDENTIAL_PRICE_PERIODS))
            ->add('durationMin')
            ->add('durationMax')
            ->add('workloadMin')
            ->add('workloadMax')
            ->add('workloadType','choice', array('choices'=> Credential::$CREDENTIAL_WORKLOAD))
            ->add('url')
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
