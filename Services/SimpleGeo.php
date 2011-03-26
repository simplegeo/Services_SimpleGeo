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
     * Valid days of the week
     *
     * @var array $_days Valid days
     * @see Services_SimpleGeo::getDensity()
     */
    static private $_days = array('mon', 'wed', 'tue', 'thu', 'fri', 
        'sat', 'sun');

    /**
     * API URL.
     *
     * @var string $_api The API URL to use
     */
    private $_api;

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
     * @param string $token  Your OAuth token
     * @param string $secret Your OAuth secret
     * @param string $url    API URL (optional).
     *
     * @return void
     * @see HTTP_OAuth_Consumer
     */
    public function __construct($token, $secret, $url = 'http://api.simplegeo.com')
    {
        $this->_oauth  = new HTTP_OAuth_Consumer($token, $secret);
        $this->_token  = $token;
        $this->_secret = $secret;
        $this->_api    = $url;
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
        $version = '0.1';
        return $this->_sendRequest(
            $version . '/records/' . $layer . '/' . $id . '.json'
        );
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
        $version = '0.1';
        return $this->_sendRequest(
            $version . '/records/' . $layer . '/' . implode(',', $ids) . '.json'
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
        $version = '0.1';
        return $this->_sendRequest(
            $version . '/records/' . $layer . '/' . $id . '/history.json', $args
        );
    }

    /**
     * Get nearby points
     *
     * @param string $layer The layer the record belongs to
     * @param string $arg  Either 'lat,lon', 'geohash', or 'IP address'
     * @param array  $args GET arguments for query
     *
     * @return array
     */
    public function getNearby($layer, $arg, array $args = array())
    {
        $version = '0.1';
        return $this->_sendRequest(
            $version . '/records/' . $layer . '/nearby/' . $arg . '.json', $args
        );
    }

    /**
     * Get layers from a user
     *
     * @return string
     */
    public function getLayers()
    {
        $version = '0.1';
        return $this->_sendRequest(
            $version . '/layers.json'
        );
    }

    /**
     * Get details about a layer
     *
     * @return string
     */
    public function getLayerDetails($layer)
    {
        $version = '0.1';
        return $this->_sendRequest(
            $version . '/layers/' . $layer . '.json'
        );
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
        $version = '0.1';
        $endpoint = $version . '/records/' . $rec->layer . '/' . $rec->id . '.json';
        $url = $this->_getURL($endpoint);

        $result = $this->_sendRequestWithBody($url, (string)$rec);
        return ($result->getStatus() === 202);
    }

    /**
     * Delete a record from the API
     *
     * @param string $layer The layer the record belongs to
     * @param string $id    The unique id of the record in the layer
     *
     * @return boolean Returns true on success
     */
    public function deleteRecord($layer, $id)
    {
        $version = '0.1';
        $result = $this->_sendRequest(
            $version . '/records/' . $layer . '/' . $id . '.json', array(), 'DELETE'
        );

        return ($result === null);
    }

    /**
     * Add multiple records in a single call
     *
     * @param stirng $layer   The layer to POST the records to
     * @param array  $records An array of {@link Services_SimpleGeo_Record}
     *
     * @see Services_SimpleGeo::addRecord()
     * @see Services_SimpleGeo_Record
     * @return boolean
     */
    public function addRecords($layer, array $records)
    {
        $body = array(
            'type' => 'FeatureCollection',
            'features' => array()
        );

        foreach ($records as $rec) {
            if (!$rec instanceof Services_SimpleGeo_Record) {
                throw new Services_SimpleGeo_Exception(
                    'Records must be instances of Services_SimpleGeo_Record'
                );
            }

            $body['features'][] = $rec->toArray();
        }

        $version = '0.1';
        $endpoint = $version . '/records/' . $layer . '.json';

        $result = $this->_sendRequestWithBody(
            $this->_getURL($endpoint),
            json_encode($body), "POST"
        );

        return ($result->getStatus() === 202);
    }

    /**
     * Do a SimpleGeo Context query with a coordinate
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     *
     * @return mixed
     */
    public function getContext($lat, $lon)
    {
        $version = '1.0';
        return $this->_sendRequest(
            $version . '/context/' . $lat . ',' . $lon . '.json'
        );
    }

    /**
     * Do a SimpleGeo Context query with an address
     *
     * @param string $address Human-readable address
     *
     * @return mixed
     */
    public function getContextFromAddress($address)
    {
        $version = '1.0';
        return $this->_sendRequest(
            $version . '/context/address.json', array("address" => $address)
        );
    }

    /**
     * Do a SimpleGeo Context query with an IP.
     * If you don't pass anything, it uses the requesting IP (which will be
     * your server).
     *
     * @param string $ip IP address
     *
     * @return mixed
     */
    public function getContextFromIPAddress($ip='ip')
    {
        $version = '1.0';
        return $this->_sendRequest(
            $version . '/context/' . $ip . '.json'
        );
    }

    /**
     * Add a place
     *
     * @param array   $feature Nested array representation of a GeoJSON Feature.
     * @param boolean $private Whether this feature should be private (not
     *                         submitted for inclusion); defaults to false.
     *
     * @return array Hash containing the generated handle ("handle") and
     *               a status token ("token").
     */
    public function addPlace(array $feature, $private = false)
    {
        $version = '1.0';
        $result = $this->_sendRequestWithBody(
            $version . '/places', json_encode($feature), 'POST'
        );

        return @json_decode($result->getBody());
    }

    /**
     * Delete a place
     *
     * @param string $handle Feature handle.
     *
     * @return boolean Returns true on success
     */
    public function deletePlace($handle) {
        $version = '1.0';
        $result = $this->_sendRequest(
            $version . '/features/' . $handle . '.json', array(), 'DELETE'
        );

        return ($result === null);
    }

    /**
     * Edit a place
     *
     * @param array   $feature Nested array representation of a partial GeoJSON
     *                         Feature with just the fields present that you
     *                         wish to change.
     * @param boolean $private Whether these changes should be private (not
     *                         submitted for inclusion); defaults to false.
     *
     * @return array Hash containing the generated handle ("handle") and
     *               a status token ("token").
     */
    public function editPlace($handle, array $feature, $private = false)
    {
        $version = '1.0';
        $result = $this->_sendRequestWithBody(
            $version . '/places/' . $handle . '.json', json_encode($feature), 'POST'
        );

        return @json_decode($result->getBody());
    }

    /**
     * Do a nearby search for SimpleGeo Places
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param array $args Arguments (e.g. 'q' => 'Search Query', 'category' =>
     * 'Search Category', 'radius' => 'Search Radius (km)')
     *
     * @return mixed
     */
    public function getPlaces($lat, $lon, array $args = array())
    {
        $version = '1.0';
        return $this->_sendRequest(
            $version . '/places/' . $lat . ',' . $lon . '.json', $args
        );
    }

    /**
     * Do a nearby search for SimpleGeo Places
     *
     * @param string $address Address
     * @param array $args Arguments (e.g. 'q' => 'Search Query', 'category' =>
     * 'Search Category', 'radius' => 'Search Radius (km)')
     *
     * @return mixed
     */
    public function getPlacesNearAddress($address, array $args = array())
    {
        $args['address'] = $address;

        $version = '1.0';
        return $this->_sendRequest(
            $version . '/places/address.json', $args
        );
    }

    /**
     * Do a nearby search for SimpleGeo Places
     *
     * @param string $ip IP Address
     * @param array $args Arguments (e.g. 'q' => 'Search Query', 'category' =>
     * 'Search Category', 'radius' => 'Search Radius (km)')
     *
     * @return mixed
     */
    public function getPlacesNearIPAddress($ip, array $args = array())
    {
        // if $ip was null, use the magic string "ip" which causes SimpleGeo to
        // use the IP address of the requester
        if (!$ip) {
            $ip = 'ip';
        }

        $version = '1.0';
        return $this->_sendRequest(
            $version . '/places/' . $ip . '.json', $args
        );
    }

    /**
     * Return information about a SimpleGeo feature
     *
     * @param string $handle A SimpleGeo handle
     *
     * @return mixed
     */
    public function getFeature($handle)
    {
        $version = '1.0';
        return $this->_sendRequest(
            $version . '/features/' . $handle . '.json'
        );
    }

    /**
     * Get the density of a given point
     *
     * If you do not provide a $day then the current day will be
     * used. You must provide an hour if you wish to query a 
     * specific day/hour combination.
     *
     * @param float  $lat  The latitude of the point
     * @param float  $lon  The longitude of the point
     * @param string $day  The day of the week (defaults to today)
     * @param string $hour The hour of the day (0 - 23)
     *
     * @throws {@link Services_SimpleGeo_Exception} on API error
     * @return array
     */
    public function getDensity($lat, $lon, $day = null, $hour = null)
    {
        if ($day === null) {
            $day = strtolower(date("D"));
        } elseif (!in_array($day, self::$_days)) {
            throw new Services_SimpleGeo_Exception(
                $day . ' is not a valid day of the week (e.g. mon).'
            );
        }
        
        $version = '0.1';

        if ($hour === null) {
            $endpoint = $version . '/density/' . $day . '/' . $lat . ',' . $lon . '.json';
        } else {
            if ($hour < 0 || $hour > 23) {
                throw new Services_SimpleGeo_Exception(
                    'Hour must be between 0 and 23.'
                );
            }

            $endpoint = $version . '/density/' . $day . '/' . $hour . '/' . $lat . ',' .
                $lon . '.json';
        }

        return $this->_sendRequest($endpoint);
    }

    /**
     * Send an OAuth signed request with a body to the API
     *
     * @param string $url    The URL to send the request to
     * @param string $body   The raw body to PUT/POST to the URL
     * @param string $method The HTTP method to use (POST or PUT)
     *
     * @return object Instance of {@link HTTP_Request2_Response}
     * @see http://bit.ly/cdZGfr
     */
    private function _sendRequestWithBody($url, $body, $method="PUT")
    {
        static $map = array(
            'PUT'  => HTTP_Request2::METHOD_PUT,
            'POST' => HTTP_Request2::METHOD_POST
        );

        if (array_key_exists($method, $map)) {
            $method = $map[$method];
        } else {
            throw new Services_SimpleGeo_Exception(
                'Invalid HTTP method ' . $method
            );
        }

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
            $method, $url, $params, $this->_secret
        );

        // Build the header
        $header = 'OAuth realm="' . $this->_api . '"';
        foreach ($params as $name => $value) {
            $header .= ", " . HTTP_OAuth::urlencode($name) . '="' .
                HTTP_OAuth::urlencode($value) . '"';
        }

        $req = new HTTP_Request2(new Net_URL2($url), $method);
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

        throw new Services_SimpleGeo_Exception($body->message, 
            $result->getStatus());
    }

    /**
     * Construct an API URL
     *
     * @param string $endpoint The relative path for the endpoint
     *
     * @return string
     * @see Services_SimpleGeo::$_api
     */
    private function _getURL($endpoint)
    {
        return $this->_api . '/' . $endpoint;
    }
}

?>
