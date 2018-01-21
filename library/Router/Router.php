<?php


namespace Router;


use Mu\Env;
use Router\Actors\RouterRequestConsumer;
use Router\Exceptions\EmptyRouterMessage;
use Router\Model\RouterServer;
use Uuid\Uuid;

class Router
{
    const THREAD_MAIN          = 'main';
    const REASON_CONSUME_REPLY = 'consumeReply';
    const THREAD_CONSUME       = 'consume:';
    
    /**
     * @var RouterServer[]
     */
    private $servers = [];
    
    private $nodeId = '';
    
    private $replyQueueName = '';
    
    /**
     * @var RouterRegistry
     */
    private $registry;
    
    /**
     * Router2 constructor.
     *
     * @param string $nodeId
     */
    public function __construct($nodeId = null)
    {
        $nodeId = $nodeId ?: gethostname() . ':' . getmypid().':'.Uuid::v4();
        
        $this->nodeId         = $this->_prepareQueueName($nodeId);
        $this->replyQueueName = RouterConfig::getReplyQueueName($this->nodeId);
        $this->registry       = new RouterRegistry();
    }
    
    /**
     * PUBLIC API
     */
    
    /**
     * Инициализация модуля
     *
     * @param \Mu\Interfaces\ConfigInterface $config
     *
     * @return $this
     */
    public function init($config = null)
    {
        $config = $config ?: Env::getLegacyConfig();
        $host   = $config->get('host', 'amqp', RouterConfig::AMQP_RABBIT_DEFAULT_HOST);
        $port   = $config->get('port', 'amqp', RouterConfig::AMQP_RABBIT_DEFAULT_PORT);
        
        $defaultServer = new RouterServer($host, $port, RouterConfig::SERVER_TAG_DEFAULT);
        
        $this->registry->registerServer($defaultServer);
        
        return $this;
    }
    
    /**
     * Зарегистрировать нахождение очереди на определенном сервере
     *
     * @param $queueName
     * @param $host
     * @param $port
     *
     * @return bool
     */
    public function registerQueue($queueName, $host, $port = RouterConfig::AMQP_RABBIT_DEFAULT_PORT)
    {
        $queueName = $this->_prepareQueueName($queueName);
        $server    = $this->registry->findServer($host, $port);
        
        if (!$server) {
            $server = new RouterServer($host, $port);
            $this->registry->registerServer($server);
        }
        
        $this->registry->registerQueueToServer($queueName, $server);
        
        return $queueName;
    }
    
    
    public function publish($messageData, $queueNameRaw, $needReply = false, $params = [])
    {
        $messageString = $this->_prepareMessage($messageData);
        
        if (!$messageString) {
            throw new EmptyRouterMessage();
        }
        
        $thread = self::THREAD_MAIN;
        
        $queueName = $this->_prepareQueueName($queueNameRaw);
        
        $server = $this->registry->findServerForQueue($queueName, RouterConfig::SERVER_TAG_DEFAULT);
        
        $publisher = $server->getPublisher($thread);
        
        $correlationId = true;
        
        if ($needReply) {
            // setup reply queue
            $server->getQueue($thread, $this->replyQueueName, RouterConfig::CONFIG_REPLY_CONSUMER);
            
            $correlationId = $this->_getCorrelationId();
            
            $params += [
                'correlation_id' => $correlationId, // Связующий ID
                'reply_to'       => $this->replyQueueName,
            ];
        }
        
        $res = $publisher->publish($messageString, $queueName, $params);
        
        return $res ? $correlationId : false;
    }
    
    public function reply($replyData, $replyQueue, $corrId)
    {
        $queueName = $this->_prepareQueueName($replyQueue);
        
        $messageString = $this->_prepareMessage($replyData);
        
        if (is_null($messageString)) {
            throw new EmptyRouterMessage();
        }
        
        $server = $this->registry->findServerForQueue($queueName, RouterConfig::SERVER_TAG_DEFAULT);
        $publisher = $server->getPublisher(self::THREAD_MAIN);
        
        $params = [
            'correlation_id' => $corrId, // Связующий ID
        ];
        
        // @todo проверить свзяь тредов через свзяующий айди
        return $publisher->publish($messageString, $queueName, $params);
    }
    
    /**
     * @param      $queueName
     *
     * @param      $timeout
     *
     * @param null $refresh
     *
     * @return RouterRequestConsumer
     */
    public function getConsumer($queueName, $timeout = null, $refresh = null)
    {
        $queueName = $this->_prepareQueueName($queueName);
        $thread    = self::THREAD_CONSUME . $queueName.':'.$timeout;
        
        $server = $this->registry->findServerForQueue($queueName, RouterConfig::SERVER_TAG_DEFAULT);
        
        $config = [
            RouterConfig::REQUEST_CONSUMER_TIME_LIMIT => $timeout,
            RouterConfig::REQUEST_CONSUMER_REFRESH_INTERVAL => $refresh 
        ];
        
        $consumer = $server->getRequestConsumer($thread, $queueName, RouterConfig::CONFIG_PERSISTENT_CONSUMER, $config);
        
        return $consumer;
    }
    
    public function getRequestReader ($queueName, $timeout) 
    {
        
    }
    
    public function readResult($forwardQueueName, $correlationId, $timeout)
    {
        /* prepare read result */
        $forwardQueueName = $this->_prepareQueueName($forwardQueueName);
        
        $server = $this->registry->findServerForQueue($forwardQueueName, RouterConfig::SERVER_TAG_DEFAULT);
        
        $replyReader = $server->getReplyReader(self::THREAD_MAIN, $this->replyQueueName, RouterConfig::CONFIG_REPLY_CONSUMER);
        
        return $replyReader->read($correlationId, $timeout);
    }
    
    /**
     * @return array
     */
    public function getServers()
    {
        return $this->servers;
    }
    
    /**
     * INTERNAL FUNCTIONS
     */
    
    
    /**
     * @param $queueName
     *
     * @return string
     */
    private function _prepareQueueName($queueName)
    {
        return strtolower($queueName);
    }
    
    /**
     * @param $message
     *
     * @return null|string
     */
    private function _prepareMessage($message)
    {
        if (is_string($message) || is_numeric($message)) {
            return $message;
        }
        
        if (is_array($message)) {
            return json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        
        return null;
    }
    
    
    /**
     * @return string
     */
    private function _getCorrelationId()
    {
        return Uuid::v4();
    }
    
    /**
     * @return RouterRegistry
     */
    public function getRegistry()
    {
        return $this->registry;
    }
    
    public function disconnectAll()
    {
        foreach ($this->registry->getServers() as $server) {
            $server->disconnectAll();
        }
    }
    
    function __destruct()
    {
        $this->disconnectAll();
    }
    
    /**
     * @return string
     */
    public function getReplyQueueName()
    {
        return $this->replyQueueName;
    }
}