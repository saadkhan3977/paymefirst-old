<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Wishlist;
use Auth;

class CartController extends Controller
{
    public function index()
    {
        try
        {
            $cart = Cart::with('product')->where('user_id',Auth::user()->id)->where('order_id',null)->get();
            return response()->json(['success'=>true,'data'=>$cart]);
        }
        catch(\Eception $e)
        {
            return response()->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try
        {
            $product = Product::find($request->product_id);
            $already_cart = Cart::where('user_id', auth()->user()->id)->where('order_id',null)->where('product_id', $request->product_id)->first();
            if($already_cart) {
                // dd($already_cart);
                $already_cart->quantity = $already_cart->quantity + 1;
                $already_cart->amount = $product->price+ $already_cart->amount;
                // return $already_cart->quantity;
                if ($already_cart->product->stock < $already_cart->quantity || $already_cart->product->stock <= 0) return back()->with('error','Stock not sufficient!.');
                $already_cart->save();
            }else{
                $cart = new Cart;
                $cart->user_id = auth()->user()->id;
                $cart->product_id =  $request->product_id;
                $cart->price = ($product->price-($product->price*$product->discount)/100);
                $cart->quantity = 1;
                $cart->color = $request->color;
                $cart->size = $request->size;
                $cart->amount=$cart->price*$cart->quantity;
                if ($cart->product->stock < $cart->quantity || $cart->product->stock <= 0) return back()->with('error','Stock not sufficient!.');
                $cart->save();
                $wishlist=Wishlist::where('user_id',auth()->user()->id)->where('cart_id',null)->update(['cart_id'=>$cart->id]);
            }
            

            $carts = Cart::with('product')->where('user_id',Auth::user()->id)->where('order_id',null)->get();
            return response()->json(['success'=>true,'message'=>'Product successfully added to cart','data'=>$carts]);    
        }
        catch(\Eception $e)
        {
            return response()->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }  

    public function update(Request $request,$id)
    {
        try
        {
            $validator = \Validator::make($request->all(),[
                'quantity'=>'required',
            ]);
            if($validator->fails()) {
                return response()->json(['success'=>false,'message'=>$validator->errors()]);    
            }
            // return 'sad';
            if($request->quantity)
            {
                $error = array();
                $success = '';
                $quant = $request->quantity;
                $cart = Cart::find($id);
                
                if($cart) 
                {
                    if($cart->product->stock < $quant){
                        request()->session()->flash('error','Out of stock');
                        return back();
                    }
                    $cart->quantity = ($cart->product->stock > $quant) ? $quant  : $cart->product->stock;
                    
                    $after_price=($cart->product->price-($cart->product->price*$cart->product->discount)/100);
                    $cart->amount = $after_price * $quant;
                    $cart->save();

                    $carts = Cart::with('product')->where('user_id',Auth::user()->id)->where('order_id',null)->get();
                    return response()->json(['success'=>true,'message'=>'Cart successfully updated!','data'=>$carts]);    
                }
                else
                {                    
                    $carts = Cart::with('product')->where('user_id',Auth::user()->id)->where('order_id',null)->get();
                    return response()->json(['success'=>false,'message'=>'Cart Invalid!','data'=>$carts]);    
                }
            }
        }
        catch(\Eception $e)
        {
            return response()->json(['success'=>false,'message'=>$e->getMessage()]);    
        }    
    }
}
