<?php
/**
 * Copyright (c) Christopher Keefer, 2014. See LICENSE distributed with this software
 * for full license terms and conditions.
 *
 * Responses for exceptions with appropriate HTTP response codes, and logging.
 */

namespace sanemethod\kiskit\system\core;

use Psr\Log\LoggerInterface;
use \Exception;
use \DateTime;
use \DateTimeZone;

class ExceptHandler extends Controller implements LoggerInterface{

    const VIEW_LOC = 'templates';

    const DEBUG = 100;
    const INFO = 200;
    const NOTICE = 250;
    const WARNING = 300;
    const ERROR = 600;
    const CRITICAL = 700;
    const ALERT = 800;
    const EMERGENCY = 900;

    public static $phpErrMap = array(
        E_ERROR => self::CRITICAL,
        E_WARNING => self::WARNING,
        E_PARSE => self::ALERT,
        E_NOTICE => self::NOTICE,
        E_CORE_ERROR => self::CRITICAL,
        E_CORE_WARNING => self::WARNING,
        E_COMPILE_ERROR => self::ALERT,
        E_COMPILE_WARNING => self::WARNING,
        E_USER_ERROR => self::ERROR,
        E_USER_WARNING => self::WARNING,
        E_USER_NOTICE => self::NOTICE,
        E_STRICT => self::NOTICE,
        E_RECOVERABLE_ERROR => self::ERROR,
        E_DEPRECATED => self::NOTICE,
        E_USER_DEPRECATED => self::NOTICE
    );

    public static $responseCodes = array(
        400 => array('header' => "HTTP/1.1 400 Bad Request",
            'message' => "Request is malformed and cannot be fulfilled."),
        403 => array('header' => "HTTP/1.1 403 Forbidden", 'message' => "Server will not respond to this request."),
        404 => array('header' => "HTTP/1.1 404 Not Found", 'message' => "Requested resources was not found."),
        405 => array('header' => "HTTP/1.1 405 Method Not Allowed",
            'message' => "Requested method is not supported for this resource."),
        418 => array('header' => "HTTP/1.1 418 I'm A Teapot", 'message' => "I'm a teapot, short and stout."),
        500 => array('header' => "HTTP/1.1 500 Internal Server Error",
            'message' => "The server has experienced an internal error."),
        501 => array('header' => "HTTP/1.1 501 Not Implemented",
            'message' => "Requested method is unavailable or not recognized."),
        502 => array('header' => "HTTP/1.1 502 Bad Gateway",
            'message' => "Server failed to fulfill request while acting as gateway."),
        503 => array('header' => "HTTP/1.1 503 Service Unavailable", 'message' => "Server is currently offline.")
    );

    protected static $levels = array(
        100 => 'DEBUG',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARNING',
        600 => 'ERROR',
        700 => 'CRITICAL',
        800 => 'ALERT',
        900 => 'EMERGENCY',
    );

    protected static $timezone;
    
