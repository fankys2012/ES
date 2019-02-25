<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/17
 * Time: 11:42
 */

namespace frame\log;


use frame\base\Component;
use frame\helpers\BaseVarDumper;

abstract class Target extends Component
{
    /**
     * @var bool whether to enable this log target. Defaults to true.
     */
    public $enabled = true;
    /**
     * @var array list of message categories that this target is interested in. Defaults to empty, meaning all categories.
     * You can use an asterisk at the end of a category so that the category may be used to
     * match those categories sharing the same common prefix.
     */
    public $categories = [];
    /**
     * @var array list of message categories that this target is NOT interested in. Defaults to empty, meaning no uninteresting messages.
     * If this property is not empty, then any category listed here will be excluded from [[categories]].
     * You can use an asterisk at the end of a category so that the category can be used to
     * match those categories sharing the same common prefix.
     */
    public $except = [];
    /**
     * @var array list of the PHP predefined variables that should be logged in a message.
     * Note that a variable must be accessible via `$GLOBALS`. Otherwise it won't be logged.
     *
     * Defaults to `['_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_SERVER']`.
     *
     * Since version 2.0.9 additional syntax can be used:
     * Each element could be specified as one of the following:
     *
     * - `var` - `var` will be logged.
     * - `var.key` - only `var[key]` key will be logged.
     * - `!var.key` - `var[key]` key will be excluded.
     *
     */
    public $logVars = ['_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_SERVER'];
    /**
     * @var callable a PHP callable that returns a string to be prefixed to every exported message.
     *
     * If not set, [[getMessagePrefix()]] will be used, which prefixes the message with context information
     * such as user IP, user ID and session ID.
     *
     * The signature of the callable should be `function ($message)`.
     */
    public $prefix;
    /**
     * @var int how many messages should be accumulated before they are exported.
     * Defaults to 1000. Note that messages will always be exported when the application terminates.
     * Set this property to be 0 if you don't want to export messages until the application terminates.
     */
    public $exportInterval = 1000;
    /**
     * @var array the messages that are retrieved from the logger so far by this log target.
     * Please refer to [[Logger::messages]] for the details about the message structure.
     */
    public $messages = [];

    private $_levels = 0;

    /**
     * @var unique code number
     */
    public $traceCode;

    /**
     * Exports log [[messages]] to a specific destination.
     * Child classes must implement this method.
     */
    abstract public function export();

    public function getLevels()
    {
        return $this->_levels;
    }

    /**
     * Processes the given log messages.
     * This method will filter the given messages with [[levels]] and [[categories]].
     * And if requested, it will also export the filtering result to specific medium (e.g. email).
     * @param array $messages log messages to be processed. See [[Logger::messages]] for the structure
     * of each message.
     * @param bool $final whether this method is called at the end of the current application
     */
    public function collect($messages, $final)
    {
        $this->messages = array_merge($this->messages, static::filterMessages($messages, $this->getLevels(), $this->categories, $this->except));
        $count = count($this->messages);
        if ($count > 0 && ($final || $this->exportInterval > 0 && $count >= $this->exportInterval)) {

            // set exportInterval to 0 to avoid triggering export again while exporting
            $oldExportInterval = $this->exportInterval;
            $this->exportInterval = 0;
            $this->export();
            $this->exportInterval = $oldExportInterval;

            $this->messages = [];
        }
    }

    /**
     * Filters the given messages according to their categories and levels.
     * @param array $messages messages to be filtered.
     * The message structure follows that in [[Logger::messages]].
     * @param int $levels the message levels to filter by. This is a bitmap of
     * level values. Value 0 means allowing all levels.
     * @param array $categories the message categories to filter by. If empty, it means all categories are allowed.
     * @param array $except the message categories to exclude. If empty, it means all categories are allowed.
     * @return array the filtered messages.
     */
    public static function filterMessages($messages, $levels = 0, $categories = [], $except = [])
    {
        foreach ($messages as $i => $message) {
            if ($levels && !($levels & $message[1])) {
                unset($messages[$i]);
                continue;
            }

            $matched = empty($categories);
            foreach ($categories as $category) {
                if ($message[2] === $category || !empty($category) && substr_compare($category, '*', -1, 1) === 0 && strpos($message[2], rtrim($category, '*')) === 0) {
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                foreach ($except as $category) {
                    $prefix = rtrim($category, '*');
                    if (($message[2] === $category || $prefix !== $category) && strpos($message[2], $prefix) === 0) {
                        $matched = false;
                        break;
                    }
                }
            }

            if (!$matched) {
                unset($messages[$i]);
            }
        }
        return $messages;
    }

    /**
     * Formats a log message for display as a string.
     * @param array $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return string the formatted message
     */
    public function formatMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;
        $level = Logger::getLevelName($level);
        if (!is_string($text)) {
            // exceptions may not be serializable if in the call stack somewhere is a Closure
            if ($text instanceof \Throwable || $text instanceof \Exception) {
                $text = (string) $text;
            } else {
                $text = BaseVarDumper::export($text);
            }
        }
        $traces = [];
        if (isset($message[4])) {
            foreach ($message[4] as $trace) {
                $traces[] = "in {$trace['file']}:{$trace['line']}";
            }
        }
        return date('Y-m-d H:i:s', $timestamp) . " [{$this->traceCode}][$level] $text"
            . (empty($traces) ? '' : "\n    " . implode("\n    ", $traces));
    }

    public function setLogPath($path,$file='')
    {

    }

    public function createLogFile(){}
}