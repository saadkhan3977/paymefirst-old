<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController as BaseController;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;
use Image;
use File;
use Auth;
use Validator;
class UserController extends BaseController
{
	public function __construct()
    {
          $stripe = \Stripe\Stripe::setApiKey('sk_test_51LCrVHHNvw3AIrpxjbOuGKoRaQ3K68ZDXrgU41PRmyDb9eH7h9qShHEn1T8gEUV7amg1TfNSy1cVXWaREFgcfmMr00yqKik6dg');
    }

	public function un_reead_notification()
	{
		$notification = Auth::user()->unreadNotifications;
		$notificationold = Auth::user()->readNotifications;
		$unread = count(Auth::user()->unreadNotifications); 
		$read = count(Auth::user()->readNotifications); 
		// return $notification[0]->data['title']; 
		$data = null;
		if($notification)
		{
			foreach($notification as $row)
			{
				$data[] = [
					'id' => $row->id,
					'title' => $row->data['title'],
					'description' => $row->data['description'],
					'created_at' => $row->data['time']
				];
				// $data[] = $row->data;
			}
		}
			
		$olddata = null;
		if($notificationold){

			foreach($notificationold as $row)
			{
				$olddata[] = [
					'id' => $row->id,
					'title' => $row->data['title'],
					'description' => $row->data['description'],
					'read_at' => $row->data['time']
				];
			}
		}
		return response()->json(['success'=>true,'unread'=> $unread,'read'=> $read,'un_readnotification' => $data ,'read_notification' => $olddata]);
	}
	
	
	public function read_notification(Request $request)
	{
		try{
			$validator = Validator::make($request->all(),[
				'notification_id' => 'required',
			]);
			if($validator->fails())
			{

				return response()->json(['success'=>false,'message'=> $validator->errors()->first()]);
			}

			$notification= Notification::find($request->notification_id);
			if($notification){
				$notification->read_at = date(now());
				$notification->save();
				$status= $notification;
				if($status)
				{
					return response()->json(['success'=>true,'message'=> 'Notification successfully deleted']);
				}
				else
				{
					return response()->json(['success'=>false,'message'=> 'Error please try again']);
				}
			}
			else
			{
				return response()->json(['success'=>false,'message'=> 'Notification not found']);
			}
		}
		catch(\Eception $e)
		{
			return response()->json(['error'=>$e->getMessage()]);
	   	}
	}

    public function profile(Request $request)
    {
        try{
                    //$user = User::findOrFail(Auth::id());
					$user = User::with(['goal','temporary_wallet','wallet','payments'])->where('id',Auth::user()->id)->first();
                    $validator = Validator::make($request->all(),[
                        'first_name' =>'string',
                        'last_name' =>'string',
                        'phone' =>'numeric',
                        'email' => 'email|unique:users,email,'.$user->id,
                        'country'=>'string',
						'photo' => 'image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
                    ]);
                    if($validator->fails())
                    {
                     return $this->sendError($validator->errors()->first());
            
                    }
                    $profile = $user->photo;

					if($request->hasFile('photo')) 
					{
							$file = request()->file('photo');
							$fileName = md5($file->getClientOriginalName() . time()) . "PayMefirst." . $file->getClientOriginalExtension();
							$file->move('uploads/user/profiles/', $fileName);  
							$profile = asset('uploads/user/profiles/'.$fileName);
					}
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->email = $request->email;
                $user->country = $request->country;
                $user->photo = $profile;
                $user->save();

                return response()->json(['success'=>true,'message'=>'Profile Updated Successfully','user_info'=>$user]);

            }catch(\Eception $e){
                
                 return $this->sendError($e->getMessage());
        
               
            }
                
   
    }
	public function current_plan(Request $request)
	{
		try{
		//$user= User::findOrFail(Auth::id());
		$user = User::with(['goal','temporary_wallet','wallet','payments'])->where('id',Auth::user()->id)->first();
		
		$amount = 100;
		$charge = \Stripe\Charge::create([
			'amount' => $amount,
			'currency' => 'usd',
			'customer' => $user->stripe_id,
		]);
		if($request->current_plan == 'basic')
		{		
			$user->update(['current_plan' =>"premium",'card_change_limit'=>'1','created_plan'=> \Carbon\Carbon::now()]);
			return response()->json(['success'=>true,'message'=>'Current Plan Updated Successfully','user_info'=>$user,'payment' => $charge]);

		}
		elseif($request->current_plan == 'premium')
		{
			$user->update(['current_plan' =>"basic",'card_change_limit'=>'0','created_plan'=> \Carbon\Carbon::now()]);
		
		 return response()->json(['success'=>true,'message'=>'Current Plan Updated Successfully','user_info'=>$user]);
		}
		else
		{
			return $this->sendError("Invalid Body ");
		}
		}
		catch(\Exception $e){
	  return $this->sendError($e->getMessage());

		}
		
	}

    
}
