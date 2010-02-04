<?php

require_once 'HTTP/OAuth/Consumer.php';
require_once 'Services/SimpleGeo/Exception.php';

/**
 * A simple interface to SimpleGeo API
 *
 * @author Joe Stump <joe@simplegeo.com>
 */
class Services_SimpleGeo 
{
    /**
     * Version of the API to use
     *
     * @var string $version The version of the API to use
     */
    private $version = '1.0';

    /**
     * Base URI of the API
     *
     * @var string $api The base URI for the SimpleGeo API
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
    public function __construct($token, $secret, $version = '1.0')
    {
        $this->oauth   = new HTTP_OAuth_Consumer($token, $secret);
        $this->version = $version;
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
            return $this->_sendRequest('/nearby/address/' . $lat . ',' . 
                $lon . '.json');
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
        $url    = $this->api . '/' . $this->version . $endpoint;
        $result = $this->oauth->sendRequest($url, $args, $method);
        $body   = @json_decode($result->getBody());
        if (substr($result->getStatus(), 0, 1) == '2') {
            return $body;
        }
    }
}

?>
