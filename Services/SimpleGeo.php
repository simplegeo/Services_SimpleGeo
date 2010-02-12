<?php

/**
 * Services_SimpleGeo
 *
 * Implementation of the OAuth specification
 *
 * PHP version 5.2.0+
 *
 * LICENSE: This source file is subject to the New BSD license that is
 * available through the world-wide-web at the following URI:
 * http://www.opensource.org/licenses/bsd-license.php. If you did not receive
 * a copy of the New BSD License and are unable to obtain it through the web,
 * please send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Services
 * @package   Services_SimpleGeo
 * @author    Joe Stump <joe@joestump.net>
 * @copyright 2010 Joe Stump <joe@joestump.net>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/Services_SimpleGeo
 * @link      http://github.com/simplegeo/Services_SimpleGeo
 */

require_once 'HTTP/OAuth.php';
require_once 'HTTP/OAuth/Consumer.php';
require_once 'HTTP/OAuth/Signature.php';
require_once 'HTTP/Request2.php';
require_once 'Net/URL2.php';
require_once 'Services/SimpleGeo/Exception.php';
require_once 'Services/SimpleGeo/Record.php';

/**
 * Services_SimpleGeo
 *
 * @category  Services
 * @package   Services_SimpleGeo
 * @author    Joe Stump <joe@joestump.net>
 * @copyright 2010 Joe Stump <joe@joestump.net>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/Services_SimpleGeo
 * @link      http://github.com/simplegeo/Services_SimpleGeo
 */
class Services_SimpleGeo
{
    /**
     * Version of the API to use
     *
     * @var string $_version The version of the API to use
     */
    private $_version = '0.1';

    /**
     * Base URI of the API
     *
     * @var string $_api The base URI for the SimpleGeo API
     */
    private $_api = 'http://api.simplegeo.com';

    /**
     * OAuth client
     *
     * @var object $_oauth Instance of OAuth client
     * @see HTTP_OAuth_Consumer
     */
    private $_oauth = null;

    /**
     * API token
     *
     * @var string $_token OAuth token
     */
    private $_token;

    /**
     * API secret
     *
     * @var string $_secret OAuth secret
     */
    private $_secret;

    /**
     * Constructor
     *
     * @param string $token   Your OAuth token
     * @param string $secret  Your OAuth secret
     * @param string $version Which version to use
     *
     * @return void
     * @see HTTP_OAuth_Consumer
     */
    public function __construct($token, $secret, $version = '0.1')
    {
        $this->_oauth   = new HTTP_OAuth_Consumer($token, $secret);
        $this->_version = $version;
        $this->_token   = $token;
        $this->_secret  = $secret;
    }

    /**
     * Reverse geocode a lat/lon to an address
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     *
     * @return mixed
     */
    public function getAddress($lat, $lon)
    {
        return $this->_sendRequest(
            '/nearby/address/' . $lat . ',' . $lon . '.json'
        );
    }

    /**
     * Fetch a single record
     *
     * @param string $layer The layer the record belongs to
     * @param string $id    The unique id of the record in the layer
     *
     * @return array
     */
    public function getRecord($layer, $id)
    {
        return $this->_sendRequest('/records/' . $layer . '/' . $id . '.json');
    }

    /**
     * Fetch multiple records
     *
     * @param string $layer The layer the record belongs to
     * @param array  $ids   A list of unique id's of the records in the layer
     *
     * @return array
     */
    public function getRecords($layer, $ids)
    {
        return $this->_sendRequest(
            '/records/' . $layer . '/' . implode(',', $ids) . '.json'
        );
    }

    /**
     * Get location history of a record
     *
     * @param string $layer The layer the record belongs to
     * @param string $id    The unique id of the record in the layer
     * @param array  $args  Extra arguments for call
     * 
     * @return array
     */
    public function getHistory($layer, $id, array $args = array())
    {
        return $this->_sendRequest(
            '/records/' . $layer . '/' . $id . '/history.json', $args
        );
    }

