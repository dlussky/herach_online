<?php


namespace App\Rest\Auth\Storage;


use App\Rest\Storage\DataAdapterFactory;
use Storage\SimpleStorage;
use Storage\StorageContext;
use Storage\StorageDependency;

class SessionStorage extends SimpleStorage
{
    
    public function loadConfig()
    {
        $this->context->set(StorageContext::RESOURCE, 'session');
    }
    
    public function customizeDi(StorageDependency $container, StorageContext $context)
    {
        $container->setModule(StorageDependency::DATA_ADAPTER, (new DataAdapterFactory())->getAdapter($context));
    }
}