<?php


namespace App\Rest\Run;

use App\Base\Run\RestProcessorProto;

class RestProcessor extends RestProcessorProto
{
    
    public function getAppName()
    {
        return 'Rest';
    }
}