<?php
/**
 * Copyright (c) Christopher Keefer 2014. See LICENSE distributed with this software
 * for full license terms and conditions.
 *
 * Offers the http headers, extracted from an array (generally $_SERVER).
 */

namespace sanemethod\kiskit\system\core;


class Headers {
    /**
     * Content types that indicate media we'll want to parse from input.
     * @var array
     */
    public static $mediaType = array(
        'json'=>'application/json',
        'formData'=>'application/x-www-form-urlencoded'
    );
    /**
     * Headers we're interested in that aren't prefixed with X_ or HTTP_
     * @var array
     */
    private $npHeaders = array(
        'AUTH_TYPE',
        'CONTENT_LENGTH',
        'CONTENT_TYPE',
        'PHP_AUTH_DIGEST',
        'PHP_AUTH_PW',
        'PHP_AUTH_USER'
    );
    /**
     * The headers set via getHeaders.
     * @var array
     */
    private $headers = array();

    public function __construct(array $arr = array()){
        $this->getHeaders($arr);
    }

    /**
     * @param array $arr Array from which to extract headers by their key name or prefix.
     * @return array
     */
    private function getHeaders(array $arr)
    {
        foreach($arr as $key => $val){
            $key = strtoupper($key);
            if (strpos($key, 'HTTP_') === 0 || strpos($key, 'X_') === 0 || in_array($key, $this->npHeaders)){
                $this->headers[$key] = preg_split('/\s*[;,]\s*/', $val)[0];
            }
        }
    }

    /**
     * Get the header by key, or null if not set.
     * @param $key
     * @return null|string
     */
    function __get($key){
        if (array_key_exists($key, $this->headers))
        {
            return $this->headers[$key];
        }
        return null;
    }
} 