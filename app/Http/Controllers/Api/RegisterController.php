<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Payment;
use App\Models\Goal;
use App\Models\Child;
use App\Models\TemporaryWallet;
use App\Models\Tranasaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Mail\SendVerifyCode;
use Mail;
use Carbon\Carbon;
use Twilio\Rest\Client; 
use Hash;
use Image;
use File;
use Stripe\Customer;
use Helper;


class RegisterController extends BaseController
{

    public function __construct()
    {
          $stripe = \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function register(Request $request)
    {
		$validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'child_name' => 'required|string',
            'child_age' => 'required|string',
            'child_gander' => 'required|string',
            'child_image' =>'required|string',
            'passcode' => 'required|numeric',
            'phone' => 'required|numeric|unique:users',
            'country' => 'required|string',
            'email' => 'required|email|unique:users',			
            'password' => 'required|min:8',
            'c_password' => 'required|same:password',
			'photo' => 'image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
        ]);      
        if($validator->fails())
        {
		 return $this->sendError($validator->errors()->first());

        }
		$profile = null;
				if($request->hasFile('photo')) 
				{$file = request()->file('photo');
				$fileName = md5($file->getClientOriginalName() . time()) . "PayMefirst." . $file->getClientOriginalExtension();
				$file->move('uploads/user/profiles/', $fileName);  
				$profile = asset('uploads/user/profiles/'.$fileName);
				}
        $input = $request->except(['c_password','child_name','child_age','child_gander'],$request->all());
        $input['password'] = bcrypt($input['password']);
        $input['photo'] = $profile;
		$input['current_plan'] = 'basic';
		$input['created_plan'] = Carbon::now();
    	$input['email_verified_at'] = Carbon::now();
		$input['is_goal'] = false;
        $input['email_code'] = mt_rand(9000, 9999);
        $user = User::create($input);

        Child::create([
            'user_id' => $user->id,
            'name' => $request->child_name,
            'age' => $request->child_age,
            'gander' => $request->child_gander,
            'image' => $request->child_image,
        ]);
        
        TemporaryWallet::create([
            'user_id' => $user->id,
            'amount' => 0,
        ]);
        
        Wallet::create([
            'user_id' => $user->id,
            'pending_amount' => 0,
            'amount' => 0,
            'withdraw' => 0,
        ]);
        
