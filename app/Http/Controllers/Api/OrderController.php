<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\Order;
use App\Models\Wallet;
use Stripe;

use Auth;

class OrderController extends Controller
{


    public function __construct()
    {
        $stripe = \Stripe\Stripe::setApiKey('sk_test_51LCrVHHNvw3AIrpxjbOuGKoRaQ3K68ZDXrgU41PRmyDb9eH7h9qShHEn1T8gEUV7amg1TfNSy1cVXWaREFgcfmMr00yqKik6dg');
    }


    public function store(Request $request)
    {
        try
        {
            $orderid = 'ORD-'.strtoupper(\Str::random(10));
            $validator = \Validator::make($request->all(),[
                'product_id'=>'required',
                'shipping_id'=>'required',
                'size'=>'required',
                'color'=>'required',
                'quantity'=>'required',
                // 'price'=>'required',
                'total_amount'=>'required',
                'address'=>'required',
                'longitude'=>'required',
                'latitude'=>'required',
                'method'=>'required',
            ]);
            if($validator->fails()) {
                return response()->json(['success'=>false,'message'=>$validator->errors()]);    
            }
            $wallet =Wallet::where('user_id',Auth::user()->id)->first();

            if($request->method == 'wallet')
            {
            
                if($wallet->amount < $request->total_amount)
                {
                    return response()->json(['success'=>false,'message'=>"You Don't hane enough amount in your wallet"]);    
                }
                else
                {
                    $wallet->amount = $wallet->amount - $request->total_amount;
                    $wallet->save(); 

                }
            }
            else
            {
                $token = $request->input('stripeToken');
                Stripe\Charge::create ([
                    "amount" => $request->total_amount * 100,
                    "currency" => "usd",
                    "source" => $request->stripeToken,
                    "description" => "This is a Pay Me First Checkout transaction" 
                ]);
            }

            $size = json_decode($request->size);
            $color = json_decode($request->color);
            $quantity = json_decode($request->quantity);
            foreach(json_decode($request->product_id) as $key => $productid)
            {
                $product = Product::find($productid);
                OrderDetail::create([
                    'order_no' => $orderid,
                    'product_id' => $productid,
                    'color' => $color[$key],
                    'size' => $size[$key],
                    'quantity' => $quantity[$key],
                ]);
            }

            $order=new Order();
            $order_data=$request->all();
            $order->order_number =  $orderid;
            $order->user_id =  Auth::user()->id;
            $order->shipping_id = $request->shipping_id;
            $order->total_amount = $request->total_amount;
            $order->country = $request->country;
            $order->longitude = $request->longitude;
            $order->address = $request->address;
            $order->latitude = $request->latitude;
            $order->payment_method = $request->method;
            $order->save();
            
            return response()->json(['success'=>true,'message'=>'Order Placed Successfully','data'=>$order]);
        }
        catch(\Eception $e)
        {
            return response()->json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    public function confirm(Request $request)
    {
        $response = $this->gateway->confirm([
            'paymentIntentReference' => $request->input('payment_intent'),
            'returnUrl' => $this->completePaymentUrl,
        ])->send();
         
        if($response->isSuccessful())
        {
            $response = $this->gateway->capture([
                'amount' => $request->input('amount'),
                'currency' => env('STRIPE_CURRENCY'),
                'paymentIntentReference' => $request->input('payment_intent'),
            ])->send();
 
            $arr_payment_data = $response->getData();
 
            $this->store_payment([
                'payment_id' => $arr_payment_data['id'],
                'payer_email' => session('payer_email'),
                'amount' => $arr_payment_data['amount']/100,
                'currency' => env('STRIPE_CURRENCY'),
                'payment_status' => $arr_payment_data['status'],
            ]);
 
            return redirect("payment")->with("success", "Payment is successful. Your payment id is: ". $arr_payment_data['id']);
        }
        else
        {
            return redirect("payment")->with("error", $response->getMessage());
        }
    }
 
    public function store_payment($arr_data = [])
    {
        $isPaymentExist = Payment::where('payment_id', $arr_data['payment_id'])->first();  
  
        if(!$isPaymentExist)
        {
            $payment = new Payment;
            $payment->payment_id = $arr_data['payment_id'];
            $payment->payer_email = $arr_data['payer_email'];
            $payment->amount = $arr_data['amount'];
            $payment->currency = env('STRIPE_CURRENCY');
            $payment->payment_status = $arr_data['payment_status'];
            $payment->save();
        }
    }
}
