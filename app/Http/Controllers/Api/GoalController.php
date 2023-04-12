<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Goal;
use App\Models\User;
use App\Models\Payment;
use App\Models\TemporaryWallet;
use App\Models\Wallet;
use App\Models\GoalHistory;
use Validator;
use Auth;

class GoalController extends BaseController
{
    
    public function __construct()
    {
          $stripe = \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function list(GoalHistory $goalhistory)
    {
        $goal =  GoalHistory::with('user')->where('user_id',Auth::user()->id)->get();
        return response()->json(['success'=>true,'message'=>'Goal History','data'=>$goal]);
    }

    public function set_goal(Request $request)
    {
        
        try
        { 
            
            $valiadator = Validator::make($request->all(),[
                'amount_save' =>'required',
                'plan' =>'required',
                'starting_date' => 'required',
                'ending_date' => 'required',
                'number_deduction' => 'required',
                'amount_per_deduction' => 'required',
            ]);

            if($valiadator->fails())
            {
                return $this->sendError($valiadator->errors()->first());
            }        
            $amount = $request->amount_save;
            $charge = \Stripe\Charge::create([
                'amount' => $amount*100,
                'currency' => 'usd',
                'customer' => Auth::user()->stripe_id
            ]);
            
            $data = Goal::create([
                'goal_name' => $request->goal_name,
                'amount_save' => $request->amount_save,
                'plan' => $request->plan,
                'starting_date' => $request->starting_date,
                'ending_date' => $request->ending_date,
                'number_deduction' => $request->number_deduction,
                'amount_per_deduction' => $request->amount_per_deduction,
                'cnd' => 1,
                'user_id' => Auth::id(),
                'created_at' => date('Y-m-d')
            ]);
            
            $amount = $request->amount_per_deduction;

            Payment::create([
                'amount' => $amount,
                'customer_id' => Auth::user()->stripe_id,
                'currency' => 'usd',
            ]);
			if(!Auth::user()->is_goal == true)
			{
				Auth::user()->update([
					'is_goal' =>true
				]);
			}
            
            $temporarywallet = TemporaryWallet::where('user_id',Auth::user()->id)->first();
            $temporarywallet->update([
                'amount' => $request->amount_per_deduction,
            ]);
            
            
            $user = User::with(['child','goal','temporary_wallet','wallet','payments'])->where('id',Auth::user()->id)->first();
            // $wallet = TemporaryWallet::where('user_id',Auth::user()->id)->get();

            return response()->json(['success'=>true,'message'=>'Record Created Successfully','data'=>$user]);
       
        }
        catch(\Eception $e){
            return $this->sendError($e->getMessage());

        }
    }
}
