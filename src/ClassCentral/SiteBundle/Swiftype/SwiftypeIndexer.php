<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 12/15/13
 * Time: 5:13 PM
 */

namespace ClassCentral\SiteBundle\Swiftype;

/**
 * Creates/Updates the swiftype index
 * Class SwiftypeIndexer
 * @package ClassCentral\SiteBundle\Swiftype
 */
class SwiftypeIndexer {

    private $token;
    private $engine;

    private $bulkUrlFormat = 'https://api.swiftype.com/api/v1/engines/%s/document_types/%s/documents/%s';

    public function __construct($token, $engine)
    {
        $this->token = $token;
        $this->engine = $engine;
    }

    private function getBulkUrl($docType, $bulkCall)
    {
        return sprintf($this->bulkUrlFormat, $this->engine,$docType, $bulkCall);
    }

    public function bulkCreateOrUpdate($docs, $documentType)
    {
        $url = $this->getBulkUrl($documentType,'bulk_create_or_update');
        return $this->bulkCall($url,$docs);

    }
        

    /**
     * Calls and updates the swiftype indexer
     * @param $url
     * @param $documents
     */
    private function bulkCall($url,$documents)
    {
        $postObj = new \stdClass();
        $postObj->auth_token = $this->token;
        $postObj->documents = $documents;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postObj));
        curl_setopt($ch, CURLOPT_HTTPHEADER,array("Content-Type: application/json"));

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result,true);
    }

} 