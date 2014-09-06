<?php
/**
 * Class to contain/generate mock objects.
 */
class mock{
    /**
     * Create a mock request.
     * @param array $opts
     * @return array
     */
    public static function request($opts = array()){
        return array_merge(array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' =>'',
            'SCRIPT_NAME' => '',
            'PATH_INFO' => '',
            'QUERY_STRING' => '',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'USER_AGENT' => 'KisKit',
            'REMOTE_ADDR' => '127.0.0.1'
        ), $opts);
    }
}

/**
 * Class to exercise the Controller portion of tests.
 * Class MockController
 */
class MockController extends \sanemethod\kiskit\system\core\Controller{
    public function index(){
        echo 'index';
    }

    public function test_GET(){
        echo 'test'.$this->request->verb;
    }

    public function test_POST(){
        echo 'test'.$this->request->verb;
    }

    public function test_PUT(){
        echo 'test'.$this->request->verb;
    }

    public function test_DELETE(){
        echo 'test'.$this->request->verb;
    }

    public function test_HEAD(){
        echo 'test'.$this->request->verb;
    }

    public function test_OPTIONS(){
        echo 'test'.$this->request->verb;
    }

    public function mock(){
        echo 'mock';
    }

    public function mockArgs($arg1 = null, $arg2 = 'arg2'){
        echo $arg1.$arg2;
    }

    public function mockGet($arg1 = null){
        $get = $this->request->get;
        echo json_encode($get);
    }

    public function mock_POST(){
        $post = $this->request->post;
        echo $post;
    }

    public function mock_PUT(){
        $put = $this->request->put;
        echo json_encode($put);
    }

    protected function hidden(){
        // This should never be callable via the router, but only internally to the controller class.
    }
}