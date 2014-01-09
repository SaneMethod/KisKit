<?php
/**
 * Copyright (c) Christopher Keefer, 2013. All Rights Reserved.
 *
 * Base Controller class, from which all other controllers should extend. Contains functions for requesting
 * models and views.
 */

namespace sanemethod\kiskit\system\core;

use \ReflectionClass;

/**
 * Class Controller
 * @package sanemethod\kiskit\system\core
 * @property \Psr\Log\LoggerInterface $logger PSR-3 compliant logger injected by Router.
 */
class Controller {

    const VIEW_LOC = '';
    public $request = null;

    /**
     * Load the specified view, extracting the passed array as local variables if present.
     * @param string $view Name of the view to load. Can specify the view sub-directory, if different from
     * VIEW_LOC, by including a forward slash in between the dir and the view file name -
     * ie. home/index (for index.php in directory 'views/home/'), or /index (for index.php in directory 'views/').
     * @param null|array $localVars Array of values to extract into the symbol table for use in the view.
     */
    protected function view($view, $localVars = null)
    {
        $view = $this->determinePath($view, static::VIEW_LOC);
        $target = SERVER_ROOT . APP_DIR . 'views/' . $view;
        if (file_exists($target))
        {
            if ($localVars) extract($localVars, EXTR_SKIP);
            include_once($target);
        }
    }

    /**
     * Load the specified helper class.
     * @param string $helper Name of the helper to instantiate.
     * @return object|bool Returns reference to class if helper is found, false otherwise.
     */
    protected function helper($helper){
        return $this->load($helper, array('prefix'=>SERVER_ROOT . APP_DIR . 'helpers/', 'postfix'=>'Helper'),
            array_slice(func_get_args(), 1));
    }

    /**
     * Load the specified model.
     * @param $model String Name of the model to instantiate
     * @return object|bool Returns reference to class if model construction succeeds, false otherwise.
     */
    protected function model($model){
        return $this->load($model, array('prefix'=>SERVER_ROOT . APP_DIR . 'models/', 'postfix'=>'Model'),
            array_slice(func_get_args(), 1));
    }

    /**
     * Load an arbitrary file and an arbitrarily named class from within said file.
     *
     * @param $path
     * @param array $options {
     *      @type string $prefix File prefix (directory/file location).
     *      @type string $postfix Class postfix, if any.
     *      @type string $className Explicit className, will not append postfix.
     * }
     * @param array $args Array of arguments to pass to the new class, as standard arguments (ie. not as a single
     * array, but as individual arguments - if you want to pass an array as an argument, you'll need to pass a multi-
     * dimensional array).
     * @return object|bool
     */
    protected function load($path, $options = array(), $args = array()){
        $prefix = (isset($options['prefix'])) ? $options['prefix'] : '';
        $postfix = (isset($options['postfix'])) ? $options['postfix'] : '';
        $target = $this->determinePath($path, $prefix);
        $classTarget = (isset($options['className'])) ? $options['className'] : $this->determineClass($path, $postfix);

        if (file_exists($target))
        {
            include_once($target);
        }
        if (class_exists($classTarget))
        {
            $reflect = new ReflectionClass($classTarget);
            $reflect = $reflect->newInstanceArgs($args);
            $reflect->logger =& $this->logger;
            return $reflect;
        }
        return false;
    }

    /**
     * Load the specified library, conforming to psr-0/psr-4 (see http://www.php-fig.org/psr/psr-0/). We rely
     * on the Composer auto class loader here, which makes this just a somewhat expensive (due to the
     * use of Reflection) alias for something like:
     * new Monolog\Formatter\JsonFormatter($arg1, $arg2).
     *
     * @param string $lib Fully qualified name of the library (ie. Monolog\Formatter\JsonFormatter)
     * @return object|false
     */
    protected function lib($lib){
        $args = (func_num_args() > 1) ? array_slice(func_get_args(), 1) : null;
        if ($lib){
            $reflect = new ReflectionClass($lib);
            return $reflect->newInstanceArgs($args);
        }
        return false;
    }

    /**
     * Echo a json encoded value.
     * @see JsonSerializable::json_encode()
     *
     * @param mixed $val
     * @param int $options
     */
    protected function json($val, $options = 0){
        $this->sendHeaders('json');
        echo json_encode($val, $options);
    }

    /**
     * Offer a file (as found in $path) for download.
     *
     * @param string $path
     * @param string $filename A new name for the file, if desired.
     */
    protected function file($path, $filename = null){
        $fileHelper = $this->helper('file');
        $fileHelper->offerDownload($path, $filename);
    }

    /**
     * Handle a (potentially chunked) upload.
     *
     * @param null|string $filename
     * @param null|string $upload_dir
     * @param string $returnType
     */
    protected function upload($filename = null, $upload_dir = null, $returnType = 'application/json'){
        $fileHelper = $this->helper('file');
        $hupReturn = $fileHelper->handleUpload($filename, $upload_dir, $returnType);
        $this->json($hupReturn);
    }

    /**
     * Determine path for file (view, model, helper, etc.) to include, based on $path passed and, optionally,
     * $defaultPrefix.
     *
     * @param string $path Relative path to file, based on type of file (view, helper, etc.) being loaded.
     * @param string $prefix Default directory to search for file in $path - only used if $path doesn't contain
     * any forward slashes '/', which indicate a full directory listing.
     * @return string Relative path to file.
     */
    private function determinePath($path, $prefix = ''){
        if (strpos($path, '/') === false)
        {
            if ($prefix)
            {
                $path = rtrim($path, '/');
                if ($path) $path = $prefix . '/' . $path;
            }
        }
        else
        {
            $path = rtrim($path, '/');
        }
        return $path.'.php';
    }

    /**
     * Determine class name from the $path string.
     *
     * @param string $path
     * @param string $postFix String to append to the end of the class name.
     * @return string
     */
    private function determineClass($path, $postFix = ''){
        $className = substr($path, (int)strrpos($path, '/'),
            ((strrpos($path, '.') === false) ? strlen($path) : strrpos($path, '.')));
        return ucfirst($className).$postFix;
    }

    private function sendHeaders($type){
        switch($type)
        {
            case 'json':
                header('Content-Type: application/json');
                header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT');
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
                break;
        }

    }
} 