        Mail::to($user->email)->send(new SendVerifyCode($input['email_code']));
        $token =  $user->createToken('app_api')->accessToken;
		$users = User::with(['child','goal','temporary_wallet','wallet','payments'])->where('id',$user->id)->first();
		return response()->json(['success'=>true,'message'=>'User register successfully' ,'token'=>$token,'user_info'=>$users]);
    }

    public function login(Request $request)
    {   
        if(!empty($request->email) || !empty($request->password))
        {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users',
                'current_role' => 'required',        
                'password' => 'required',        
            ]);  
            if($validator->fails()){
				return $this->sendError($validator->errors()->first());
            }
            $user = User::firstWhere('email',$request->email);

            
            // if($user->email_verified_at != null)
            // {
                if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
                    $user = User::with(['child','goal','temporary_wallet','wallet','payments'])->where('id',Auth::user()->id)->first(); 
                    $user->current_role = $request->current_role;
                    $user->save();
                    $users = Auth::user();
                    $token =  $users->createToken('app_api')->accessToken; 
		            return response()->json(['success'=>true,'message'=>'User Logged In successfully' ,'token'=>$token,'user_info'=>$user]);
               } 
                else{ 
				return $this->sendError('Unauthorised User');

                } 

        //     }else{
		// return $this->sendError('Email Not Verified , Check Email');

        //     }

        }else{
		return $this->sendError('Email & Password are Required');

        }
      
    }

    public function me()
    {
        $user = User::with(['child','goal','temporary_wallet','wallet','payments'])->where('id',Auth::user()->id)->first(); 
        return response()->json(['success'=>true,'message'=>'User Fetch successfully','user_info'=>$user]);
    }
    public function logout()
    {
        if(Auth::check())
        {
            $user = Auth::user()->token();
            $user->revoke();
            $success['success'] =true; 
            return $this->sendResponse($success, 'User Logout successfully.');
        }else{
            return $this->sendError('No user in Session .');
        }
    }
    public function user(Request $request)
    {
        if(Auth::check())
        {
            $success['user_info'] = $request->user();
        return $this->sendResponse($success, 'Current user successfully.');
        }
        else{
            return $this->sendError('No user in Session .');
        }
    }
    public function verify(Request $request)
    {
		$validator = Validator::make($request->all(),['email_code'=>'required']);
        if($validator->fails()){
            return $this->sendError($validator->errors()->first());       
            }
        $user = User::firstWhere('email_code',$request->email_code);
        if($user == null)
        {
            return $this->sendError('Token Expire or Invalid');
        }else{
            $user->update(['email_verified_at'=>Carbon::now(),'email_code'=>null]);
            $success['success'] =true; 
            return $this->sendResponse($success, 'Email verified Successfully');
        }
    }
    public function change_password(Request $request)
    {
      try{
      $validator = Validator::make($request->all(),[
          'current_password' => 'required',
          'new_password' => 'required|same:confirm_password|min:8',
          'confirm_password' => 'required',
      ]);
      if($validator->fails()){
        return $this->sendError($validator->errors()->first());       
        }
        $user = Auth::user();

      if (!Hash::check($request->current_password,$user->password)) {
        return $this->sendError(['error'=>'Current Password Not Matched']);
      }
      $user->password = Hash::make($request->new_password);
      $user->save();
      return response()->json(['success'=>true,'message'=>'Password Successfully Changed','user_info'=>$user]);
         }catch(\Eception $e){
           return $this->sendError($e->getMessage());    
        }
    }   

    public function noauth(){
	 return $this->sendError('session destroyed , Login to continue!');
	
	}
	
	public function cron_plane()
	{
        // return Carbon::now()->addDays(7)->day();
        try 
        {
            // $stripe = \Stripe\Stripe::setApiKey('sk_test_51LCrVHHNvw3AIrpxjbOuGKoRaQ3K68ZDXrgU41PRmyDb9eH7h9qShHEn1T8gEUV7amg1TfNSy1cVXWaREFgcfmMr00yqKik6dg');
            $stripe = \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            $users = User::with('goal')->get();
            foreach($users as $user)
            {
                
                if($user->goal)
                {
                    $payment = Payment::where('customer_id',$user->stripe_id)->orderBy('id','desc')->first();                
                    // return $payment;
                    // return $payment->created_at;
                    $days = Carbon::parse($payment->created_at)->diffInDays(Carbon::now());
                     $temporarywallet = TemporaryWallet::where('user_id',$user->id)->first();
                    $wallet = Wallet::where('user_id',$user->id)->first();
                    
                    if($user->goal->cnd < $user->goal->number_deduction)
                    { 
                        if($user->goal->plan == 'Weekly')
                        {

                            if($days == '6')
                            {
                                Helper::twodaybefore($user->id);
                            }
                            if($days == '7')
                            {
                                Helper::onedaybefore($user->id);
                            }
                            if( $days == '8')
                            {
                                $perdeduction = $user->goal->amount_per_deduction;
                                $charge = \Stripe\Charge::create([
                                    'amount' => $perdeduction*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);
								
                               // return $chargp;
								Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0) ,
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);

                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $perdeduction,
                                ]);
                                
                                Helper::payment_charge($user->id);
                                
                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete($user->id);
                                    
                                    Payment::where('customer_id',$user->stripe_id)->delete();
                                    /* Tranasaction::create([
                                        'user_id' => $user->id,
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                        'date' => date('M-d-Y'),
                                        'reason' => 'Goal Complete',
                                        'type' => 'Credit',
                                        'status' => 'Credit',
                                    ]); */
                                    Helper::goal_history($user->id);
                                    $user->goal->delete();
                                    
                                    $point = Helper::goalpoint($user->id);
                                    
                                    Tranasaction::create([
                                        'user_id' => $user->id,
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                        'date' => date('M-d-Y'),
                                        'reason' => 'Goal Complete',
                                        'type' => 'Credit',
                                        'status' => 'Credit',
                                    ]);
									
                                    
                                    
									
									
                                    $wallet->update([
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                    ]);

                                    $user->update([
                                        'is_goal' => 0,
                                        'points' => $point
                                    ]);

                                    $temporarywallet->update([
                                        'amount' => 0,
                                    ]);
                                }
                            }

                            if($days == '13')
                            {
                                Helper::twodaybefore($user->id);
                            }
                            if($days == '14')
                            {
                                Helper::onedaybefore($user->id);
                            }
                            if($days == '15')
                            {

                                // $penalty =  $user->goal->amount_save ;
                                // $chargeamount = $user->goal->amount_per_deduction + $penalty;
                                $chargeamount = $user->goal->amount_per_deduction * 2;
								$perdeduction = $user->goal->amount_per_deduction;
                                
                                $charge = \Stripe\Charge::create([
                                    'amount' => round($chargeamount),
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

								Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0),
                                    'status' => 'Late Payment',
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
									
								$payment = Payment::where('customer_id',$user->stripe_id)->orderBy('id','desc')->first(); 
                                
                                Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0) ,
                                    'status' => 'Late Payment',
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 2
                                ]);

                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $chargeamount,
                                ]);

                                
                                Helper::payment_charge($user->id);
                                

                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete($user->id);
                                    
                                    Payment::where('customer_id',$user->stripe_id)->delete();
                                    Helper::goal_history($user->id);
                                    Tranasaction::create([
                                        'user_id' => $user->id,
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                        'date' => date('M-d-Y'),
                                        'reason' => 'Goal Complete',
                                        'type' => 'Credit',
                                        'status' => 'Credit',
                                    ]);

                                    
									
									$user->goal->delete();
                                    $wallet->update([
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                    ]);
                                    $point = Helper::lategoalpoint(2,$user->id);
                                
                                    $user->update([
                                        'is_goal' => 0,
										'points' => $point
                                    ]);

                                    $temporarywallet->update([
                                        'amount' => 0,
                                    ]);
                                }
                            }
                            
                            if($days == '20')
                            {
                                Helper::twodaybefore($user->id);
                            }
                            if($days == '21')
                            {
                                Helper::onedaybefore($user->id);
                            }
                            if($days == '22')
                            {

                                $chargeamount = $user->goal->amount_per_deduction * 3;
                                $perdeduction = $user->goal->amount_per_deduction;
								
                                $charge = \Stripe\Charge::create([
                                    'amount' => $chargeamount*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);
                                Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0) ,
                                    'penalty' => 'Late Payment',
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $payment = Payment::where('customer_id',$user->stripe_id)->orderBy('id','desc')->first();
                                Payment::create([
                                    'amount' =>round(($payment) ? $perdeduction + $payment->amount : 0),
                                    'penalty' => 'Late Payment',
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
								
								$payment = Payment::where('customer_id',$user->stripe_id)->orderBy('id','desc')->first();
                                Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0),
                                    'penalty' => 'Late Payment',
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 3
                                ]);


                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $chargeamount,
                                ]);
                                
                                Helper::payment_charge($user->id);

                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete($user->id);
                                    
                                    Payment::where('customer_id',$user->stripe_id)->delete();
                                    Helper::goal_history($user->id);
                                    Tranasaction::create([
                                        'user_id' => $user->id,
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                        'date' => date('M-d-Y'),
                                        'reason' => 'Goal Complete',
                                        'type' => 'Credit',
                                        'status' => 'Credit',
                                    ]);
                                   
                                    $point = Helper::lategoalpoint(4,$user->id);
									
									$user->goal->delete();
                                    $wallet->update([
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                    ]);

                                    $user->update([
                                        'is_goal' => 0,
										'points' => $point
                                    ]);

                                    $temporarywallet->update([
                                        'amount' => 0,
                                    ]);
                                }
                            }

                            if($days == '27')
                            {
                                Helper::twodaybefore($user->id);
                            }
                            if($days == '28')
                            {
                                Helper::onedaybefore($user->id);
                            }
                            if($days == '29')
                            {

                                $chargeamount = $user->goal->amount_per_deduction * 4;
								$perdeduction = $user->goal->amount_per_deduction;
                                
                                $charge = \Stripe\Charge::create([
                                    'amount' => $chargeamount*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

                                Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0) ,
                                    'penalty' => 'Late Payment',
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $payment = Payment::where('customer_id',$user->stripe_id)->orderBy('id','desc')->first();
                                Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0),
                                    'penalty' => 'Late Payment',
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
								$payment = Payment::where('customer_id',$user->stripe_id)->orderBy('id','desc')->first();
                                Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0),
                                    'penalty' => 'Late Payment',
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
								$payment = Payment::where('customer_id',$user->stripe_id)->orderBy('id','desc')->first();
                                Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0),
                                    'penalty' => 'Late Payment',
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);

                                
                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $chargeamount,
                                ]);
                                
                                // Notification payment
                                Helper::payment_charge($user->id);

                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete($user->id);
                                    Payment::where('customer_id',$user->stripe_id)->delete();
                                    Helper::goal_history($user->id);
                                    Tranasaction::create([
                                        'user_id' => $user->id,
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                        'date' => date('M-d-Y'),
                                        'reason' => 'Goal Complete',
                                        'type' => 'Credit',
                                        'status' => 'Credit',
                                    ]);
                                    $point = Helper::lategoalpoint(6,$user->id);
									
									$user->goal->delete();
                                    $wallet->update([
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                    ]);

                                    $user->update([
                                        'is_goal' => 0,
										'points' => $point
                                    ]);

                                    $temporarywallet->update([
                                        'amount' => 0,
                                    ]);
                                }
                            } 
                        }
                    
                        if($user->goal->plan == 'bi-weekly')
                        {
                            // return $user->stripe_id;
                            if($days == '12')
                            {
                                Helper::twodaybefore($user->id);
                            }
                            if($days == '13')
                            {
                                Helper::onedaybefore($user->id);
                            }
                            if($days == '15')
                            {
								$perdeduction = $user->goal->amount_per_deduction;
                                $charge = \Stripe\Charge::create([
                                    'amount' => $user->goal->amount_per_deduction*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

                                Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0),
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);
                                
                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $perdeduction,
                                ]);
                                
                                Helper::payment_charge($user->id);
                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete($user->id);                                  
                                    Payment::where('customer_id',$user->stripe_id)->delete();
                                    Helper::goal_history($user->id);
                                    
                                    Tranasaction::create([
                                        'user_id' => $user->id,
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                        'date' => date('M-d-Y'),
                                        'reason' => 'Goal Complete',
                                        'type' => 'Credit',
                                        'status' => 'Credit',
                                    ]);
                                    
                                    $point = Helper::goalpoint();
									
									$user->goal->delete();
                                    $wallet->update([
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                    ]);

                                    $user->update([
                                        'is_goal' => 0,
										'points' => $point
                                    ]);

                                    $temporarywallet->update([
                                        'amount' => 0,
                                    ]);
                                }
                            }
                            
                            if($days == '28')
                            {
                                Helper::twodaybefore($user->id);
                            }
                            if($days == '29')
                            {
                                Helper::onedaybefore($user->id);
                            }
                            if($days == '30')
                            {
								$chargeamount = $user->goal->amount_per_deduction * 2;
								$perdeduction = $user->goal->amount_per_deduction;
                                $charge = \Stripe\Charge::create([
                                    'amount' => $chargeamount*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

                                Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0),
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
								$payment = Payment::where('customer_id',$user->stripe_id)->orderBy('id','desc')->first();
								Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0),
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);
                                
								
                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $chargeamount,
                                ]);
                                
                                Helper::payment_charge($user->id);
                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete($user->id);                                  
                                    Payment::where('customer_id',$user->stripe_id)->delete();
                                    Helper::goal_history($user->id);
                                    
                                    Tranasaction::create([
                                        'user_id' => $user->id,
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                        'date' => date('M-d-Y'),
                                        'reason' => 'Goal Complete',
                                        'type' => 'Credit',
                                        'status' => 'Credit',
                                    ]);
                                    
                                    $point = Helper::lategoalpoint();
									
									$user->goal->delete();
                                    $wallet->update([
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                    ]);

                                    $user->update([
                                        'is_goal' => 0,
										'points' => $point
                                    ]);

                                    $temporarywallet->update([
                                        'amount' => 0,
                                    ]);
                                }
                            }
                        }
                        
                        if($user->goal->plan == 'Monthly')
                        {
                            if($days == '29')
                            {
                                Helper::twodaybefore($user->id);
                            }
                            if($days == '30')
                            {
                                Helper::onedaybefore($user->id);
                            }
                            if($monthdays > '30')
                            {
								$perdeduction = $user->goal->amount_per_deduction;
                                $charge = \Stripe\Charge::create([
                                    'amount' => $user->goal->amount_per_deduction*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

                                Payment::create([
                                    'amount' => round(($payment) ? $perdeduction + $payment->amount : 0),
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);
                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $perdeduction,
                                ]);

                                Helper::payment_charge($user->id);
                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete($user->id);                                    
                                    Payment::where('user_id',$user->id)->delete();
                                    Helper::goal_history($user->id);
                                    
                                    Tranasaction::create([
                                        'user_id' => $user->id,
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                        'date' => date('M-d-Y'),
                                        'reason' => 'Goal Complete',
                                        'type' => 'Credit',
                                        'status' => 'Credit',
                                    ]);
                                    $point = Helper::goalpoint();
									
									$user->goal->delete();
                                    $wallet->update([
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                    ]);

                                    $user->update([
                                        'is_goal' => 0,
										'points' => $point
                                    ]);

                                    $temporarywallet->update([
                                        'amount' => 0,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
        catch(\Stripe\Exception\CardException $e) {
            // Display the error message to the customer
            return response()->json(['error'=>$e->getMessage()]);
        }

    }

}
