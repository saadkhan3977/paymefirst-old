<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shipping;

class ShippingController extends Controller
{
    public function index()
    {
        try{
            $shipping = Shipping::get();
            return response()->json(['success'=>true,'data'=>$shipping]);

        }
        catch(\Eception $e){
            return $this->sendError($e->getMessage());

        }
    }
}
