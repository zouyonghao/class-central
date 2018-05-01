<?php

namespace ClassCentral\SiteBundle\Form;

use ClassCentral\SiteBundle\Entity\HelpGuideArticle;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class HelpGuideArticleType extends AbstractType
{

    private $helpGuidesWriteIds = [];

    public function __construct( $writerIds = [] )
    {
        $this->helpGuidesWriteIds = $writerIds;
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('text')
            ->add('summary')
            ->add('orderId')
            ->add('slug')
            ->add('status','choice',array('choices' => HelpGuideArticle::$statuses))
            ->add('author','entity',[
                'required' => false,
                'empty_value' => "No author",
                'class' => 'ClassCentralSiteBundle:User',
                'choice_label' => 'name',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('u')->where("u.id IN (:writerIds)")->setParameter('writerIds',  $this->helpGuidesWriteIds);
                }
            ])
            ->add('section', null, [
              'empty_value' => false
            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ClassCentral\SiteBundle\Entity\HelpGuideArticle'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'classcentral_sitebundle_helpguidearticle';
    }
}
