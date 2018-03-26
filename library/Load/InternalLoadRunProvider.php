<?php


namespace Load;


use Run\RunRequest;
use Run\Spec\HttpRequestMetaSpec;
use Run\Provider\RunProviderProto;
use Run\Util\HttpResourceHelper;

class InternalLoadRunProvider extends RunProviderProto
{
    /**
     * @var Load[]
     */
    protected $loads = [];
    
    public function prepare()
    {
    }
    
    public function run()
    {
        while ($this->loads) {
            $load = array_pop($this->loads);
            $this->_processLoad($load);
        }
    }
    
    private function _processLoad(Load $load) {
        // готовим ресурс по взрослому
        $resource = new HttpResourceHelper($load->getResource(), HttpResourceHelper::TYPE_REST);
        
        // собираем реквест
        $request = new RunRequest($load->getUuid(), $resource->getResource());
        $request->meta[HttpRequestMetaSpec::PROVIDER_TYPE] = $resource->getType();
        $request->params = $load->getParams();
    
        // отдаем в работу
        $this->core->process($request);
    }
    
    /**
     * @param Load[] $loads
     */
    public function setLoads(array $loads)
    {
        $this->loads = $loads;
    }
}