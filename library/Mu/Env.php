<?php

namespace Mu;

use Mu\Interfaces\ConfigInterface;
use Mu\Interfaces\ContainerInterface;
use Mu\Interfaces\SessionInterface;
use Monolog\Logger;

class Env
{
    /**
     * @var ContainerInterface
     */
    private static $container;
    
    private static $isProfiling;
    
    private static $isDebug;
    
    /**
     * @param bool $autoInit
     *
     * @return ContainerInterface
     * @throws \Exception
     */
    public static function getContainer() {
        return self::$container;
    }
    
    /**
     * @return bool
     */
    public static function isDebugMode()
    {
        self::$isDebug === null && self::$isDebug = (bool)filter_var(self::getConfig()->get('debug', 'error'), FILTER_VALIDATE_BOOLEAN);
        return self::$isDebug;
    }

    public static function isProfiling()
    {
        self::$isProfiling === null && self::$isProfiling = self::isDebugMode() || self::getConfig()->get('profiling', 'error'); 
        return self::$isProfiling;
    }

    /**
     * @return \Mu\Interfaces\ConfigInterface
     */
    public static function getConfig()
    {
        return self::getContainer()->bootstrap('config');
    }

    
    /**
     * @return \Mu\Cache\Redis
     */
    public static function getRedis()
    {
        return self::getContainer()->bootstrap('redis');
    }

    /**
     * @return Logger
     */
    public static function getLogger()
    {
        return self::getContainer()->bootstrap('logger');
    }
    
    /**
     * @param bool $required
     *
     * @return SessionInterface
     */
    public static function getSession($required = true)
    {
        return self::getContainer()->bootstrap('session', $required);
    }
    
    /**
     * @return \Router\Router
     */
    public static function getRouter()
    {
        return self::getContainer()->bootstrap('router');
    }
    
    /**
     * @return \Run\Event\EventDispatcher
     */
    public static function getEventDispatcher()
    {
        return self::getContainer()->bootstrap('events');
    }
    
    /**
     * @param ContainerInterface $container
     */
    public static function setContainer(ContainerInterface $container)
    {
        self::$container = $container;
    }
    
    
}