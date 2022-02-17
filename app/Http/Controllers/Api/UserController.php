<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AppBaseController;
use Laravel\Passport\PersonalAccessTokenResult;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends AppBaseController
{
    public function register(Request $request)
    {
        // Validate the user request data
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'bail|required|email|unique:users',
                'name' => 'bail|required|string|max:255',
                'password' => 'bail|required|string|min:6'
            ]
        );

        if ($validator->fails()) {
            $error = $validator->errors();
            $message = "Please check the format of all fields";
            return $this->sendError($message, $error, 422);
        }

        $validated = $validator->validated();
        // dd($validated);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($request->password),
        ]);

        $ut = $user->createToken('casamania_auth')->accessToken;
        $token = [
            'token' => $ut,
            'user' => $user
        ];

        return $this->sendResponse('Successfully registered', $token);
    }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors();
            $message = "User details are not valid.";
            return $this->sendError($message, $error, 401);
        }

        $attr = ['email' => $request->email, 'password' => $request->password];

        $exists = User::where(['email' => $request->email])->exists();
        if (!$exists) {
            return $this->sendError('Email does not exists. Please register.', '', 401);
        }

        if (!Auth::attempt($attr)) {
            return $this->sendError('Credentials not match', '', 401);
        }

        $ut = auth()->user()->createToken('casamania_auth')->accessToken;
        // $ut = explode('|', $ut);

        $token = [
            'token' => $ut,
            'user' => auth()->user()
        ];

        return $this->sendResponse("Login success.", $token);
    }
}
