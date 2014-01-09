<?php
/**
 * An object representing a request to the server, with the type of request (GET, POST, PUT, etc.), GET and POST
 * variables and other details set.
 */

namespace sanemethod\kiskit\system\core;

use \ReflectionMethod;

class Request{

    public $verb = 'GET';
    public $target = null;
    public $controller = null;
    public $method = null;
    public $params = array();
    public $get = array();
    public $post = array();

    function __construct(){
        $this->verb = $_SERVER['REQUEST_METHOD'];
        $this->get = $this->splitRequest();
        $this->post =& $_POST;
    }

    /**
     * Split the request uri - diff request uri against script name to remove dir and script name; then,
     * first arg will be controller class, second function, third+ arguments.
     * Set controller string (arg0) and, if available, function string (arg1).
     * Expects that arguments will be formatted as /arg0/arg1/arg2?yada=Banana,
     * or as ?arg0&arg1&arg2&yada=Banana.
     *
     * @return array Our arguments to the desired controller[,function] as an array.
     */
    function splitRequest(){
        // Remove the query string from the end of the request uri
        $ruri = (strpos($_SERVER['REQUEST_URI'],'?')) ?
            substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'],'?')) : $_SERVER['REQUEST_URI'];
        $args = array_values(
            array_diff(
                explode('/', $ruri),
                explode('/', $_SERVER['SCRIPT_NAME'])
            )
        );
        // Handle the query string as arguments if set - QUERY_STRING unnamed parameters will be appended to the
        // end of the $args array as incremented numerically keyed. Named parameters will be preserved.
        if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'])
        {
            $args = array_merge($args, $this->parseQuery());
        }
        // If no args are defined, route us to the default controller
        if (!count($args))
        {
            $args[] = DEFAULT_CONTROLLER;
        }

        // Sanitize GET string - if you need to post values outside the allowed characters, use POST/PUT
        preg_replace('/[^a-zA-Z0-9~%.:_-]+/', "", $args);

        // Assign target/controller and, if set, function
        $this->target = strtolower(array_shift($args));
        $this->controller = ucfirst($this->target);
        if (count($args) > 0) $this->method = array_shift($args);

        return $args;
    }

    /**
     * Parse the query string. Assign associations if available, otherwise assign to a numeric index
     * with our args array.
     * @return array Arguments found within query string.
     */
    function parseQuery(){
        $args = array();
        $queries = explode('&', $_SERVER['QUERY_STRING']);
        foreach ($queries as $query)
        {
            $query = explode('=', $query);
            (count($query) > 1) ? $args[$query[0]] = $query[1] : $args[] = $query[0];
        }
        return $args;
    }

    function buildParams(ReflectionMethod $reflection){
        // Line up GET params in uri or query string with named parameters in our
        // controller function, and set both GET and POST params on the controllers
        foreach($reflection->getParameters() as $param)
        {
            $pname = $param->getName();
            $ppos = $param->getPosition();

            if (isset($this->get[$pname]))
            {
                // Named parameters get priority - this allows a url of the form
                // controller/function?name1=arg1&name2=arg2
                // If url is controller/function/arg1?name2=arg2, name2 will be assigned to correct position
                // based on where said name appears in argument list for function
                $this->params[] = $this->get[$pname];
            }
            else if (isset($this->get[$ppos]))
            {
                // Then, unnamed parameters get assigned in order - this allows a url of the form
                // controller/function/arg1/arg2
                // if url is controller/function/arg1?name2=arg2, name2 will still be assigned to correct position
                // based on where said name appears in argument list for function
                $this->params[] = $this->get[$ppos];
            }
            else if ($param->isDefaultValueAvailable())
            {
                // If no argument for this parameter is provided in the url, and
                $this->params[] = $param->getDefaultValue();
            }
        }
    }

} 