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
    public $layer;
    public $id;
    public $lat;
    public $lon;
    public $type = 'object';
    public $created = 0;
    private $properties = array();

    /**
     * Constructor
     *
     * @var string $layer   Name of layer
     * @var string $id      Unique identifier of record within the layer
     * @var float  $lat     Latitude of point
     * @var float  $lon     Longitude of point
     * @var string $type    Type of record (person, place, object, audio, text,
     *                      video or photo)
     * @var int    $created Unix timestamp of when the object was created
     */
    public function __construct($layer, $id, $lat, $lon, $type = 'object',
        $created = null)
    {
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
     * @var string $var Property name to set
     * @var mixed  $val Property value
     *
     * @return void
     */
    public function __set($var, $val) 
    {
        $this->properties[$var] = $val;
    }

    /**
     * Convert the record into a GeoJSON object
     *
     * @return string
     */
    public function __toString() 
    {
        $ret = array(
            'type'     => 'Feature',
            'id'       => $this->id,
            'created'  => $this->created,
            'geometry' => array(
                'type'        => 'Point',
                'coordinates' => array($this->lon, $this->lat)
            ),
            'properties' => $this->properties
        );


        return json_encode($ret);
    }
}

?>
