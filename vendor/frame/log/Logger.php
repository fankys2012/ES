<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/17
 * Time: 10:59
 */

namespace frame\log;


use frame\base\Component;

class Logger extends Component
{
    /**
     * Error message level. An error message is one that indicates the abnormal termination of the
     * application and may require developer's handling.
     */
    const LEVEL_ERROR = 0x01;
    /**
     * Warning message level. A warning message is one that indicates some abnormal happens but
     * the application is able to continue to run. Developers should pay attention to this message.
     */
    const LEVEL_WARNING = 0x02;
    /**
     * Informational message level. An informational message is one that includes certain information
     * for developers to review.
     */
    const LEVEL_INFO = 0x04;
    /**
     * Tracing message level. An tracing message is one that reveals the code execution flow.
     */
    const LEVEL_TRACE = 0x08;
    /**
     * Profiling message level. This indicates the message is for profiling purpose.
     */
    const LEVEL_PROFILE = 0x40;
    /**
     * Profiling message level. This indicates the message is for profiling purpose. It marks the
     * beginning of a profiling block.
     */
    const LEVEL_PROFILE_BEGIN = 0x50;
    /**
     * Profiling message level. This indicates the message is for profiling purpose. It marks the
     * end of a profiling block.
     */
    const LEVEL_PROFILE_END = 0x60;

    /**
     * @var array logged messages. This property is managed by [[log()]] and [[flush()]].
     * Each log message is of the following structure:
     *
     * ```
     * [
     *   [0] => message (mixed, can be a string or some complex data, such as an exception object)
     *   [1] => level (integer)
     *   [2] => category (string)
     *   [3] => timestamp (float, obtained by microtime(true))
     *   [4] => traces (array, debug backtrace, contains the application code call stacks)
     *   [5] => memory usage in bytes (int, obtained by memory_get_usage()), available since version 2.0.11.
     * ]
     * ```
     */
    public $messages = [];
    /**
     * @var int how many messages should be logged before they are flushed from memory and sent to targets.
     * Defaults to 1000, meaning the [[flush]] method will be invoked once every 1000 messages logged.
     * Set this property to be 0 if you don't want to flush messages until the application terminates.
     * This property mainly affects how much memory will be taken by the logged messages.
     * A smaller value means less memory, but will increase the execution time due to the overhead of [[flush()]].
     */
    public $flushInterval = 1000;
    /**
     * @var int how much call stack information (file name and line number) should be logged for each message.
     * If it is greater than 0, at most that number of call stacks will be logged. Note that only application
     * call stacks are counted.
     */
    public $traceLevel = 0;
    /**
     * @var Dispatcher the message dispatcher
     */
    public $dispatcher;




    /**
     * Initializes the logger by registering [[flush()]] as a shutdown function.
     */
    public function init()
    {
        parent::init();


        register_shutdown_function(function () {
            // make regular flush before other shutdown functions, which allows session data collection and so on
            $this->flush();
            // make sure log entries written by shutdown functions are also flushed
            // ensure "flush()" is called last when there are multiple shutdown functions
            register_shutdown_function([$this, 'flush'], true);
        });
    }

    /**
     * Logs a message with the given type and category.
     * If [[traceLevel]] is greater than 0, additional call stack information about
     * the application code will be logged as well.
     * @param string|array $message the message to be logged. This can be a simple string or a more
     * complex data structure that will be handled by a [[Target|log target]].
     * @param int $level the level of the message. This must be one of the following:
     * `Logger::LEVEL_ERROR`, `Logger::LEVEL_WARNING`, `Logger::LEVEL_INFO`, `Logger::LEVEL_TRACE`,
     * `Logger::LEVEL_PROFILE_BEGIN`, `Logger::LEVEL_PROFILE_END`.
     * @param string $category the category of the message.
     */
    public function log($message, $level, $category = 'application')
    {
        $time = microtime(true);
        $traces = [];
        if ($this->traceLevel > 0) {
            $count = 0;
            $ts = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_pop($ts); // remove the last trace since it would be the entry script, not very useful
            foreach ($ts as $trace) {
                if (isset($trace['file'], $trace['line']) && strpos($trace['file'], FRAME_PATH) !== 0) {
                    unset($trace['object'], $trace['args']);
                    $traces[] = $trace;
                    if (++$count >= $this->traceLevel) {
                        break;
                    }
                }
            }
        }
        $this->messages[] = [$message, $level, $category, $time, $traces, memory_get_usage()];
        if ($this->flushInterval > 0 && count($this->messages) >= $this->flushInterval) {
            $this->flush();
        }
    }

