<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Api\BaseController as BaseController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialBreakdown;
use Validator;
use Auth;
class FinancialController extends BaseController
{
    public function financialdata($date)
    {
        try{
			$data = FinancialBreakdown::where('user_id',Auth::id())->where('created_at',$date)->get();
            return response()->json(['success'=>true,'data'=>$data]);

        }catch(\Exception $e){
            return $this->sendError($e->getMessage());
        }
    }
    public function financialpost(Request $request)
    {
        try{ 
            $valiadator = Validator::make($request->all(),[
                'name' =>'required',
                'color' =>'required',
                'value' => 'required'
            ]);
            if($valiadator->fails())
            {
             return $this->sendError($valiadator->errors()->first());
    
           }        
                $data = FinancialBreakdown::create([
                    'name' => $request->name,
                    'value' => $request->value,
                    'color' => $request->color,
                    'user_id' => Auth::id(),
					'created_at' => date('Y-m-d')
                ]);
            
            return response()->json(['success'=>true,'message'=>'Record Created Successfully','data'=>$data]);
       
        }catch(\Eception $e){
            return $this->sendError($e->getMessage());

        }
    }
}
