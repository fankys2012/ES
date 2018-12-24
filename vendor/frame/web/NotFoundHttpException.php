<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/18
 * Time: 10:26
 */

namespace frame\web;


class NotFoundHttpException extends HttpException
{
    /**
     * Constructor.
     * @param string $message error message
     * @param int $code error code
     * @param \Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct(404, $message, $code, $previous);
    }
}