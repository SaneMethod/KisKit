<?php
/**
 * Copyright (c) Christopher Keefer 2014. See LICENSE distributed with this software
 * for full license terms and conditions.
 *
 * Performs routing for page requests.
 */
namespace sanemethod\kiskit\system\core;

use \Exception;
use \ReflectionMethod;

class Router{

    private $options;

    function __construct($options = array()){
        $this->setOptions($options);

        $request = new Request();
        $this->route($request);
    }

    /**
     * Set options for the router, declaring defaults and overriding if the appropriately named
     * option is found in $options.
     * @param $options
     */
    function setOptions($options)
    {
        $this->options = array_merge(array(
            '404'=>null,
            '403'=>null,
            'conhome'=>SERVER_ROOT . APP_DIR . 'controllers/',
            'conPostfix'=>'Controller',
            'debug'=>true,
            'logger'=>null
        ), $options);
    }

    /**
     * Get the router options array.
     * @return mixed
     */
    function getOptions(){
        return $this->options;
    }

    /**
     * Call passed function of passed controller, if existent - otherwise, throw an exception.
     * @param $request
     * @throws Exception
     */
    function route(Request $request)
    {
        $conhome = $this->options['conhome'];
        $conPostfix = $this->options['conPostfix'];
        $target = $conhome . $request->target . '.php';
        $logger = $this->options['logger'];
        $classTarget = null;
        $controller = null;
        $reflection = null;

        if (file_exists($target))
        {
            // Prevent directory traversal by comparing the real path to this file to
            // $conhome - if the root isn't the same, disallow
            $rp = str_replace("\\", "/", realpath($target));
            if (strlen($rp) > strlen($conhome) &&
                strcmp($conhome, substr($rp, 0, strlen($conhome))) === 0)
            {
                include_once($target);
            }
            else
            {
                throw new Exception('Security Exception, attempt to traverse directories.', 403);
            }
        }
        else
        {
            throw new Exception('404 Not Found: '.$target, 404);
        }

        $classTarget = $request->controller.$conPostfix;

        if (class_exists($classTarget))
        {
            $controller = new $classTarget;
            $controller->request =& $request;
            $controller->logger =& $logger;
        }
        else
        {
            throw new Exception('404 Not Found', 404);
        }

        // First, check the controller for the named method in the request, postfixed with an underscore and the
        // request method (ie. _GET, _POST, etc). Case matters.
        if (isset($request->method) && method_exists($controller, $request->method . '_' . $request->verb))
        {
            $reflection = new ReflectionMethod($controller, $request->method . '_' . $request->verb);
        }
        // Otherwise, reflect the named method as is.
        else if (isset($request->method) && method_exists($controller, $request->method))
        {
            $reflection = new ReflectionMethod($controller, $request->method);
        }
        // If named method isn't found, method may actually be a parameter to the index function.
        // Unshift $request->get with the method name (if any) and call the controller index.
        else
        {
            if (isset($request->method)) array_unshift($request->get, $request->method);
            $reflection = new ReflectionMethod($controller, 'index');
        }

        // If method is not public, throw a 403.
        if (!$reflection->isPublic()) {
            throw new Exception("The called method is not public.", 403);
        }

        // Build parameters on request and invoke the reflected method.
        $request->buildParams($reflection);
        $reflection->invokeArgs($controller, $request->params);
    }

    /**
     * Determine routes when an exception occurs in the route function.
     * @param $e Exception
     */
    function routeError($e)
    {
        switch ($e->getCode())
        {
            case 403:
                (isset($this->options['403'])) ?
                    $this->route($this->options['403']) : $this->default403($e);
                break;
            case 404:
                (isset($this->options['404'])) ?
                    $this->route($this->options['404']) : $this->default404($e);
                break;
        }
    }

    /**
     * Display default 404 view
     * @param $e Exception
     */
    function default404($e){
        header("HTTP/1.1 404 Not Found");
        echo "<h1>404 Not Found</h1>";
        echo "The page that you have requested could not be found.";
        if ($this->options['debug']) echo "Error Message: ".$e->getMessage();
    }

    /**
     * Display default 403 view
     * @param $e Exception
     */
    function default403($e)
    {
        header("HTTP/1.1 403 Forbidden");
        echo "<h1>403 Forbidden</h1>";
        if ($this->options['debug']) echo "Error Message: ".$e->getMessage();
    }
}