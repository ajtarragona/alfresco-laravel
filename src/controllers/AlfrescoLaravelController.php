<?php

namespace Ajtarragona\AlfrescoLaravel\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Alfresco;

class AlfrescoLaravelController extends Controller
{

    public function index(Request $request)
    {
        echo "hola";
    }
}