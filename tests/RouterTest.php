<?php
/**
 * Copyright (c) Christopher Keefer, 2014. See LICENSE distributed with this software
 * for full license terms and conditions.
 *
 * Test router functionality.
 */
use \sanemethod\kiskit\system\core\Router;
class RouterTest extends PHPUnit_Framework_TestCase{

    /**
     * Test that an exception is thrown when a request fails to resolve to a route.
     * @expectedException Exception
     * @expectedExceptionCode 404
     */
    public function testRouteNotFound(){
        $_SERVER = array_merge($_SERVER, mock::request());
        new Router();
    }

    /**
     * Test that an attempt to traverse up from the conhome dir throws an exception.
     * @expectedException Exception
     * @expectedExceptionCode 403
     */
    public function testDirTraversal(){
        $_SERVER = array_merge($_SERVER, mock::request(array('REQUEST_URI'=>'..\system\cli\setup')));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that an attempt to invoke a non-public function throws an exception.
     * @expectedException Exception
     * @expectedExceptionCode 403
     */
    public function testNotPublic(){
        $_SERVER = array_merge($_SERVER, mock::request(array('REQUEST_URI'=>'mock/hidden')));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that a valid file router without a valid controller class throws an exception.
     * @expectedException Exception
     * @expectedExceptionCode 404
     */
    public function classNotFound(){
        $_SERVER = array_merge($_SERVER, mock::request(array('REQUEST_URI'=>'bootstrap')));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that a valid route without a function argument resolves to the index() of the called class.
     */
    public function testRouteIndex(){
        $this->expectOutputString('index');
        $_SERVER = array_merge($_SERVER, mock::request(array('REQUEST_URI'=>'mock')));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that a valid route with a function argument resolves to that function.
     */
    public function testRouteFunction(){
        $this->expectOutputString('mock');
        $_SERVER = array_merge($_SERVER, mock::request(array('REQUEST_URI'=>'mock/mock')));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that a postfixed route is being properly routed to based on the request verb.
     */
    public function testRouteGet(){
        $this->expectOutputString('testGET');
        $_SERVER = array_merge($_SERVER, mock::request(
            array('REQUEST_URI'=>'mock/test', 'REQUEST_METHOD'=>'GET')
        ));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that a postfixed route is being properly routed to based on the request verb.
     */
    public function testRoutePOST(){
        $this->expectOutputString('testPOST');
        $_SERVER = array_merge($_SERVER, mock::request(
            array('REQUEST_URI'=>'mock/test', 'REQUEST_METHOD'=>'POST')
        ));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that a postfixed route is being properly routed to based on the request verb.
     */
    public function testRoutePUT(){
        $this->expectOutputString('testPUT');
        $_SERVER = array_merge($_SERVER, mock::request(
            array('REQUEST_URI'=>'mock/test', 'REQUEST_METHOD'=>'PUT')
        ));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that a postfixed route is being properly routed to based on the request verb.
     */
    public function testRouteDELETE(){
        $this->expectOutputString('testDELETE');
        $_SERVER = array_merge($_SERVER, mock::request(
            array('REQUEST_URI'=>'mock/test', 'REQUEST_METHOD'=>'DELETE')
        ));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that a postfixed route is being properly routed to based on the request verb.
     */
    public function testRouteHEAD(){
        $this->expectOutputString('testHEAD');
        $_SERVER = array_merge($_SERVER, mock::request(
            array('REQUEST_URI'=>'mock/test', 'REQUEST_METHOD'=>'HEAD')
        ));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that a postfixed route is being properly routed to based on the request verb.
     */
    public function testRouteOPTIONS(){
        $this->expectOutputString('testOPTIONS');
        $_SERVER = array_merge($_SERVER, mock::request(
            array('REQUEST_URI'=>'mock/test', 'REQUEST_METHOD'=>'OPTIONS')
        ));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that arguments passed on the uri are available to the controller function as its parameters,
     * and only those that fit the number of parameters are being set, in order.
     */
    public function testUriArgs(){
        $this->expectOutputString('13');
        $_SERVER = array_merge($_SERVER, mock::request(array('REQUEST_URI'=>'mock/mockArgs/1/3/2')));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test that arguments named in the query string are assigned to the named parameters in the function
     * preferentially.
     */
    public function testNamedArgs(){
        $this->expectOutputString('weeblewobble');
        $_SERVER = array_merge($_SERVER, mock::request(
            array('REQUEST_URI'=>'mock/mockArgs?arg1=weeble&55&arg2=wobble',
                'QUERY_STRING'=>'arg1=weeble&55&arg2=wobble')
        ));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
    }

    /**
     * Test the contents of request->get.
     */
    public function testGetContents(){
        ob_start();
        $_SERVER = array_merge($_SERVER, mock::request(
            array('REQUEST_URI'=>'mock/mockGet/5/6/7?8&arg1=9', 'QUERY_STRING'=>'8&arg1=9')
        ));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
        $json = ob_get_clean();
        $this->assertJsonStringEqualsJsonString('{"0":"5","1":"6","2":"7","3":"8","arg1":"9"}', $json);
    }

    /**
     * Test the contents of request->post.
     */
    public function testPostContents(){
        ob_start();
        $_POST = json_encode(array(5,6,7,8,'arg1'=>9));
        $_SERVER = array_merge($_SERVER, mock::request(
            array('REQUEST_URI'=>'mock/mock', 'REQUEST_METHOD'=>'POST', 'CONTENT_TYPE'=>'application/json')
        ));
        new Router(array('conhome'=>SERVER_ROOT.'tests/'));
        $json = ob_get_clean();
        $this->assertJsonStringEqualsJsonString('{"0":"5","1":"6","2":"7","3":"8","arg1":"9"}', $json);
    }
} 