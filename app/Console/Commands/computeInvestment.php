<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class computeInvestment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'computeInvestment:computeInvestmentDaily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'compute payouts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
//        $username = Input::get('principalId');
        $startDate = date("Y-m-d");
        $endDate = date("Y-m-d");

        $getAllInvestors = DB::table('users')
            ->where('act_type', 'ACCOUNTS')
            ->where('status','1')
            ->get();

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

                    for ($x = 0; $x <= $dayDiff; $x++) {
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
                        $payoutDate = $getAddOneMonth;
//                        dd($xdate,$payoutDate);
                        if($xdate == $payoutDate) {

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
                                            'createdby' => '0',
                                        ));
                                } else {
//                                    return response()->json([ 'message'=>'Date are greater than Date Created!' ], 201);
                                }

                            }
                        }
                    }
                }
            }
        }



    }
}
