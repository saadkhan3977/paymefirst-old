<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Goal;
use App\Models\GoalHistory;
use App\Models\Payment;
use App\Models\TemporaryWallet;
use App\Models\Wallet;
use App\Models\Tranasaction;
use Carbon\Carbon;
use Helper;

class RecurringPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
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
                    $days = Carbon::parse($payment->created_at)->diffInDays(Carbon::now());
                    $temporarywallet = TemporaryWallet::where('user_id',$user->id)->first();
                    $wallet = Wallet::where('user_id',$user->id)->first();
                    
                    if($user->goal->cnd < $user->goal->number_deduction)
                    {
                        if($user->goal->plan == 'Weekly')
                        {
                            if($days == '6')
                            {
                                Helper::twodaybefore();
                            }
                            if($days == '7')
                            {
                                Helper::onedaybefore();
                            }
                            if( $days == '8')
                            {
                                $charge = \Stripe\Charge::create([
                                    'amount' => $user->goal->amount_per_deduction*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

                                Payment::create([
                                    'amount' => $payment->amount + $user->goal->amount_per_deduction,
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);

                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $user->goal->amount_per_deduction,
                                ]);

                                
                                Helper::payment_charge();

                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete();
                                    
                                    Payment::where('customer_id',$user->stripe_id)->delete();
                                    Tranasaction::create([
                                        'user_id' => $user->id,
                                        'amount' => $wallet->amout + $temporarywallet->amount,
                                        'date' => date('M-d-Y'),
                                        'reason' => 'Goal Complete',
                                        'type' => 'Credit',
                                        'status' => 'Credit',
                                    ]);
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

                            if($days == '13')
                            {
                                Helper::twodaybefore();
                            }
                            if($days == '14')
                            {
                                Helper::onedaybefore();
                            }
                            if($days == '15')
                            {

                                $penalty =  $user->goal->amount_save / 10;
                                $chargeamount = $user->goal->amount_per_deduction + $penalty;
                                $charge = \Stripe\Charge::create([
                                    'amount' => $chargeamount*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

                                Payment::create([
                                    'amount' => $payment->amount + $user->goal->amount_per_deduction,
                                    'penalty' => $penalty,
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);

                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $user->goal->amount_per_deduction,
                                ]);

                                
                                Helper::payment_charge();

                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete();
                                    
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
                            
                            if($days == '20')
                            {
                                Helper::twodaybefore();
                            }
                            if($days == '21')
                            {
                                Helper::onedaybefore();
                            }
                            if($days == '22')
                            {

                                $penalty =  $user->goal->amount_save / 10;
                                $chargeamount = $user->goal->amount_per_deduction + $penalty;
                                $charge = \Stripe\Charge::create([
                                    'amount' => $chargeamount*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

                                Payment::create([
                                    'amount' => $payment->amount + $user->goal->amount_per_deduction,
                                    'penalty' => $penalty,
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);

                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $user->goal->amount_per_deduction,
                                ]);
                                
                                Helper::payment_charge();

                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete();
                                    
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

                            if($days == '27')
                            {
                                Helper::twodaybefore();
                            }
                            if($days == '28')
                            {
                                Helper::onedaybefore();
                            }
                            if($days == '29')
                            {

                                $penalty =  $user->goal->amount_save / 10;
                                $chargeamount = $user->goal->amount_per_deduction + $penalty;
                                $charge = \Stripe\Charge::create([
                                    'amount' => $chargeamount*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

                                Payment::create([
                                    'amount' => $payment->amount + $user->goal->amount_per_deduction,
                                    'penalty' => $penalty,
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);

                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $user->goal->amount_per_deduction,
                                ]);
                                
                                // Notification payment
                                Helper::payment_charge();

                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete();
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
                    
                        if($user->goal->plan == 'bi-weekly')
                        {
                            // return $user->stripe_id;
                            if($days == '12')
                            {
                                Helper::twodaybefore();
                            }
                            if($days == '13')
                            {
                                Helper::onedaybefore();
                            }
                            if($days == '15')
                            {
                                $charge = \Stripe\Charge::create([
                                    'amount' => $user->goal->amount_per_deduction*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

                                Payment::create([
                                    'amount' => $user->goal->amount_per_deduction,
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);
                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $user->goal->amount_per_deduction,
                                ]);
                                
                                Helper::payment_charge();
                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete();                                  
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
                                Helper::twodaybefore();
                            }
                            if($days == '29')
                            {
                                Helper::onedaybefore();
                            }
                            if($days == '30')
                            {
                                $charge = \Stripe\Charge::create([
                                    'amount' => $user->goal->amount_per_deduction*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

                                Payment::create([
                                    'amount' => $user->goal->amount_per_deduction,
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);
                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $user->goal->amount_per_deduction,
                                ]);
                                
                                Helper::payment_charge();
                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete();                                  
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
                                Helper::twodaybefore();
                            }
                            if($days == '30')
                            {
                                Helper::onedaybefore();
                            }
                            if($monthdays > '30')
                            {
                                $charge = \Stripe\Charge::create([
                                    'amount' => $user->goal->amount_per_deduction*100,
                                    'currency' => 'usd',
                                    'customer' => $user->stripe_id,
                                ]);

                                Payment::create([
                                    'amount' => $user->goal->amount_per_deduction,
                                    'customer_id' => $user->stripe_id,
                                    'currency' => 'usd',
                                ]);
                                $user->goal->update([
                                    'cnd' => $user->goal->cnd + 1
                                ]);
                                $temporarywallet->update([
                                    'amount' => $temporarywallet->amount + $user->goal->amount_per_deduction,
                                ]);

                                Helper::payment_charge();
                                if($user->goal->cnd == $user->goal->number_deduction)
                                {
                                    Helper::goal_complete();                                    
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
// amount \
// date
// reason
// type {'dabit/cradit'}
// status
// user id