    /**
     * Get nearby points
     *
     * @param string $arg  Either 'lat,lon' or 'geohash'
     * @param array  $args GET arguments for query
     *
     * @return array
     */
    public function getNearby($arg, array $args = array())
    {
        return $this->_sendRequest('/nearby/' . $arg . '.json', $args);
    }

    /**
     * Add a record to a layer
     *
     * @param object $rec An instance of {@link Services_SimpleGeo_Record}
     *
     * @see Services_SimpleGeo_Record
     * @return boolean
     */
    public function addRecord(Services_SimpleGeo_Record $rec)
    {
        $url = $this->_getURL(
            '/records/' . $rec->layer . '/' . $rec->id . '.json'
        );

        $result = $this->_put($url, (string)$rec);
        return ($result->getStatus() === 202);
    }

    /**
     * Add multiple records in a single call
     *
     * @param array $records An array of {@link Services_SimpleGeo_Record}
     *
     * @see Services_SimpleGeo::addRecord()
     * @see Services_SimpleGeo_Record
     * @return boolean
     */
    public function addRecords(array $records)
    {

    }

    /**
     * Send an OAuth signed PUT request to the API
     *
     * @param string $url  The URL to send the PUT to
     * @param string $body The raw body to PUT to the URL
     *
     * @return object Instance of {@link HTTP_Request2_Response}
     * @see http://bit.ly/cdZGfr
     */
    private function _put($url, $body)
    {
        $signatureMethod = $this->_oauth->getSignatureMethod();
        $params          = array(
            'oauth_nonce'            => (string)rand(0, 100000000),
            'oauth_timestamp'        => time(),
            'oauth_consumer_key'     => $this->_oauth->getKey(),
            'oauth_signature_method' => $signatureMethod,
            'oauth_version'          => '1.0'
        ); 

        $sig = HTTP_OAuth_Signature::factory($signatureMethod);
        $params['oauth_signature'] = $sig->build(
            'PUT', $url, $params, $this->_secret
        );

        // Build the header
        $header = 'OAuth realm="' . $this->_api . '"';
        foreach ($params as $name => $value) {
            $header .= ", " . HTTP_OAuth::urlencode($name) . '="' .
                HTTP_OAuth::urlencode($value) . '"';
        }

        $req = new HTTP_Request2(new Net_URL2($url), HTTP_Request2::METHOD_PUT);
        $req->setHeader('Authorization', $header);
        $req->setBody($body);

        try {
            $result = $req->send();
        } catch (Exception $e) {
            throw new Services_SimpleGeo_Exception($e->getMessage(), 
                $e->getCode());
        }

        $check = (int)substr($result->getStatus(), 0, 1);
        if ($check !== 2) {
            $body = @json_decode($result->getBody());
            throw new Services_SimpleGeo_Exception($body->message, 
                $result->getStatus());
        }

        return $result;
    }

    /**
     * Send a request to the API
     *
     * @param string $endpoint Relative path to endpoint
     * @param array  $args     Additional arguments passed to HTTP_OAuth
     * @param string $method   HTTP method to use
     * 
     * @return mixed
     * @see HTTP_OAuth_Consumer::sendRequest()
     */
    private function _sendRequest($endpoint, $args = array(), $method = 'GET')
    {
        try {
            $result = $this->_oauth->sendRequest(
                $this->_getURL($endpoint), $args, $method
            );
        } catch (HTTP_OAuth_Exception $e) {
            throw new Services_SimpleGeo_Exception($e->getMessage(),
                $e->getCode());
        }

        $body   = @json_decode($result->getBody());
        if (substr($result->getStatus(), 0, 1) == '2') {
            return $body;
        }

        throw new Services_SimpleGeo_Exception($body['message'], 
            $result->getStatus());
    }

    /**
     * Construct an API URL
     *
     * @param string $endpoint The relative path for the endpoint
     *
     * @return string
     * @see Services_SimpleGeo::$_api, Services_SimpleGeo::$_version
     */
    private function _getURL($endpoint)
    {
        return $this->_api . '/' . $this->_version . $endpoint;
    }
}

?>