    /**
     * Flushes log messages from memory to targets.
     * @param bool $final whether this is a final call during a request.
     */
    public function flush($final = false)
    {
        $messages = $this->messages;
        $this->messages = [];
        if ($this->dispatcher instanceof Dispatcher) {
            $this->dispatcher->dispatch($messages, $final);
        }
    }

    /**
     * Returns the total elapsed time since the start of the current request.
     * This method calculates the difference between now and the timestamp
     * defined by constant `YII_BEGIN_TIME` which is evaluated at the beginning
     * of [[\yii\BaseYii]] class file.
     * @return float the total elapsed time in seconds for current request.
     */
    public function getElapsedTime()
    {
        return microtime(true) - FRAME_BEGIN_TIME;
    }

    /**
     * Returns the profiling results.
     *
     * By default, all profiling results will be returned. You may provide
     * `$categories` and `$excludeCategories` as parameters to retrieve the
     * results that you are interested in.
     *
     * @param array $categories list of categories that you are interested in.
     * You can use an asterisk at the end of a category to do a prefix match.
     * @param array $excludeCategories list of categories that you want to exclude
     * @return array the profiling results. Each element is an array consisting of these elements:
     * `info`, `category`, `timestamp`, `trace`, `level`, `duration`, `memory`, `memoryDiff`.
     * The `memory` and `memoryDiff` values are available since version 2.0.11.
     */
    public function getProfiling($categories = [], $excludeCategories = [])
    {
        $timings = $this->calculateTimings($this->messages);
        if (empty($categories) && empty($excludeCategories)) {
            return $timings;
        }

        foreach ($timings as $i => $timing) {
            $matched = empty($categories);
            foreach ($categories as $category) {
                $prefix = rtrim($category, '*');
                if (($timing['category'] === $category || $prefix !== $category) && strpos($timing['category'], $prefix) === 0) {
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                foreach ($excludeCategories as $category) {
                    $prefix = rtrim($category, '*');
                    foreach ($timings as $i => $timing) {
                        if (($timing['category'] === $category || $prefix !== $category) && strpos($timing['category'], $prefix) === 0) {
                            $matched = false;
                            break;
                        }
                    }
                }
            }

            if (!$matched) {
                unset($timings[$i]);
            }
        }

        return array_values($timings);
    }


    /**
     * Calculates the elapsed time for the given log messages.
     * @param array $messages the log messages obtained from profiling
     * @return array timings. Each element is an array consisting of these elements:
     * `info`, `category`, `timestamp`, `trace`, `level`, `duration`, `memory`, `memoryDiff`.
     * The `memory` and `memoryDiff` values are available since version 2.0.11.
     */
    public function calculateTimings($messages)
    {
        $timings = [];
        $stack = [];

        foreach ($messages as $i => $log) {
            list($token, $level, $category, $timestamp, $traces) = $log;
            $memory = isset($log[5]) ? $log[5] : 0;
            $log[6] = $i;
            $hash = md5($token);
            if ($level == Logger::LEVEL_PROFILE_BEGIN) {
                $stack[$hash] = $log;
            } elseif ($level == Logger::LEVEL_PROFILE_END) {
                if (isset($stack[$hash])) {
                    $timings[$stack[$hash][6]] = [
                        'info' => $stack[$hash][0],
                        'category' => $stack[$hash][2],
                        'timestamp' => $stack[$hash][3],
                        'trace' => $stack[$hash][4],
                        'level' => count($stack) - 1,
                        'duration' => $timestamp - $stack[$hash][3],
                        'memory' => $memory,
                        'memoryDiff' => $memory - (isset($stack[$hash][5]) ? $stack[$hash][5] : 0),
                    ];
                    unset($stack[$hash]);
                }
            }
        }

        ksort($timings);

        return array_values($timings);
    }


    /**
     * Returns the text display of the specified level.
     * @param int $level the message level, e.g. [[LEVEL_ERROR]], [[LEVEL_WARNING]].
     * @return string the text display of the level
     */
    public static function getLevelName($level)
    {
        static $levels = [
            self::LEVEL_ERROR => 'error',
            self::LEVEL_WARNING => 'warning',
            self::LEVEL_INFO => 'info',
            self::LEVEL_TRACE => 'trace',
            self::LEVEL_PROFILE_BEGIN => 'profile begin',
            self::LEVEL_PROFILE_END => 'profile end',
            self::LEVEL_PROFILE => 'profile'
        ];

        return isset($levels[$level]) ? $levels[$level] : 'unknown';
    }

    public function setLogPath($path,$file='')
    {
        if ($this->dispatcher instanceof Dispatcher) {
            $this->dispatcher->setLogPath($path,$file);
        }
    }

}