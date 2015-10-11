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
use ClassCentral\SiteBundle\Entity\Profile;
use ClassCentral\SiteBundle\Entity\Review;
use ClassCentral\SiteBundle\Services\Kuber;
use ClassCentral\SiteBundle\Utility\ReviewUtility;
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
            case 'hbxcore':
                return array('name'=>'HBX CORe', 'slug' => 'hbxcore');
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

        $params['subjects'] = array();
        if( !empty($getParams['subjects']) )
        {
            $params['subjects'] = explode(',', $getParams['subjects'] );
        }

        return $params;
    }

    /**
     * Given the params, get all credentials and the associated parameters
     * @param array $params
     * @return mixed
     */
    public function getCredentialsInfo( $params = array() )
    {
        $esCredentials = $this->container->get('es_credentials');
        $esCredentialsResponse =  $esCredentials->find($params);

        return array(
            'credentials' => $esCredentialsResponse['hits']['hits'],
            'facets' =>  array(
                'certificates' => $esCredentialsResponse['facets']['certificate']['terms'],
                'subjects' => $esCredentialsResponse['facets']['subjectSlug']['terms'],
            ),
            'numCredentials' => $esCredentialsResponse['hits']['total'],
        );
    }

    public function getCredentialReviewArray(CredentialReview $review)
    {
        $r = array();
        $r['id'] = $review->getId();
        $r['title'] = $review->getTitle();
        $r['text'] = nl2br( preg_replace("/[\r\n]+/", "\n\n", $review->getText()));;
        $r['status'] = $review->getStatus();
        $r['progress'] = $review->getProgress();
        $r['certificateLink'] = $review->getLink();
        $r['rating'] = $review->getRating();
        $r['formattedRating'] = ReviewUtility::formatRating($review->getRating());
        $r['topicCoverage'] = $review->getTopicCoverage();
        $r['jobReadiness'] = $review->getJobReadiness();
        $r['support'] = $review->getSupport();
        $r['effort'] = $review->getEffort();
        $r['duration'] = $review->getDuration();
        $r['publishedDate'] = $review->getCreated()->format('Y-m-d');

        // Get user details
        $u = array();
        if( $review->getUser() )
        {
            $userService = $this->container->get('user_service');
            $user = $review->getUser();
            $u['name'] = $user->getDisplayName();
            $u['profileUrl'] = $userService->getProfileUrl( $user->getId(), $user->getHandle(), $user->getIsPrivate() );
            $u['profilePic'] = $userService->getProfilePic( $user->getId() );
        }
        else
        {
            $u['name'] = ( $review->getReviewerName() ) ? $review->getReviewerName() : 'Anonymous';
            $u['profileUrl'] = null;
            $u['profilePic'] = Profile::DEFAULT_PROFILE_PIC;
        }
        $r['user'] = $u;

        $reviewSubtitle = '';
        if( $review->getProgress() == CredentialReview::PROGRESS_TYPE_COMPLETED )
        {
            // completed the course
            $reviewSubtitle = "completed this credential in " . $review->getDateCompleted()->format('M Y') . ".";
        }
        else
        {
            $reviewSubtitle  =  CredentialReview::$progressListDropdown[$review->getProgress()] . " this credential.";
        }
        $r['reviewSubtitle'] = $reviewSubtitle;



        return $r;
    }

    public function getCredentialReviews($slug)
    {
        $em = $this->container->get('doctrine')->getManager();
        $credential = $em->getRepository('ClassCentralCredentialBundle:Credential')->findOneBy(array(
            'slug' => $slug
        ));
        if( !$credential )
        {
            throw new \Exception("$slug is not a valid credential");
        }

        $rating = $this->calculateAverageRating( $credential);

        $reviewEntities = $em->createQuery("
               SELECT r,LENGTH (r.text) as reviewLength from ClassCentralCredentialBundle:CredentialReview r JOIN r.credential c WHERE c.slug = '$slug'
                ORDER BY r.rating DESC, reviewLength DESC")
            ->getResult();

        $reviewCount = 0;
        $ratingCount = 0;
        $r = array();
        foreach($reviewEntities as $review)
        {
            $review = $review[0];
            if( $review->getStatus() < Review::REVIEW_NOT_SHOWN_STATUS_LOWER_BOUND )
            {
                $ratingCount++;
                $reviewCount++;
                $r[] = $this->getCredentialReviewArray($review);
            }
        }

        $reviews = array();
        $reviews['count'] = $ratingCount;
        $reviews['ratingCount'] = $ratingCount;
        $reviews['reviewCount'] = $reviewCount;
        $reviews['rating'] = $rating['rating'];
        $reviews['formattedRating'] = ReviewUtility::formatRating( $rating['rating'] );

        $reviews['reviews'] = $r;

        return $reviews;
    }


} 