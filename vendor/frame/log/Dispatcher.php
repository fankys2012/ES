<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/17
 * Time: 14:53
 */

namespace frame\log;


use frame\Base;
use frame\base\Component;
use frame\Log;

class Dispatcher extends Component
{
    public $targets = [];

    /**
     * @var Logger the logger.
     */
    private $_logger;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // ensure logger gets set before any other config option
        if (isset($config['logger'])) {
            $this->setLogger($config['logger']);
            unset($config['logger']);
        }
        // connect logger and dispatcher
        $this->getLogger();

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        foreach ($this->targets as $name => $target) {
            if (!$target instanceof Target) {
                $this->targets[$name] = Base::createObject($target);
            }
        }
    }

    public function getLogger()
    {
        if ($this->_logger === null) {
            $this->setLogger(Log::getLogger());
        }
        return $this->_logger;
    }

    public function setLogger($value)
    {
        if (is_string($value) || is_array($value)) {
            $value = Base::createObject($value);
        }
        $this->_logger = $value;
        $this->_logger->dispatcher = $this;
    }

    public function getTraceLevel()
    {
        return $this->getLogger()->traceLevel;
    }

    public function setTraceLevel($value)
    {
        $this->getLogger()->traceLevel = $value;
    }

    public function getFlushInterval()
    {
        return $this->getLogger()->flushInterval;
    }

    public function setFlushInterval($value)
    {
        $this->getLogger()->flushInterval = $value;
    }

    /**
     * Dispatches the logged messages to [[targets]].
     * @param array $messages the logged messages
     * @param bool $final whether this method is called at the end of the current application
     */
    public function dispatch($messages, $final)
    {
        $targetErrors = [];
        foreach ($this->targets as $target) {
            if ($target->enabled) {
                try {
                    $target->collect($messages, $final);
                } catch (\Exception $e) {
                    $target->enabled = false;
                    $targetErrors[] = [
                        'Unable to send log via ' . get_class($target) ,
                        Logger::LEVEL_WARNING,
                        __METHOD__,
                        microtime(true),
                        [],
                    ];
                }
            }
        }

        if (!empty($targetErrors)) {
            $this->dispatch($targetErrors, true);
        }
    }

    public function setLogPath($path,$file='')
    {
        foreach ($this->targets as $target) {
            if ($target->enabled) {
                $target->setLogPath($path,$file);
            }
        }
    }

}