<?php

require_once 'HTTP/OAuth.php';
require_once 'Services/SimpleGeo/Exception.php';

class Services_SimpleGeo 
{
    /**
     */
    private $api = 'http://api.simplegeo.com';

    /**
     * OAuth client
     *
     * @var object $oauth Instance of OAuth client
     * @see HTTP_OAuth_Consumer
     */
    private $oauth = null;

    /**
     * Constructor
     *
     * @var string $token  Your OAuth token
     * @var string $secret Your OAuth secret
     *
     * @return void
     * @see HTTP_OAuth_Consumer
     */
    public function __construct($token, $secret)
    {
        $this->oauth = new HTTP_OAuth_Consumer($token, $secret);
    }

    /**
     * Reverse geocode a lat/lon to an address
     *
     * @var float $lat Latitude
     * @var float $lon Longitude
     *
     * @return mixed
     */
    public function getAddress($lat, $lon)
    {
        try {
            $result = $this->oauth->sendRequest('/nearby/address/' . $lat .
                ',' . $lon);
        } catch (HTTP_OAuth_Exception $e) {
            throw new Services_SimpleGeo_Exception($e->getMessage());
        }
    }


    /**
     * Send a request to the API
     *
     * @var string $endpoint Relative path to endpoint
     * @var array  $args     Additional arguments passed to HTTP_OAuth
     * @var string $method   HTTP method to use
     * 
     * @return mixed
     * @see HTTP_OAuth_Consumer::sendRequest()
     */
    private function _sendRequest($endpoint, $args = array(), $method = 'GET')
    {
        $url = $this->api . $endpoint;
        return $this->oauth->sendRequest($url, $args, $method);
    }
}

?>
