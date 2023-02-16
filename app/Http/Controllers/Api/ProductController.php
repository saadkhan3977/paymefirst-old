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
}
