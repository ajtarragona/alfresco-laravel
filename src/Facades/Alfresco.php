<?php

namespace Ajtarragona\AlfrescoLaravel\Facades; 

use Illuminate\Support\Facades\Facade;

class Alfresco extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'alfresco';
    }
}
