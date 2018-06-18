<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\User;

class ApiAdminController extends Controller
{

    public function loadPayoutsByPrincipalId ()
    {
        $principalId = Input::get('id');
        $user = JWTAuth::parseToken()->toUser();

        $data = DB::select("
            SELECT 
                principal_id,
                based_amount AS principal,
                days AS days,
                payout_date,
                CASE WHEN status = 0 THEN 'FOR PAYOUT' ELSE 'CLAIMED' END AS status,
                ROUND(SUM(daily_growth_amount),2) AS payoutAmount
            FROM 
                tbl_user_payouts
            WHERE 
                username = $principalId 
            GROUP BY 
                principal_id,
                principal,
                days,
                CASE WHEN status = 0 THEN 'FOR PAYOUT' ELSE 'CLAIMED' END,
                payout_date
            ORDER BY payout_date DESC
        ");
        return response()->json(array("payout"=>$data), 201);
    }
    public function triggerManualPayouts ()
    {
//        dd(Input::all());
        $user = JWTAuth::parseToken()->toUser();
        $username = Input::get('principalId');

        $startDate = Input::get('startDate');
        $endDate = Input::get('endDate');
        if ($startDate == '' OR $endDate == '') {
            return response()->json([ 'message' => 'Invalid Dates!' ], 201);
        }


        $getUser = DB::table('tbl_user_principal')->where('username', $username)->get();

        foreach ($getUser as $aRowPrincipal) {
            $principalId = $aRowPrincipal->id;
            $getInvestorPrincipal = DB::table('tbl_user_principal')
                ->where('id', $principalId)
                ->first();
            if ( $getInvestorPrincipal ) {
                $username = $getInvestorPrincipal->username;
                $principal = $getInvestorPrincipal->principal;
                $principalInterest1 = $getInvestorPrincipal->monthly_growth;
                $datecreated = $getInvestorPrincipal->principal_date;
                $createdby = $getInvestorPrincipal->createdby;

                $dayDiff = DB::select(" SELECT DATEDIFF('$endDate', '$startDate') AS NumberOfDays ")[0]->NumberOfDays;
                $day = 0;
                for ($x = 0; $x <= $dayDiff - 1; $x++) {
                    $principalValue = $principal;
                    $principalInterest = $principalInterest1 / 100;
                    $getMonthlyInterest = $principalValue * $principalInterest;


                    $day++;
                    if($x == 0) {
                        $xdate = DB::select(" SELECT DATE('$startDate') AS NewDate ")[0]->NewDate;
                    } else {
                        $xdate = DB::select(" SELECT DATE(DATE_ADD('$startDate', INTERVAL ".($x)." DAY)) AS NewDate")[0]->NewDate;
                    }

                    $xDay = date("d", strtotime($xdate));
                    $xMonth = date("m", strtotime($xdate));
                    $xYear = date("y", strtotime($xdate));

                    $getMonthDiff = DB::select(" SELECT TIMESTAMPDIFF(MONTH, DATE('$datecreated'), DATE('$xdate')) AS monthDiff ")[0]->monthDiff;
                    if($getMonthDiff == 0) {
                        $getMonthDiff = $getMonthDiff + 1;
                    }
                    $getAddOneMonth = DB::select(" SELECT DATE(DATE_ADD(DATE('$datecreated'), INTERVAL $getMonthDiff MONTH)) AS NewDate ")[0]->NewDate;
                    $getSubOneMonth = DB::select(" SELECT DATE(DATE_SUB(DATE('$getAddOneMonth'), INTERVAL 1 MONTH)) AS NewDate ")[0]->NewDate;

                    $checkIfgreaterThan = DB::select(" SELECT DATE('$xdate') >= DATE('$getAddOneMonth') AS checkDate ")[0]->checkDate;
                    $checkIfgreaterThanValid = DB::select(" SELECT DATE('$xdate') >= DATE('$datecreated') AS checkDate ")[0]->checkDate;

                    if($checkIfgreaterThan==0){
                        $getAddOneMonth = DB::select(" SELECT DATE(DATE_ADD(DATE('$getAddOneMonth'), INTERVAL 1 MONTH)) AS NewDate ")[0]->NewDate;
                        $getSubOneMonth = DB::select(" SELECT DATE(DATE_SUB(DATE('$getAddOneMonth'), INTERVAL 1 MONTH)) AS NewDate ")[0]->NewDate;
                    }



                    $dayDiff2 = DB::select(" SELECT DATEDIFF('$getAddOneMonth', '$getSubOneMonth') AS NumberOfDays ")[0]->NumberOfDays;
                    $intPerDay = $getMonthlyInterest / $dayDiff2;

//                    return response()->json([ 'message'=>$xdate." ".$checkIfgreaterThan ], 201);
                    $payoutDate = $getAddOneMonth;

                    //payout date
                    //day and days
                    if($xdate == $payoutDate){
                        $checkIfExist = DB::table('tbl_user_payouts')
                            ->where('principal_id', $principalId)
                            ->where('xdate', $xdate)
                            ->where('payout_date', $payoutDate)
                            ->first();
                        if (!$checkIfExist) {
                            if($checkIfgreaterThanValid) {
                                DB::table('tbl_user_payouts')
                                    ->insert(array(
                                        'principal_id' => $principalId,
                                        'username' => $username,
                                        'based_amount' => $principal,
                                        'monthly_growth' => $principalInterest1,
                                        'daily_growth_amount' => $getMonthlyInterest,
                                        'days' => $dayDiff2,
                                        'process_type' => 'MNL',
                                        'xdate' => $xdate,
                                        'payout_date' => $payoutDate,
                                        'releasedby' => '',
                                        'createdby' => $user->username,
                                    ));
                            } else {
                                return response()->json([ 'message'=>'Date are greater than Date Created!' ], 201);
                            }

                        }
                    }
                }

//            } else {
//                return response()->json([ 'message' => 'No Interest Setup!' ], 201);
//            }
            }

        }
        return response()->json([ 'message'=>'Manual Trigger Finished!' ], 201);

//        return response()->json([ 'message'=> 'No Principal Setup!' ], 201);
    }
    public function triggerALLManualPayouts ()
    {
//        dd(Input::all());
        $user = JWTAuth::parseToken()->toUser();
//        $username = Input::get('principalId');

        $startDate = Input::get('startDate');
        $endDate = Input::get('endDate');

        $getAllInvestors = DB::table('users')
            ->where('act_type', 'ACCOUNTS')
            ->where('status','1')
            ->get();

//        $ctr = 0;
        foreach ($getAllInvestors as $aRowAllInvestors) {
            $username = $aRowAllInvestors->username;
            if ($startDate == '' OR $endDate == '') {
                return response()->json([ 'message' => 'Invalid Dates!' ], 201);
            }
            $getUser = DB::table('tbl_user_principal')->where('username', $username)->get();
            foreach ($getUser as $aRowPrincipal) {
                $principalId = $aRowPrincipal->id;
                $getInvestorPrincipal = DB::table('tbl_user_principal')
                    ->where('id', $principalId)
                    ->first();
                if ( $getInvestorPrincipal ) {
                    $username = $getInvestorPrincipal->username;
                    $principal = $getInvestorPrincipal->principal;
                    $principalInterest1 = $getInvestorPrincipal->monthly_growth;
                    $datecreated = $getInvestorPrincipal->principal_date;
                    $createdby = $getInvestorPrincipal->createdby;

                    $dayDiff = DB::select(" SELECT DATEDIFF('$endDate', '$startDate') AS NumberOfDays ")[0]->NumberOfDays;
                    $day = 0;
                    for ($x = 0; $x <= $dayDiff - 1; $x++) {
                        $principalValue = $principal;
                        $principalInterest = $principalInterest1 / 100;
                        $getMonthlyInterest = $principalValue * $principalInterest;


                        $day++;
                        if($x == 0) {
                            $xdate = DB::select(" SELECT DATE('$startDate') AS NewDate ")[0]->NewDate;
                        } else {
                            $xdate = DB::select(" SELECT DATE(DATE_ADD('$startDate', INTERVAL ".($x)." DAY)) AS NewDate")[0]->NewDate;
                        }

                        $xDay = date("d", strtotime($xdate));
                        $xMonth = date("m", strtotime($xdate));
                        $xYear = date("y", strtotime($xdate));

                        $getMonthDiff = DB::select(" SELECT TIMESTAMPDIFF(MONTH, DATE('$datecreated'), DATE('$xdate')) AS monthDiff ")[0]->monthDiff;
                        if($getMonthDiff == 0) {
                            $getMonthDiff = $getMonthDiff + 1;
                        }
                        $getAddOneMonth = DB::select(" SELECT DATE(DATE_ADD(DATE('$datecreated'), INTERVAL $getMonthDiff MONTH)) AS NewDate ")[0]->NewDate;
                        $getSubOneMonth = DB::select(" SELECT DATE(DATE_SUB(DATE('$getAddOneMonth'), INTERVAL 1 MONTH)) AS NewDate ")[0]->NewDate;

                        $checkIfgreaterThan = DB::select(" SELECT DATE('$xdate') >= DATE('$getAddOneMonth') AS checkDate ")[0]->checkDate;
                        $checkIfgreaterThanValid = DB::select(" SELECT DATE('$xdate') >= DATE('$datecreated') AS checkDate ")[0]->checkDate;

                        if($checkIfgreaterThan==0){
                            $getAddOneMonth = DB::select(" SELECT DATE(DATE_ADD(DATE('$getAddOneMonth'), INTERVAL 1 MONTH)) AS NewDate ")[0]->NewDate;
                            $getSubOneMonth = DB::select(" SELECT DATE(DATE_SUB(DATE('$getAddOneMonth'), INTERVAL 1 MONTH)) AS NewDate ")[0]->NewDate;
                        }



                        $dayDiff2 = DB::select(" SELECT DATEDIFF('$getAddOneMonth', '$getSubOneMonth') AS NumberOfDays ")[0]->NumberOfDays;
                        $intPerDay = $getMonthlyInterest / $dayDiff2;

//                    return response()->json([ 'message'=>$xdate." ".$checkIfgreaterThan ], 201);
                        $payoutDate = $getAddOneMonth;

                        //payout date
                        //day and days
                        if($xdate == $payoutDate){
                            $checkIfExist = DB::table('tbl_user_payouts')
                                ->where('principal_id', $principalId)
                                ->where('xdate', $xdate)
                                ->where('payout_date', $payoutDate)
                                ->first();
                            if (!$checkIfExist) {
                                if($checkIfgreaterThanValid) {
                                    DB::table('tbl_user_payouts')
                                        ->insert(array(
                                            'principal_id' => $principalId,
                                            'username' => $username,
                                            'based_amount' => $principal,
                                            'monthly_growth' => $principalInterest1,
                                            'daily_growth_amount' => $getMonthlyInterest,
                                            'days' => $dayDiff2,
                                            'process_type' => 'MNL',
                                            'xdate' => $xdate,
                                            'payout_date' => $payoutDate,
                                            'releasedby' => '',
                                            'createdby' => $user->username,
                                        ));
                                } else {
//                                return response()->json([ 'message'=>'Date are greater than Date Created!' ], 201);
                                }

                            }
                        }
                    }

//            } else {
//                return response()->json([ 'message' => 'No Interest Setup!' ], 201);
//            }
                }

            }
        }

        return response()->json([ 'message'=>'Manual Trigger Finished!' ], 201);

//        return response()->json([ 'message'=> 'No Principal Setup!' ], 201);
    }
    public function updateInvestor (Request $request)
    {
        $user = JWTAuth::parseToken()->toUser();
        // return response()->json(['message'=>$user->username], 201);
        $file = Input::file("picture");
        $pictureDefault = "icon-user-default.png";

        $bday = $request->input('birthday');
        $username = $request->input('username');

        $time = strtotime($bday);
        $bday = date('Y-m-d',$time);

        if($file){
            $pictureDefault = $username.".png";
        }
        DB::table('users')->where('username', $username)->update(
            array(
                'fname' => strtoupper($request->input('firstName')),
                'mname' => strtoupper($request->input('middleName')),
                'lname' => strtoupper($request->input('lastName')),
                'email' => $request->input('email'),
                'gender' => strtoupper($request->input('gender')),
                'contactNo' => $request->input('contactNo'),
                'bday' => $bday,
                'createdby' => $user->username
            )
        );
        // $file_count = count($file);
        $itemNo = $username;
        if($file){
            try {
                $path =  public_path() . '/uploads/picture/';
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $dateNow = date('Ymd_His');
                $new_filename = $itemNo . '.png';
                if(file_exists($path.$new_filename)){
                    unlink($path.$new_filename);
                }
                
                $upload_success = $file->move($path, $new_filename);
            } catch (Exception $ex) {
                $path =  public_path() . '\\uploads\picture\\';
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $dateNow = date('Ymd_His');
                $new_filename = $itemNo . '.png';
                if(file_exists($path.$new_filename)){
                    unlink($path.$new_filename);
                }
                $upload_success = $file->move($path, $new_filename);
            }
            DB::table('users')->where('username',$username)->update(array(
                'picture' => $new_filename
            ));
        }
        return response()->json(['message'=>'Successfuly Updated'], 201);
    }
    public function addPrincipalByUsername ()
    {
        $user = JWTAuth::parseToken()->toUser();
        $username = Input::get('username');
        $principal = Input::get('principal');
        $principalDate = Input::get('principalDate');
        $principalDate = substr($principalDate, 0, 10);

        DB::table('tbl_user_principal')->insert(array(
            'username' => $username,
            'createdby' => $user->username,
            'principal' => $principal,
            'principal_date' => $principalDate
        ));

        return response()->json(['message'=>'Successfuly Added'], 201);
    }
    public function loadInvestorByUsername () 
    {
        $username = Input::get('username');
        $data = DB::table('users')
            ->where('username', $username)
            ->where('act_type','<>','ADMIN')
            ->orderBy('id','DESC')
            ->first();
        $principal = DB::table('tbl_user_principal')->where('username',$data->username)->get();
        return response()->json(['data'=>$data,'principalList'=>$principal], 201);
    }
    
    public function loadInvestorByPrincipalIdByPaydate () 
    {
        //javier
        $id = Input::get('id');
        $paydate = Input::get('paydate');
        $getUserPayout = DB::table('tbl_user_payouts')
            ->where('username',$id)
            ->where('payout_date',$paydate)
            ->get();
        $data = DB::select("
            SELECT 
                ROUND(SUM(based_amount),2) AS principal,
                payout_date,
                CASE WHEN status = 0 THEN 'FOR PAYOUT' ELSE 'CLAIMED' END AS status,
                ROUND(SUM(daily_growth_amount),2) AS payoutAmount
            FROM 
                tbl_user_payouts
            WHERE 
                username = $id 
                AND payout_date = '$paydate'
            GROUP BY 
                CASE WHEN status = 0 THEN 'FOR PAYOUT' ELSE 'CLAIMED' END,
                payout_date
            ORDER BY payout_date ASC
        ");
        return response()->json(['payoutHeader' => $data ,'payoutList'=>$getUserPayout], 201);
    }
    public function loadInvestorByPrincipalId () 
    {
        $username = Input::get('id');
//        $getUsername = DB::table('tbl_user_principal')->where('id',$username)->first();
//        if ($getUsername) {
            $data = DB::table('users')->where('username', $username)->where('act_type','<>','ADMIN')->orderBy('id','DESC')->first();
            $principal = DB::table('tbl_user_principal')->where('username',$username)->get();
            return response()->json(['data'=>$data,'principalList'=>$principal], 201);
//        }
        
    }
    public function getAuthDetails () 
    {
        $user = JWTAuth::parseToken()->toUser();
        return response()->json(['user'=>$user]);
    }
    public function getInvestors ()
    {
        $data = DB::table('users')
            ->where('act_type','<>','ADMIN')
            ->where('status','1')
            ->selectRaw(" id AS unique_id, CONCAT(fname, ' ', lname) AS name, bday, datecreated, createdby, username, picture, gender, contactNo, email, username AS action, status as status ")
            ->orderBy('id','DESC')
            ->get();
        return response()->json(['data'=>$data], 201);
    }
    public function addInvestor (Request $request) 
    {
        $user = JWTAuth::parseToken()->toUser();
        // return response()->json(['message'=>$user->username], 201);
        $file = Input::file("picture");
        $pictureDefault = "icon-user-default.png";
        
        $bday = $request->input('birthday');
        $time = strtotime($bday);

        $bday = date('Y-m-d',$time);


        $principalDate = Input::get('principalDate');
        $principalDate = substr($principalDate, 0, 10);
        
        $pasEx = explode("-",$bday);
        $password = $pasEx[1].$pasEx[2].substr($pasEx[0],2,2);

        $getLastUserName = DB::table('users')->orderBy("username","DESC")->first();
        $username = $getLastUserName->username + 1;
//        dd($password);
        if($file){
            $pictureDefault = $username.".png";
        }
        $user = new User([
            'act_type' => "ACCOUNTS",
            'fname' => strtoupper($request->input('firstName')),
            'mname' => strtoupper($request->input('middleName')),
            'lname' => strtoupper($request->input('lastName')),
            'email' => $request->input('email'),
            'username' => $username,
            'password' => bcrypt($password),
            'gender' => strtoupper($request->input('gender')),
            'contactNo' => $request->input('contactNo'),
            'picture' => $pictureDefault,
            'bday' => $bday,
            'createdby' => $user->username
        ]);
        $user->save();

        DB::table('tbl_user_principal')->insert(array(
            'username' => $username,
            'principal' => $request->input('principal'),
            'monthly_growth' => $request->input('principalInterest'),
            'principal_date' => $principalDate,
            'createdby' => $user->username
        ));


        // $file_count = count($file);
        $itemNo = $username;
        if($file){
            try {
                $path =  public_path() . '/uploads/picture/';
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $dateNow = date('Ymd_His');
                $new_filename = $itemNo . '.png';
                if(file_exists($path.$new_filename)){
                    unlink($path.$new_filename);
                }
                
                $upload_success = $file->move($path, $new_filename);
            } catch (Exception $ex) {
                $path =  public_path() . '\\uploads\picture\\';
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $dateNow = date('Ymd_His');
                $new_filename = $itemNo . '.png';
                if(file_exists($path.$new_filename)){
                    unlink($path.$new_filename);
                }
                $upload_success = $file->move($path, $new_filename);
            }
        }
        return response()->json(['message'=>'Successfuly Added'], 201);
    }
    public function loadCurrentPayouts()
    {
        $user = JWTAuth::parseToken()->toUser();

        $data = DB::select("
            SELECT 
                b.username,
                CONCAT(c.fname,' ',c.lname) AS name,
                a.payout_date,
                ROUND(SUM(a.daily_growth_amount),2) as amount
            FROM 
                tbl_user_payouts a
            LEFT JOIN tbl_user_principal b
            ON a.principal_id = b.id
            LEFT JOIN users c 
            ON b.username = c.username
            WHERE 
                a.payout_date <= DATE(now())
                AND a.status = '0'
            GROUP BY 
                b.username,
                CONCAT(c.fname,' ',c.lname),
                a.payout_date
            ORDER BY a.payout_date ASC
        ");
        return response()->json(array("payout"=>$data), 201);
    }
    public function loadDashboard()
    {
        $getTodayForPayoutsAmount = DB::table('tbl_user_payouts')
                ->where('payout_date','<=',DB::raw('DATE(now())'))
                ->where('status','0')
                ->selectRaw("ROUND(SUM(daily_growth_amount),2) as totalPayoutAmount,COUNT(DISTINCT payout_date) AS totalPayoutCount")
                ->first();
        $getTodayPayoutsAmount = DB::table('tbl_user_payouts')
                ->where(DB::raw('DATE(released_date)'),DB::raw('DATE(now())'))
                ->where('status','1')
                ->selectRaw("ROUND(SUM(daily_growth_amount),2) as totalPayoutAmount,COUNT(DISTINCT payout_date) AS totalPayoutCount")
                ->first();
        $getPrincipalAmount = DB::table('tbl_user_principal')
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

    public function payoutInvestorById ()
    {

        $status = Input::get('status');
        $principalId = Input::get('principalId');
        $payoutDate = Input::get('payoutDate');
        $user = JWTAuth::parseToken()->toUser();
//        if ($status!=0 OR $status!=1) {
//            $data = array(
//                'status' => 'Payout Failed'
//            );
//            return response()->json($data, 201);
//        }

        DB::table('tbl_user_payouts')
            ->where('username', $principalId)
            ->where('payout_date', $payoutDate)
            ->update(array(
                'status' => $status,
                'releasedby' => $user->username,
                'released_date' => DB::raw('now()')
            ));
        $statusMessage = "Unpayout Successful";
        if ($status=='1') {
            $statusMessage = "Payout Successful";
        }
        $data = array(
            'status' => $statusMessage
        );


        return response()->json($data, 201);
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

    public function addExpense () {
        $user = JWTAuth::parseToken()->toUser();
        $description = Input::get('expensesDescription');
        $expenseDate = Input::get('expensesDate');
        $expenseAmount = Input::get('expensesAmount');

        $time = strtotime($expenseDate);

        $expenseDate = date('Y-m-d',$time);
//        dd($description,$expenseAmount,$expenseDate);

        DB::table('tbl_expenses')
            ->insert(array(
                "expenses_desc" => $description,
                "expenses_date" => $expenseDate,
                "expenses_amount" => $expenseAmount,
                "createdby" => $user->username
            ));

        return response()->json(['message'=>'Successfuly Added'], 201);
    }
    public function addCashAdvance () {
        $user = JWTAuth::parseToken()->toUser();
        $fullname = Input::get('fullname');
        $cashAdvanceDate = Input::get('cash_advance_date');
        $cashAdvanceAmount = Input::get('cash_advance_amount');

        $time = strtotime($cashAdvanceDate);

        $cashAdvanceDate = date('Y-m-d',$time);
//        dd($description,$expenseAmount,$expenseDate);

        DB::table('tbl_cash_advance')
            ->insert(array(
                "ca_fullname" => $fullname,
                "ca_date" => $cashAdvanceDate,
                "ca_amount" => $cashAdvanceAmount,
                "createdby" => $user->username
            ));

        return response()->json(['message'=>'Successfuly Added'], 201);
    }
    public function addLoan () {
        $user = JWTAuth::parseToken()->toUser();
        $fullname = Input::get('fullname');
        $loandate = Input::get('loanDate');
        $loanAmount = Input::get('loanAmount');

        $time = strtotime($loandate);

        $loandate = date('Y-m-d',$time);
//        dd($description,$expenseAmount,$expenseDate);

        DB::table('tbl_loans')
            ->insert(array(
                "loan_fullname" => $fullname,
                "loan_date" => $loandate,
                "loan_amount" => $loanAmount,
                "createdby" => $user->username
            ));

        return response()->json(['message'=>'Successfuly Added'], 201);
    }
    public function loadExpenses ()
    {
        $expensesData = DB::table('tbl_expenses')
            ->orderBy('id','DESC')
            ->get();
        return response()->json(['data'=>$expensesData], 201);
    }
    public function loadCashAdvance ()
    {
        $expensesData = DB::table('tbl_cash_advance')
            ->orderBy('id','DESC')
            ->get();
        return response()->json(['data'=>$expensesData], 201);
    }
    public function loadLoans ()
    {
        $expensesData = DB::table('tbl_loans')
            ->orderBy('id','DESC')
            ->get();
        return response()->json(['data'=>$expensesData], 201);
    }
    public function loadPrincipal ()
    {
        $expensesData = DB::table('tbl_user_principal')
            ->leftjoin('users','tbl_user_principal.username','=','users.username')
            ->where('users.status','1')
            ->selectRaw("tbl_user_principal.id as id,tbl_user_principal.username AS username, CONCAT(users.fname,' ',users.lname) AS fullname,tbl_user_principal.principal,tbl_user_principal.monthly_growth,tbl_user_principal.principal_date,tbl_user_principal.createdby")
            ->orderBy('tbl_user_principal.username','ASC')
            ->get();
        return response()->json(['data'=>$expensesData], 201);
    }

    public function deleteExpense()
    {
        $getExpenseId = Input::get('expenseId');

        DB::table('tbl_expenses')
            ->where('id',$getExpenseId)
            ->delete();

        return response()->json(['message'=>$getExpenseId], 201);
    }
    public function deleteCashAdvance()
    {
        $getId = Input::get('id');

        DB::table('tbl_cash_advance')
            ->where('id',$getId)
            ->delete();

        return response()->json(['message'=>$getId], 201);
    }
    public function deleteLoan()
    {
        $getId = Input::get('id');

        DB::table('tbl_loans')
            ->where('id',$getId)
            ->delete();

        return response()->json(['message'=>$getId], 201);
    }
    public function paidLoan ()
    {
        $getId = Input::get('id');

        DB::table('tbl_loans')
            ->where('id',$getId)
            ->update(array(
                'status' => '1',
                'loan_paid_date' => DB::raw('now()')
            ));

        return response()->json(['message'=>$getId], 201);
    }
    public function unPaidLoan ()
    {
        $getId = Input::get('id');

        DB::table('tbl_loans')
            ->where('id',$getId)
            ->update(array(
                'status' => '0',
                'loan_paid_date' => DB::raw('null')
            ));

        return response()->json(['message'=>$getId], 201);
    }
    public function updatePrincipal ()
    {
        $getId = Input::get('id');
        $getPrincipal = Input::get('principalGrowth');

        DB::table('tbl_user_principal')
            ->where('id',$getId)
            ->update(array(
                'monthly_growth' =>$getPrincipal
            ));

        return response()->json(['message'=>$getId], 201);
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

    public function updateStatusInvestorByUsername()
    {

        $username = Input::get('username');
        $statusUser = Input::get('status');
        $status = "Successfully Updated";

        DB::table("users")
            ->where('act_type', 'ACCOUNTS')
            ->where('username', $username)
            ->update(array(
                "status" => $statusUser
            ));

        $data = array(
            "status" => $status
        );
        return response()->json($data, 201);
    }

    public function combineAllPrincalByUsername()
    {
        $username = Input::get('username');
        $newPrincipalDate = Input::get('newPrincipalDate');

        DB::table('tbl_user_principal')
            ->where('username', $username)
            ->update(array(
               "principal_date" => $newPrincipalDate
            ));


        $data = array(
            "status" => "Success"
        );
        return response()->json($data, 201);
    }
    public function deletePrincipal()
    {
        $principalId = Input::get('principalId');

        DB::table('tbl_user_principal')
            ->where('id', $principalId)
            ->delete();


        $data = array(
            "status" => "Success"
        );
        return response()->json($data, 201);
    }
    public function pulloutPrincipal()
    {
        $principalId = Input::get('principalId');
        $principalAmount = Input::get('principalAmount');

        DB::table('tbl_user_principal')
            ->where('id', $principalId)
            ->update(array(
                "principal" => $principalAmount
            ));


        $data = array(
            "status" => "Success"
        );
        return response()->json($data, 201);
    }
    public function investorPrincipalList()
    {
        $startDate = Input::get('startDate');
        $endDate = Input::get('endDate');
        $getInvestors = DB::select(DB::raw("
            SELECT 
               a.username AS username,
               CONCAT(b.fname, ' ', b.lname) as fullname,
               a.principal AS principal,
               a.principal_date AS principal_date,
               a.created_at AS datecreated,
               a.createdby AS createdby
            FROM tbl_user_principal a 
            LEFT JOIN users b 
            ON a.username = b.username
            WHERE 
              b.act_type = 'ACCOUNTS'
              AND b.status = '1'
        "));
        $data = array(
            "data" => $getInvestors
        );
        return response()->json($data, 201);
    }
    public function investorPayoutList()
    {
        $startDate = Input::get('startDate');
        $endDate = Input::get('endDate');
        $getInvestors = DB::select(DB::raw("
            SELECT 
               a.username AS username,
               CONCAT(b.fname, ' ', b.lname) as fullname,
               a.based_amount AS principal,
               a.payout_date AS payout_date,
               a.daily_growth_amount AS payout_amount,
               a.released_date AS released_date,
               a.releasedby AS released_by
            FROM tbl_user_payouts a 
            LEFT JOIN users b 
            ON a.username = b.username
            WHERE 
              b.act_type = 'ACCOUNTS'
              AND b.status = '1'
              AND DATE(a.released_date) BETWEEN DATE('$startDate') AND DATE('$endDate')
        "));
        $data = array(
            "data" => $getInvestors
        );
        return response()->json($data, 201);
    }

}
