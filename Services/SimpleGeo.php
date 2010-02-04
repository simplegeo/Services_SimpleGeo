<?php

require_once 'HTTP/OAuth.php';

class Services_SimpleGeo 
{
    /**
     * OAuth client
     *
     * @var object $oauth Instance of OAuth client
     * @see HTTP_OAuth
     */
    private $oauth = null;

    /**
     * Constructor
     *
     * @var string $token  Your OAuth token
     * @var string $secret Your OAuth secret
     *
     * @return void
     * @see HTTP_OAuth
     */
    public function __construct($token, $secret)
    {
        $this->oauth = new HTTP_OAuth($token, $secret);
    }
}

?>
