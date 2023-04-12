<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        try
        {
            $product = Product::get();
            return response()->json(['success'=>true,'data'=>$product]);
        }
        catch(\Eception $e)
        {
            return $this->sendError($e->getMessage());
        }
    }
    
    public function brand_product($id)
    {
        try
        {
            $product = Product::where('brand_id',$id)->get();
            return response()->json(['success'=>true,'data'=>$product]);
        }
        catch(\Eception $e)
        {
            return $this->sendError($e->getMessage());
        }
    }
}
