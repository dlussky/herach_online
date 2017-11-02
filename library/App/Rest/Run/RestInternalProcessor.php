<?php


namespace App\Rest\Run;


use Run\ChannelMessage\ChannelMsg;
use Run\Processor\RunRequestProcessorProto;
use Run\Rest\RestRequestOptions;
use Run\RunRequest;
use Run\Spec\HttpResponseSpec;

class RestInternalProcessor extends RunRequestProcessorProto
{
    
    public function prepare()
    {
        // TODO: Implement prepare() method.
    }
    
    public function process(RunRequest $request)
    {
        $resParts = array_filter(explode('/', $request->getResource()));
        $module = isset($resParts[0]) ? ucfirst($resParts[0]): 'Landing';
        $controller = isset($resParts[1]) ? ucfirst($resParts[1]) : $module;
        $method = $resParts[2] ?? 'index';
    
        $controllerClass = '\\App\\Rest\\'.$module.'\\Controller\\'.$controller;
    
        $response = new ChannelMsg();
        $response->setChannelState($request->getChannelState());
        $response->setUid($request->getUid());
    
        if (!class_exists($controllerClass)) {
            return $this->abnormalResponse(
                HttpResponseSpec::HTTP_CODE_NOT_FOUND,
                'Resource not found',
                $response,
                $request
            );
        }
    
        $method = 'get';
    
        try {
            $controller = new $controllerClass;
        
            if (!$controller instanceof RestControllerProto) {
                return $this->abnormalResponse(
                    HttpResponseSpec::HTTP_CODE_NOT_FOUND,
                    'Incorrect resource',
                    $response,
                    $request
                );
            }
        
            if (!method_exists($controller, $method)) {
                return $this->abnormalResponse(
                    HttpResponseSpec::HTTP_CODE_NOT_FOUND,
                    'Incorrect resource',
                    $response,
                    $request
                );
            }
            
            $options = new RestRequestOptions();
            $options->setRequest($request);
            $controller->setRequest($options);
        
            $response->setCode(HttpResponseSpec::HTTP_CODE_OK);
            $response->setBody($controller->{$method}());
        
        } catch (\Throwable $throwable) {
            return $this->abnormalResponse(
                HttpResponseSpec::HTTP_CODE_ERROR,
                'Internal error : '. $throwable->getMessage().' on '.$throwable->getTraceAsString(),
                $response,
                $request
            );
        }
        
        $this->sendResponse($response, $request);
    }
    
    protected function abnormalResponse(int $code, string $text, ChannelMsg $response, RunRequest $request) {
        $response->setCode($code);
        $response->body = $text;
        $this->sendResponse($response, $request);
    }
}