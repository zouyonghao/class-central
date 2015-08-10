<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 7/23/15
 * Time: 5:13 PM
 */

namespace ClassCentral\CredentialBundle\Services;



use ClassCentral\CredentialBundle\Entity\CredentialReview;
use ClassCentral\ElasticSearchBundle\DocumentType\CredentialDocumentType;
use ClassCentral\SiteBundle\Services\Kuber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class Credential {

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Gets an image from an Credential image if there is one
     * @param \ClassCentral\CredentialBundle\Entity\Credential $credential
     */
    public function getImage(\ClassCentral\CredentialBundle\Entity\Credential $credential)
    {
        $kuber = $this->container->get('kuber');
        return $kuber->getUrl(Kuber::KUBER_ENTITY_CREDENTIAL,Kuber::KUBER_TYPE_CREDENTIAL_IMAGE, $credential->getId() );
    }

    /**
     * Gets an image from the credential
     * @param \ClassCentral\CredentialBundle\Entity\Credential $credential
     */
    public function getCardImage(\ClassCentral\CredentialBundle\Entity\Credential $credential)
    {
        $kuber = $this->container->get('kuber');
        $imageService = $this->container->get('image_service');
        $credentialImage = $this->getImage($credential);
        if($credentialImage)
        {
            // TODO: Default image for credential card
            return $imageService->cropAndSaveImage(
                  $credentialImage,
                \ClassCentral\CredentialBundle\Entity\Credential::CREDENTIAL_CARD_IMAGE_HEIGHT,
                \ClassCentral\CredentialBundle\Entity\Credential::CREDENTIAL_CARD_IMAGE_WIDTH,
                Kuber::KUBER_ENTITY_CREDENTIAL,
                Kuber::KUBER_TYPE_CREDENTIAL_CARD_IMAGE,
                $credential->getId()
            );
        }

        // TODO: Default image for credential card if none exists
        return null;
    }

    /**
     * @param $providerName
     * @return array
     */
    public function getCertificateDetails( $providerName )
    {
        switch(strtolower($providerName))
        {
            case 'coursera':
                return array('name'=>'Specialization', 'slug' => 'specialization');
                break;
            case 'udacity':
                return array('name'=>'Nanodegree', 'slug' => 'nanodegree');
                break;
            case 'edX':
                return array('name'=>'XSeries', 'slug' => 'xseries');
                break;
        }

        return array();
    }

    public function getCertDetailsFromCertSlug( $slug )
    {
        switch(strtolower($slug))
        {
            case 'specialization':
                return array('name'=>'Specialization', 'slug' => 'specialization');
                break;
            case 'nanodegree':
                return array('name'=>'Nanodegree', 'slug' => 'nanodegree');
                break;
            case 'xseries':
                return array('name'=>'XSeries', 'slug' => 'xseries');
                break;
        }

    }
    public function calculateAverageRating(\ClassCentral\CredentialBundle\Entity\Credential $credential)
    {
        $rating = 0;
        $reviews = $credential->getReviews();
        $validReviewsCount = 0;

        if($reviews && $reviews->count() > 0)
        {
            $ratingSum = 0;
            foreach($reviews as $review)
            {
                if($review->getStatus() < CredentialReview::REVIEW_NOT_SHOWN_STATUS_LOWER_BOUND )
                {
                    $ratingSum += $review->getRating();
                    $validReviewsCount++;
                }
            }

            if($validReviewsCount > 0)
            {
                $rating = $ratingSum/$validReviewsCount;
            }
        }

        return array(
            'rating' => $rating,
            'numRatings' => $validReviewsCount
        );
    }


    /**
     * Indexs a credential into elasticsearch
     * @param \ClassCentral\CredentialBundle\Entity\Credential $credential
     */
    public function index(\ClassCentral\CredentialBundle\Entity\Credential $credential)
    {
        $cDoc = new CredentialDocumentType( $credential, $this->container);
        $doc = $cDoc->getDocument( $this->container->getParameter( 'es_index_name' ) );
        $this->container->get('es_client')->index($doc);
    }

    /**
     * Parse the Request parameters figure out the filtering options
     * @param Request $request
     * @return array
     */
    public function getCredentialsFilterParams( $getParams = array() )
    {
        $params = array();

        $params['credentials'] = array();
        if( !empty($getParams['credentials']) )
        {
            $params['credentials'] = explode(',', $getParams['credentials'] );
        }
        return $params;
    }

    /**
     * Given the params, get all credentials and the associated parameters
     * @param array $params
     * @return mixed
     */
    public function getCredentialsHTML( $params = array() )
    {
        $esCredentials = $this->get('es_credentials');


        return $this->render('ClassCentralCredentialBundle:Credential:credentials.html.twig', array(
            'page' => 'credentials',
            'credentials' => $esCredentials->find()
        ));
    }
} 