    private static $fatalPHPErr = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);

    /**
     * Set handler levels from logconfig, overriding with any values set in options.
     * Set exception, error and fatal error handlers to ensure we can log on these events.
     * @param array $options Should conform to arrays seen in logconfig.php.
     */
    function __construct(array $options = array())
    {
        $logConfig = require(SERVER_ROOT . 'config/logconfig.php');
        $this->handlers = array_merge($logConfig[EXCEPT_HANDLER], $options);

        set_exception_handler(array($this, 'handleException'));
        set_error_handler(array($this, 'handleError'));
        register_shutdown_function(array($this, 'handleFatalError'));
    }

    /**
     * Handle an uncaught exception. Determine the level of the exception (translating from its php error
     * code or HTML response code) and call log.
     * @param Exception $e
     */
    public function handleException(Exception $e)
    {
        $code = $e->getCode();
        $level = (isset(self::$phpErrMap[$code])) ? self::$phpErrMap[$code] :
            (isset(self::$responseCodes[$code])) ? $code : self::ERROR;

        $this->log($level, $e->getMessage().PHP_EOL.' {file} {line} '.PHP_EOL.' {trace}',
            array('file'=>$e->getFile(), 'line'=>$e->getLine(), 'trace'=>$e->getTraceAsString()));
    }

    /**
     * Handle a non-fatal error if error_reporting is on and the PHP error code for this error is
     * not below the error reporting threshold. Translate the php error code to a psr-3 log level and
     * call log.
     *
     * @param int $code
     * @param string $message
     * @param string $file
     * @param int $line
     */
    public function handleError($code, $message, $file = '', $line = 0)
    {
        if (!(error_reporting() & $code)) {
            return;
        }

        $level = (isset(self::$phpErrMap[$code])) ? self::$phpErrMap[$code] : 
            self::CRITICAL;
        
        $this->log($level, $message, array('file'=>$file, 'line'=>$line));
    }

    /**
     * Handle a fatal error. Get the last error and if the error type is in our fatal error array,
     * get the error level (translating from the php error type), and call log.
     */
    public function handleFatalError()
    {
        $e = error_get_last();
        if ($e && in_array($e['type'], self::$fatalPHPErr))
        {
            $level = self::$phpErrMap[$e['type']];
            $this->log($level, $e['message'],
                array('file'=>$e['file'], 'line'=>$e['line']));
        }
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function emergency($message, array $context = array())
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function alert($message, array $context = array())
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function critical($message, array $context = array())
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function error($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function warning($message, array $context = array())
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function notice($message, array $context = array())
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function info($message, array $context = array())
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function debug($message, array $context = array())
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param int $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        $response = $this->response($level, $message, $context);

        // For each expected handler, if true, output log to said handler. Exception is html && json -
        // json will override html output.
        if ($this->handlers['stderr'] && $this->handlers['stderr'] <= $level)
        {
            $this->streamWrite('php://stderr', $response);
        }
        if ($this->handlers['file'] && $this->handlers['file'] <= $level)
        {
            $this->streamWrite($this->handlers['logPath'] . date('Y_m_d') . '.log', $response);
        }
        if ($this->handlers['json'] && $this->handlers['json'] <= $level)
        {
            $this->JSONWrite($response);
        }
        else if ($this->handlers['html'] <= $level || in_array($level, self::$responseCodes))
        {
            if ($this->handlers['webTrace'])
            {
                $this->HTMLWrite($response);
            }
            else
            {
                $simpleResponse = $this->response($level, preg_replace('(\n|\r|\r\n)', '', $message),
                    array_merge($context, array('file'=>'', 'line'=>'', 'trace'=>'')));
                $this->HTMLWrite($simpleResponse);
            }
        }
    }

    /**
     * @param string $view
     * @param null|array $localVars
     * @override
     */
    protected function view($view, $localVars = null)
    {
        $target = SERVER_ROOT . SYS_DIR . self::VIEW_LOC . '/' . $view . '.php';
        if (file_exists($target))
        {
            if ($localVars) extract($localVars);
            include_once($target);
        }
    }

    /**
     * Generate the response array whose contents are used to log/display the exception.
     *
     * @param int|string $status Status code - one of the HTTP status codes, or an arbitrary code. If not an
     * HTTP status code, the response object for HTTP 500 is the default, but the real status code will also be
     * set.
     * @param null|string|mixed $message Optional - message will be set from appropriate HTTP response, if found,
     * or else from passed message string. Message will be interpolated with context array - any {vars} will
     * be replaces with same-named array elements from context. Message should be a string, or else have
     * __toString defined.
     * @param array $context Array of variables to interpolate into $message, replacing named {vars}.
     *
     * @return array ['code', 'message', 'datetime']
     */
    private function response($status = 500, $message = null, array $context = array())
    {
        // If $status is in $responseCode, set response approriately - else, default to 500.
        $response = (isset(self::$responseCodes[$status])) ? self::$responseCodes[$status] :
            self::$responseCodes[500];
        // If $status is a PHP Error code, map it to a PSR compliant code string via $levels. If not in
        // levels, default Error level.
        $response['code'] = (isset(self::$phpErrMap[$status])) ? self::$levels[self::$phpErrMap[$status]] :
            (isset(self::$levels[$status])) ? self::$levels[$status] : self::$levels[self::ERROR];

        if ($message) $response['message'] = $this->interpolateErrorMessage($message, $context);
        if (!static::$timezone) static::$timezone = new DateTimeZone(date_default_timezone_get() ?: 'UTC');
        $response['datetime'] = DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)),
            static::$timezone)->setTimezone(static::$timezone);

        return $response;
    }

    /**
     * Replace tokens between curly braces within the given string with variables with case-sensitive key names
     * of said tokens in array context.
     *
     * @param string|mixed $message
     * @param array $context
     *
     * @return string
     */
    private function interpolateErrorMessage($message, array $context)
    {
        $message = (string) $message;
        // Replace all {var} instances in string $message with value of var in $context
        $message = preg_replace_callback('/\{(.*?)\}/', function($match) use(&$context){
            return $context[$match[1]]; // 'var' - $match[0] gives us '{var}'
        }, $message);

        return $message;
    }

    /**
     * Display a log/error message in an html page.
     *
     * @param array $response
     */
    private function HTMLWrite($response)
    {
        header($response['header']);
        $response['headerDisplay'] = substr($response['header'], 9); // Omit HTTP/1.1
        $response['message'] = preg_replace('(\n|\r|\r\n)', '<br />', $response['message']);

        $response['message'] = '<code>'.$response['code'].': '.$response['message'].'</code>';
        $this->view($this->handlers['logTemplate'], $response);
    }

    /**
     * Return response as json.
     *
     * @param array $response
     */
    private function JSONWrite($response)
    {
        $this->json($response);
    }

    /**
     * Write the error to a stream (stderr, file, etc).
     *
     * @param string $url
     * @param array $response
     * @throws \UnexpectedValueException
     */
    private function streamWrite($url, $response)
    {
        $stream = fopen($url, 'a');
        if (!is_resource($stream))
        {
            throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened.', $url));
        }
        fwrite($stream, '['.$response['datetime']->format('Y-m-d H:i:s').'] '.
            $response['code'].': '.$response['message'].PHP_EOL);
        fclose($stream);
    }
}