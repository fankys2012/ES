<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/3/15
 * Time: 15:20
 */

namespace frame\base;


use frame\Base;
use frame\helpers\BaseVarDumper;
use frame\Log;
use frame\web\HttpException;
use Swoole\WebSocket\Frame;

class ErrorHandler extends Component
{

    public $exception;

    public function register()
    {
        ini_set('display_errors', false);

        //注册未被捕获的异常，部分错误在error_handler 中不能正常处理，需要借助该方法。
        set_exception_handler([$this, 'handleException']);

        //注册错误处理方法
        set_error_handler([$this, 'handleError']);

        register_shutdown_function([$this, 'handleFatalError']);

    }

    public function unregister()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public function handleException($exception)
    {
        $this->exception = $exception;
        $this->unregister();

        try {
            $this->logException($exception);

            $this->renderException($exception);

        } catch (\Exception $e) {
            // an other exception could be thrown while displaying the exception
            $this->handleFallbackExceptionMessage($e, $exception);
        } catch (\Throwable $e) {
            // additional check for \Throwable introduced in PHP 7
            $this->handleFallbackExceptionMessage($e, $exception);
        }

        $this->exception = null;
    }

    /**
     * Logs the given exception
     * @param \Exception $exception the exception to be logged
     */
    public function logException($exception)
    {
        $category = get_class($exception);
        if ($exception instanceof HttpException) {
            $category = 'frame\\web\\HttpException:' . $exception->statusCode;
        } elseif ($exception instanceof \ErrorException) {
            $category .= ':' . $exception->getSeverity();
        }
        Log::error($exception, $category);
    }

    /**
     * 输出错误
     * @param $exception
     */
    protected function renderException($exception)
    {
        if (Base::$app->has('response')) {
            $response = Base::$app->getResponse();
            // reset parameters of response to avoid interference with partially created response data
            // in case the error occurred while sending the response.
            $response->isSent = false;
            $response->stream = null;
            $response->data = null;
            $response->content = null;
        } else {
            $response = new Response();
        }

        $response->setStatusCodeByException($exception);

        $response->data = self::convertExceptionToString($exception);

        $response->send();
    }

    /**
     * 错误信息格式化
     * @param \Exception $exception
     * @return string
     */
    public static function convertExceptionToString($exception)
    {
        $errorNames = array(
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_NOTICE => 'Notice'
        );

        if (FRAME_DEBUG) {
            $message = "PHP ".$errorNames[$exception->getCode()];
            $message .= " '" . get_class($exception) . "' with message '{$exception->getMessage()}' \n\nin "
                . $exception->getFile() . ':' . $exception->getLine() . "\n\n"
                . "Stack trace:\n" . $exception->getTraceAsString();
        } else {
            $message = "PHP ".$errorNames[$exception->getCode()]." ".$exception->getMessage();
        }
        return $message;
    }


    protected function handleFallbackExceptionMessage($exception, $previousException) {
        $msg = "An Error occurred while handling another error:\n";
        $msg .= (string) $exception;
        $msg .= "\nPrevious exception:\n";
        $msg .= (string) $previousException;
        echo 'An internal server error occurred.';
        $msg .= "\n\$_SERVER = " . BaseVarDumper::export($_SERVER);
        error_log($msg);

        exit(1);
    }

    public function handleError($code, $message, $file, $line)
    {
        if (error_reporting() & $code) {

            $exception = new \ErrorException($message, $code, $code, $file, $line);

            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
            foreach ($trace as $frame) {
                if ($frame['function'] === '__toString') {
                    $this->handleException($exception);

                    exit(1);
                }
            }
            throw $exception;
        }
        return false;
    }

    /**
     * Handles fatal PHP errors
     */
    public function handleFatalError()
    {

        $error = error_get_last();

        if(isset($error['type']) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING]))
        {

            $exception = new \ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            $this->exception = $exception;

            $this->logException($exception);

            $this->renderException($exception);

            // need to explicitly flush logs because exit() next will terminate the app immediately
            Log::getLogger()->flush(true);

            exit(1);
        }
    }
}