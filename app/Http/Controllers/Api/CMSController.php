<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Guide;
use App\Models\TermsAndCondition;

class CMSController extends BaseController
{
    public function guide(Request $request)
    {
        try{
        $guide = Guide::all();
        return response()->json(['success'=>true,'data'=>$guide]);
            }
		catch(\Eception $e){                
           return $this->sendError($e->getMessage());    
        }
    }
    public function termanscondition(Request $request)
    {
        try{
        $terms = TermsAndCondition::first();
        $terms['description']=preg_split("/\r\n|\n|\r/", $terms->description)  ;
        return response()->json(['success'=>true,'data'=>$terms]);
            }
		catch(\Eception $e){                
           return $this->sendError($e->getMessage());    
        }
    }
}
