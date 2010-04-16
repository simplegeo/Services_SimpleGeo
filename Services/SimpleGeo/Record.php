<?php

/**
 * Services_SimpleGeo_Record
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

/**
 * Services_SimpleGeo_Record
 *
 * @category  Services
 * @package   Services_SimpleGeo
 * @author    Joe Stump <joe@joestump.net>
 * @copyright 2010 Joe Stump <joe@joestump.net>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/Services_SimpleGeo
 * @link      http://github.com/simplegeo/Services_SimpleGeo
 */
class Services_SimpleGeo_Record
{
    /**
     * Layer 
     *
     * @var string $layer Layer name (e.g. com.simplegeo.foobar)
     */
    public $layer;

    /**
     * Unique ID in layer
     *
     * @var string $id The unique ID for the record in the layer
     */
    public $id;

    /**
     * Latitude
     *
     * @var float $lat The latitude of the object
     */
    public $lat;

    /**
     * Longitude 
     *
     * @var float $lat The longitude of the object
     */
    public $lon;

    /**
     * Type of object
     *
     * Must be one of: person, place, object, audio, text, video or photo. You
     * can query by object type as filter.
     * 
     * @var string $type The type of the object
     */
    public $type = 'object';

    /**
     * Timestamp of when record was created
     *
     * Defaults to the current Unix timestamp for the server that you're 
     * sending the requests from.
     * 
     * @var string $created Unix timestamp
     */
    public $created = 0;

    /**
     * Arbitrary properties
     *
     * @var array $_properties Array of arbitrary properties
     */
    private $_properties = array();

    /**
     * Constructor
     *
     * @param string  $layer   Name of layer
     * @param string  $id      Unique identifier of record within the layer
     * @param float   $lat     Latitude of point
     * @param float   $lon     Longitude of point
     * @param string  $type    Type of record 
     * @param integer $created Timestamp of when record was created
     *
     * @var int    $created Unix timestamp of when the object was created
     */
    public function __construct(
        $layer, $id, $lat, $lon, $type = 'object', $created = null
    ) {
        if ($created === null) {
            $created = time();
        }

        $this->layer   = $layer;
        $this->id      = $id;
        $this->lat     = (float)$lat;
        $this->lon     = (float)$lon;
        $this->type    = $type;
        $this->created = $created;
    }

    /**
     * Set a record property
     *
     * @param string $var Property name to set
     * @param mixed  $val Property value
     *
     * @return void
     */
    public function __set($var, $val) 
    {
        $this->_properties[$var] = $val;
    }

    /**
     * Convert the record into a GeoJSON object
     *
     * @return string
     */
    public function __toString() 
    {
        $array = $this->toArray();
        if(!$array['properties']) {
            /* Make sure empty properties encode to an empty object. */
            $array['properties'] = new stdClass;
        }
        return json_encode($array);
    }

    /**
     * Return the record as a plain array
     *
     * @see Services_SimpleGeo_Record::__toString()
     * @return array
     */
    public function toArray()
    {
        return array(
            'type'     => 'Feature',
            'id'       => $this->id,
            'created'  => $this->created,
            'geometry' => array(
                'type'        => 'Point',
                'coordinates' => array($this->lon, $this->lat)
            ),
            'properties' => $this->_properties
        );
    }
}

?>
