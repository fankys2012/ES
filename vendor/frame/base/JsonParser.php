<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/14
 * Time: 17:04
 */

namespace frame\base;


class JsonParser implements RequestParserInterface
{
    /**
     * @var bool whether to return objects in terms of associative arrays.
     */
    public $asArray = true;
    /**
     * @var bool whether to throw a [[BadRequestHttpException]] if the body is invalid json
     */
    public $throwException = true;


    /**
     * Parses a HTTP request body.
     * @param string $rawBody the raw HTTP request body.
     * @param string $contentType the content type specified for the request body.
     * @return array parameters parsed from the request body
     * @throws BadRequestHttpException if the body contains invalid json and [[throwException]] is `true`.
     */
    public function parse($rawBody, $contentType)
    {
        $parameters = json_decode($rawBody, $this->asArray);
        return $parameters === null ? [] : $parameters;
    }
}