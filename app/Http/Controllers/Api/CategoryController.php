<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        try{
            $category = Category::get();
            return response()->json(['success'=>true,'data'=>$category]);

        }
        catch(\Eception $e){
            return $this->sendError($e->getMessage());

        }
    }

}
