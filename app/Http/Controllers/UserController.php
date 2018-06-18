<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;
use Illuminate\Support\Facades\Input;

class UserController extends Controller
{
    //

    public function signin(Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
            'password' => 'required'
        ]);
        $credentials = [
            'username' => Input::get('username'),
            'password' => Input::get('password'),
            'act_type' => 'ADMIN',
            'status' => '1'
        ];
        // $customClaims = ['act_type' => 'ADMIN'];
        try {
            if(!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'error' => 'Invalid Credentials!'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Could not create token!'
            ], 500);
        }
        // dd($request);
        // $user = JWTAuth::parseToken()->toUser();
        return response()->json([
            'token' => $token
        ]);
        // return response()->json([
        //     'token' => $request
        // ]);
    }


    public function signinUser(Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
            'password' => 'required'
        ]);
        $credentials = [
            'username' => Input::get('username'),
            'password' => Input::get('password'),
            'act_type' => 'ACCOUNTS',
            'status' => '1'
        ];
        // $customClaims = ['act_type' => 'ADMIN'];
        try {
            if(!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'error' => 'Invalid Credentials!'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Could not create token!'
            ], 500);
        }
        // dd($request);
        // $user = JWTAuth::parseToken()->toUser();
        return response()->json([
            'token' => $token
        ]);
        // return response()->json([
        //     'token' => $request
        // ]);
    }
}
