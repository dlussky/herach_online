<?php


namespace Mu\Interfaces;


use Mu\Dispatcher\Encoder\IEncoder;

interface DispatcherInterface
{
    /**
     * @return string|null
     */
    public function getUserAgent();
    
    public function getUserAgentType();
    
    /**
     * @return string|null
     */
    public function getOrigin();
    
    /**
     * Получение IP адреса с которого пришел запрос
     * @return string|null
     */
    public function getClientIp();
    
    
    public function getUrl();
    
    /**
     * @return array
     */
    public function getParams();
    
    /**
     * @param string $contentType
     *
     * @return $this
     */
    public function setContentType($contentType);
    
    /**
     * @return string
     */
    public function getContentType();
    
    /**
     * @return int
     */
    public function getId();
    
    /**
     * @return int[]
     */
    public function getIds();
    
    /**
     * @return string
     */
    public function getMethod();
    
    /**
     * @return string
     */
    public function getResource();
    
    /**
     * @return array
     */
    public function getFilters();
    
    /**
     * @return string
     */
    public function getBody();
    
    
    public function getHeader($name);
    
    /**
     * @param $data
     *
     * $this->getEncoder()->encode($data)
     *
     * @return mixed
     */
    public function encode($data);
    
    public function decode($string);
    
    /**
     * @param IEncoder $encoder
     *
     * @return $this
     */
    public function setEncoder($encoder);
    
    /**
     * @return \Mu\Dispatcher\Encoder\IEncoder
     */
    public function getEncoder();
    
    /**
     * @param null $encodedBody
     *
     * @return $this
     */
    public function setDecodedBody($encodedBody);
    
    /**
     * @return null
     */
    public function getDecodedBody();
    
    /**
     * @return string
     */
    public function getLocale();
    
    /**
     * Получение всех параметров запроса
     * Фильтрация растпространяется на параметры, получаемые этим методом
     * @return array
     */
    public function getRequestParams();
    
    public function getParamsByKeys ($paramsKeys);
    
    /**
     *
     * if ( isset($arrayParams['id']) ) {
     * $this->setId($arrayParams['id']);
     * }
     *
     * if ( isset($arrayParams['ids']) ) {
     * $this->setIds($arrayParams['ids']);
     * }
     *
     * @param $arrayParams
     */
    public function setRequestParams($arrayParams);
    
    /**
     * @return string
     */
    public function getReqiestId();
    
    public function getParam ($key, $default = null); 
    
    public function redirect($url);
    
    /**
     * Получение платформы c которой отправлен запрос
     * 
     * @return string|null
     */
    public function getPlatform();
}