<?php

namespace ClassCentral\CredentialBundle\Controller;

use ClassCentral\CredentialBundle\Entity\CredentialReview;
use ClassCentral\SiteBundle\Entity\Profile;
use ClassCentral\SiteBundle\Utility\UniversalHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CredentialReviewController extends Controller
{

    public function newAction(Request $request)
    {
        // For the completed date dropdown show the last 18 months
        $completedDates = array();
        $dt = new \DateTime();
        $dt->modify('first day of next month');

        $timePeriod ='18';
        while($timePeriod)
        {
            $completedDates[$dt->format('Y-m-d')] = $dt->format('M Y') ;
            $dt->modify('first day of previous month');
            $timePeriod--;
        }
        return $this->render('ClassCentralCredentialBundle:CredentialReview:reviewForm.html.twig', array(
            'degrees' => Profile::$degrees,
            'progress' => array_merge(CredentialReview::$progressListDropdown, $completedDates),
        ));
    }

    public function saveAction(Request $request, $credentialId)
    {
        $em = $this->getDoctrine()->getManager();

        $credential = $em->getRepository('ClassCentralCredentialBundle:Credential')->find($credentialId);
        if( !$credential )
        {
            return UniversalHelper::getAjaxResponse(false,"Credential not found");
        }

        // Get the Json post data
        $content = $this->getRequest("request")->getContent();
        if( empty($content) )
        {
            return UniversalHelper::getAjaxResponse(false,"Error retrieving form details");
        }
        $reviewData = json_decode($content,true);

        $cr = new CredentialReview();
        $cr->setCredential( $credential );

        // check if the rating valid
        if(!isset($reviewData['rating']) &&  !is_numeric($reviewData['rating']))
        {
            return UniversalHelper::getAjaxResponse(false,'Rating is required and expected to be a number');
        }

        // Check if the rating is in range
        if(!($reviewData['rating'] >= 1 && $reviewData['rating'] <= 5))
        {
            return UniversalHelper::getAjaxResponse(false,'Rating should be between 1 to 5');
        }
        $cr->setRating( $reviewData['rating'] );

        // If review exists its length should be atleast 20 words
        if(!empty($reviewData['reviewText']) && str_word_count($reviewData['reviewText']) < 20)
        {
            return UniversalHelper::getAjaxResponse(false,'Review should be at least 20 words long');
        }
        $cr->setText( $reviewData['reviewText'] );

        // If Review exist so does title
        if( !empty($reviewData['reviewText']) && empty($reviewData['title']))
        {
            return UniversalHelper::getAjaxResponse(false,"Title cannot be empty");
        }
        $cr->setTitle( $reviewData['title'] );

        // Progress is mandatory
        if( empty($reviewData['progress']) )
        {
            return UniversalHelper::getAjaxResponse(false, "Progress cannot be empty" );
        }
        $progress = $reviewData['progress'];
        if( in_array($progress, CredentialReview::$progressListDropdown) )
        {
            $cr->setProgress( $progress );
        }
        else
        {
            $cr->setDateCompleted( new \DateTime($progress) );
            $cr->setProgress( CredentialReview::PROGRESS_TYPE_COMPLETED );
        }

        // Link
        $cr->setLink( $reviewData['certificateLink'] );

        $em->persist( $cr );
        $em->flush();

        return UniversalHelper::getAjaxResponse(true, $cr->getId() );

    }
}