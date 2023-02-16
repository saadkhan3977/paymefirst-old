<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;

class BrandController extends Controller
{
    public function index()
    {
        try{
            $brand = Brand::get();
            return response()->json(['success'=>true,'data'=>$brand]);

        }
        catch(\Eception $e){
            return $this->sendError($e->getMessage());

        }
    }
}
