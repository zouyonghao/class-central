<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/10/17
 * Time: 1:19 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Utility\UniversalHelper;
use Guzzle\Http\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsController extends Controller
{
    const MAILGUN_EVENT_LIST = [
        'dropped', 'bounced','complained','unsubscribed','clicked','opened'
    ];

    const KEEN_MAILGUN_COLLECTION = 'mailgun_events';

    /**
     * Receive a webhook call from mailgun and log it to Keen
     * @param $collection
     */
    public function mailgunWebhookReceiverAction(Request $request)
    {
        $event = $request->request->get('event');

        // Check if the event is a valid key
        if( !in_array($event,self::MAILGUN_EVENT_LIST) )
        {
            return UniversalHelper::getQuickResponse( $event . ': not an approved event', 406);
        }

        // Verify of the request.
        $apiKey = $this->container->getParameter('mailgun_api_key');
        $token = $request->request->get('token');
        $signature = $request->request->get('signature');
        $timestamp = $request->request->get('timestamp');

        if(!$this->verifyMailgunWebhook($apiKey,$token,$timestamp,$signature))
        {
            return UniversalHelper::getQuickResponse($event . ' : could not verify the request', 406);
        }

        // Verify timestamp. Reject the hook its more than 8 hours old
        $currentTimeStamp = time();
        if( ($currentTimeStamp - $timestamp) >= 60*60*8)
        {
            return UniversalHelper::getQuickResponse($event . ' : timestamp has expired', 406);
        }

        // Record the collection in Keen
        $data = $request->request->all();
        if( isset($data['my-custom-data']) )
        {
            $data['my-custom-data'] = json_decode($data['my-custom-data'],true);
        }
        $keenWebhookUrl = $this->getKeenWebhookURL(self::KEEN_MAILGUN_COLLECTION);
        $client = new Client();
        $guzzleRequest = $client->post($keenWebhookUrl,array('content-type' => 'application/json'));
        $guzzleRequest->setBody(json_encode($data));
        $response = $guzzleRequest->send();

        $statusCode = $response->getStatusCode();
        return UniversalHelper::getQuickResponse($event . ' : recorded successfully', $statusCode);
    }

    private function verifyMailgunWebhook($apiKey,$token,$timestamp,$signature)
    {
        $data = $timestamp.$token;
        $computedSignature = hash_hmac('sha256',$data,$apiKey);
        return $computedSignature == $signature;
    }

    private function getKeenWebhookURL($collectionName)
    {
        $projectId = $this->container->getParameter('keen_project_id');
        $apiKey = $this->container->getParameter('keen_write_key');

        return "https://api.keen.io/3.0/projects/$projectId/events/$collectionName?api_key=$apiKey";
    }

    private function getResponseWrapper($message,$statusCode=400)
    {
        return new Response($message,$statusCode);
    }

    /**
     * Generate CTRS for course pages
     * @param Request $request
     */
    public function coursePageGTCRateAction(Request $request)
    {
        $timeFrame = "this_28_days";
        $kc = $this->get('keen')->getClient();
        $courseRepo = $this->getDoctrine()->getEntityManager()->getRepository('ClassCentralSiteBundle:Course');
        $gtcRate = [];

        if($request->query->get('relative'))
        {
            $timeFrame = $request->query->get('relative');
        }

        if($request->query->get('start') && $request->query->get('end'))
        {
            $timeFrame = [
                'start' => $request->query->get('start'),
                'end' => $request->query->get('end')
            ];
        }

        // Get Go To Class Clicks
        $gtc = [];
        $gtcFromKeen = $kc->count('go_to_class_click', [
            'group_by' => ['metadata.course_id'],
            'timeframe' => $timeFrame
        ]);
        foreach ($gtcFromKeen['result'] as $click)
        {
            $gtc[$click['metadata.course_id']] = $click['result'];
        }

        // Get pageviews
        $pageviewsFromKeen = $kc->count('pageview',[
            'group_by' => ['metadata.course_id'],
            'timeframe' => $timeFrame,
            'filters' =>  [["operator"=>"eq","property_name" => "page","property_value"=> "course"]],
        ]);

        foreach ($pageviewsFromKeen['result'] as $pv)
        {
            $courseId = $pv['metadata.course_id'];
            $pageviews = $pv['result'];
            if(isset($gtc[$courseId]) && $pageviews > 300)
            {
                $clicks = $gtc[$courseId];
                $course = $courseRepo->find($courseId);
                $gtcRate[$courseId] = [
                    'gtc' => $clicks,
                    'pageviews' => $pageviews,
                    'gtcRate' => $clicks*100/$pageviews,
                    'name' => $course->getName()
                ];
            }

        }

        uasort($gtcRate,function($course1, $course2){
            return $course1['pageviews'] < $course2['pageviews'];
        });

        return $this->render('ClassCentralSiteBundle:Analytics:gtc_rate.html.twig',[
            'gtcRate' => $gtcRate
        ]);
    }
}