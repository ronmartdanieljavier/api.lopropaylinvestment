<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\User;

class ApiUserController extends Controller
{
//    public function __construct()
//    {
//        $user = JWTAuth::parseToken()->toUser();
//
//        return response()->json(['user'=>$user]);
//    }

    public function getAuthDetails ()
    {
        $user = JWTAuth::parseToken()->toUser();
        return response()->json(['user'=>$user]);
    }
    public function loadDashboard()
    {
        $user = JWTAuth::parseToken()->toUser();
        $getTodayForPayoutsAmount = DB::table('tbl_user_payouts')
            ->leftjoin('tbl_user_principal','tbl_user_payouts.principal_id','=','tbl_user_principal.id')
            ->where('tbl_user_payouts.payout_date','<=',DB::raw('DATE(now())'))
            ->where('tbl_user_payouts.status','0')
            ->where('tbl_user_principal.username',$user->username)
            ->selectRaw("ROUND(SUM(tbl_user_payouts.daily_growth_amount),2) as totalPayoutAmount,COUNT(DISTINCT tbl_user_payouts.payout_date) AS totalPayoutCount")
            ->first();
        $getTodayPayoutsAmount = DB::table('tbl_user_payouts')
            ->leftjoin('tbl_user_principal','tbl_user_payouts.principal_id','=','tbl_user_principal.id')
            ->where(DB::raw('DATE(tbl_user_payouts.released_date)'),'=',DB::raw('DATE(now())'))
            ->where('tbl_user_payouts.status','1')
            ->where('tbl_user_principal.username',$user->username)
            ->selectRaw("ROUND(SUM(tbl_user_payouts.daily_growth_amount),2) as totalPayoutAmount,COUNT(DISTINCT tbl_user_payouts.payout_date) AS totalPayoutCount")
            ->first();
        $getPrincipalAmount = DB::table('tbl_user_principal')
            ->where('username',$user->username)
            ->selectRaw('ROUND(SUM(principal),2) as totalPrincipal')
            ->first();

        $totalPayoutCount = 0;
        $totalPayoutAmount = 0;

        if($getTodayForPayoutsAmount){
            $totalPayoutCount = $getTodayForPayoutsAmount->totalPayoutCount;

            if($totalPayoutCount>0){
                $totalPayoutAmount = $getTodayForPayoutsAmount->totalPayoutAmount;
            }
        }

        $totalTodayPayoutCount = 0;
        $totalTodayPayoutAmount = 0;

        if($getTodayPayoutsAmount) {
            $totalTodayPayoutCount = $getTodayPayoutsAmount->totalPayoutCount;
            if($totalTodayPayoutCount>0){
                $totalTodayPayoutAmount = $getTodayPayoutsAmount->totalPayoutAmount;
            }

        }

        $principal = 0;

        if($getPrincipalAmount){
            $principal = $getPrincipalAmount->totalPrincipal;
        }

        $data = array(
            "totalPayoutCount" => $totalPayoutCount,
            "totalPayoutAmount" => $totalPayoutAmount,
            "totalTodayPayoutCount" => $totalTodayPayoutCount,
            "totalTodayPayoutAmount" => $totalTodayPayoutAmount,
            "totalPrincipal" => $principal
        );

        return response()->json($data, 201);
    }
    public function loadCurrentPayouts()
    {
        $user = JWTAuth::parseToken()->toUser();

        $data = DB::select("
            SELECT 
                b.id,
                b.username,
                CONCAT(c.fname,' ',c.lname) AS name,
                a.payout_date,
                ROUND(SUM(a.daily_growth_amount),2) as amount,
                a.status as status
            FROM 
                tbl_user_payouts a
            LEFT JOIN tbl_user_principal b
            ON a.principal_id = b.id
            LEFT JOIN users c 
            ON b.username = c.username
            WHERE 
                a.payout_date <= DATE(now())
                AND b.username = '$user->username'
            GROUP BY 
                b.id,
                b.username,
                CONCAT(c.fname,' ',c.lname),
                a.payout_date,
                a.status
            ORDER BY a.payout_date ASC
        ");
        return response()->json(array("payout"=>$data), 201);
    }
    public function loadInvestorByPrincipalIdByPaydate ()
    {
        //javier
        $id = Input::get('id');
        $paydate = Input::get('paydate');
        $getUserPayout = DB::table('tbl_user_payouts')
            ->where('principal_id',$id)
            ->where('payout_date',$paydate)
            ->get();
        $data = DB::select("
            SELECT 
                principal_id,
                based_amount AS principal,
                CONCAT(COUNT(days),'/', days) AS days,
                payout_date,
                CASE WHEN status = 0 THEN 'FOR PAYOUT' ELSE 'CLAIMED' END AS status,
                ROUND(SUM(daily_growth_amount),2) AS payoutAmount
            FROM 
                tbl_user_payouts
            WHERE 
                principal_id = $id 
                AND payout_date = '$paydate'
            GROUP BY 
                principal_id,
                principal,
                days,
                CASE WHEN status = 0 THEN 'FOR PAYOUT' ELSE 'CLAIMED' END,
                payout_date
            ORDER BY payout_date ASC
        ");
        return response()->json(['payoutHeader' => $data ,'payoutList'=>$getUserPayout], 201);
    }
    public function loadInvestorByPrincipalId ()
    {
        $username = Input::get('id');
        $getUsername = DB::table('tbl_user_principal')->where('id',$username)->first();
        if ($getUsername) {
            $data = DB::table('users')->where('username', $getUsername->username)->where('act_type','<>','ADMIN')->orderBy('id','DESC')->first();
            $principal = DB::table('tbl_user_principal')->where('id',$username)->get();
            return response()->json(['data'=>$data,'principalList'=>$principal], 201);
        }

    }
    public function confirmUserPassword() {
        $user = JWTAuth::parseToken()->toUser();

        $inputPassword = Input::get('password');

//        $checkUserIfValid = DB::table('users')
//            ->where('username', $user->username)
//            ->where('password', bcrypt($inputPassword))
//            ->first();

        if (Hash::check($inputPassword, $user->password)) {
            $data = array(
                'status' => 'Valid'
            );
            return response()->json($data, 201);
        }

//        return response()->json(array('a'=>Hash::check($inputPassword, $user->password),'b'=>($inputPassword)), 201);
    }
    public function changePassword ()
    {
        $user = JWTAuth::parseToken()->toUser();
        $inputPassword = Input::get('newPassword');

        DB::table('users')
            ->where('username',$user->username)
            ->update(array(
                'password' => bcrypt($inputPassword)
            ));

        return response()->json(array('status'=>'Success'), 201);
    }
}
