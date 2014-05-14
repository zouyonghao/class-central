<?php

namespace ClassCentral\SiteBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SignupType extends AbstractType{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email','email', array(
                   'attr' => array(
                        'placeholder' => 'Email'
                )
            ))
            ->add('name', null, array('required' => true,'attr'=>array(
                'placeholder' => 'Full name'
    )       ))
        ;

         $builder->add('password', 'repeated', array(
             'first_name'  => 'password',
             'second_name' => 'confirm_password',
             'type'        => 'password',
             'invalid_message' => "The password fields must match",
             "first_options" => array('label' => 'Password','attr'=>array('placeholder' => 'Password')),
             "second_options" => array('label' => 'Confirm Password', 'attr' => array('placeholder' => 'Confirm password'))
         ));
        $builder->add('save', 'submit',array(
            'label' => 'Sign Up',
            'attr' => array(
                'class' => 'btn btn-primary btn-course-cc'
            )
        ));
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ClassCentral\SiteBundle\Entity\User'
        ));

    }


    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return "classcentral_sitebundle_signuptype";
    }
}