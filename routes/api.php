<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/admin/signin', [
    'uses' => 'UserController@signin'
]);

Route::get('/admin/investor-list', [
    'uses' => 'ApiAdminController@getInvestors',
    'middleware' => 'auth.jwt'
]);

Route::get('/admin/loadInvestorByUsername', [
    'uses' => 'ApiAdminController@loadInvestorByUsername',
    'middleware' => 'auth.jwt'
]);

Route::get('/admin/loadInvestorByPrincipalId', [
    'uses' => 'ApiAdminController@loadInvestorByPrincipalId',
    'middleware' => 'auth.jwt'
]);


Route::get('/admin/loadPayoutsByPrincipalId', [
    'uses' => 'ApiAdminController@loadPayoutsByPrincipalId',
    'middleware' => 'auth.jwt'
]);


Route::get('/admin/loadInvestorByPrincipalIdByPaydate', [
    'uses' => 'ApiAdminController@loadInvestorByPrincipalIdByPaydate',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/investorPrincipalList', [
    'uses' => 'ApiAdminController@investorPrincipalList',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/investorPayoutList', [
    'uses' => 'ApiAdminController@investorPayoutList',
    'middleware' => 'auth.jwt'
]);

Route::post('/admin/add-investor', [
    'uses' => 'ApiAdminController@addInvestor',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/combineAllPrincalByUsername', [
    'uses' => 'ApiAdminController@combineAllPrincalByUsername',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/deletePrincipal', [
    'uses' => 'ApiAdminController@deletePrincipal',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/pulloutPrincipal', [
    'uses' => 'ApiAdminController@pulloutPrincipal',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/changePassword', [
    'uses' => 'ApiAdminController@changePassword',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/triggerManualPayouts', [
    'uses' => 'ApiAdminController@triggerManualPayouts',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/triggerALLManualPayouts', [
    'uses' => 'ApiAdminController@triggerALLManualPayouts',
    'middleware' => 'auth.jwt'
]);

Route::post('/admin/update-investor', [
    'uses' => 'ApiAdminController@updateInvestor',
    'middleware' => 'auth.jwt'
]);

Route::post('/admin/addPrincipalByUsername', [
    'uses' => 'ApiAdminController@addPrincipalByUsername',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/updateStatusInvestorByUsername', [
    'uses' => 'ApiAdminController@updateStatusInvestorByUsername',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/payoutInvestorById', [
    'uses' => 'ApiAdminController@payoutInvestorById',
    'middleware' => 'auth.jwt'
]);


Route::get('/admin/getauthdetail', [
    'uses' => 'ApiAdminController@getAuthDetails',
    'middleware' => 'auth.jwt'
]);

Route::get('/admin/loadCurrentPayouts', [
    'uses' => 'ApiAdminController@loadCurrentPayouts',
    'middleware' => 'auth.jwt'
]);
Route::get('/admin/loadDashboard', [
    'uses' => 'ApiAdminController@loadDashboard',
    'middleware' => 'auth.jwt'
]);

Route::post('/admin/confirmUserPassword', [
    'uses' => 'ApiAdminController@confirmUserPassword',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/addExpense', [
    'uses' => 'ApiAdminController@addExpense',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/addCashAdvance', [
    'uses' => 'ApiAdminController@addCashAdvance',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/addLoan', [
    'uses' => 'ApiAdminController@addLoan',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/deleteExpense', [
    'uses' => 'ApiAdminController@deleteExpense',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/deleteCashAdvance', [
    'uses' => 'ApiAdminController@deleteCashAdvance',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/deleteLoan', [
    'uses' => 'ApiAdminController@deleteLoan',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/paidLoan', [
    'uses' => 'ApiAdminController@paidLoan',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/unPaidLoan', [
    'uses' => 'ApiAdminController@unPaidLoan',
    'middleware' => 'auth.jwt'
]);
Route::post('/admin/updatePrincipal', [
    'uses' => 'ApiAdminController@updatePrincipal',
    'middleware' => 'auth.jwt'
]);
Route::get('/admin/loadExpenses', [
    'uses' => 'ApiAdminController@loadExpenses',
    'middleware' => 'auth.jwt'
]);
Route::get('/admin/loadCashAdvance', [
    'uses' => 'ApiAdminController@loadCashAdvance',
    'middleware' => 'auth.jwt'
]);
Route::get('/admin/loadLoans', [
    'uses' => 'ApiAdminController@loadLoans',
    'middleware' => 'auth.jwt'
]);
Route::get('/admin/loadPrincipal', [
    'uses' => 'ApiAdminController@loadPrincipal',
    'middleware' => 'auth.jwt'
]);

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


//User routes

Route::post('/user/signinUser', [
    'uses' => 'UserController@signinUser'
]);

Route::get('/user/getauthdetail', [
    'uses' => 'ApiUserController@getAuthDetails',
    'middleware' => 'auth.jwt.user'
]);
Route::get('/user/loadDashboard', [
    'uses' => 'ApiUserController@loadDashboard',
    'middleware' => 'auth.jwt.user'
]);
Route::get('/user/loadCurrentPayouts', [
    'uses' => 'ApiUserController@loadCurrentPayouts',
    'middleware' => 'auth.jwt.user'
]);

Route::get('/user/loadInvestorByPrincipalId', [
    'uses' => 'ApiUserController@loadInvestorByPrincipalId',
    'middleware' => 'auth.jwt.user'
]);

Route::get('/user/loadInvestorByPrincipalIdByPaydate', [
    'uses' => 'ApiUserController@loadInvestorByPrincipalIdByPaydate',
    'middleware' => 'auth.jwt.user'
]);


Route::post('/user/confirmUserPassword', [
    'uses' => 'ApiUserController@confirmUserPassword',
    'middleware' => 'auth.jwt.user'
]);
Route::post('/user/changePassword', [
    'uses' => 'ApiUserController@changePassword',
    'middleware' => 'auth.jwt.user'
]);