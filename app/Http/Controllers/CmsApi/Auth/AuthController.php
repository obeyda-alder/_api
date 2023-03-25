<?php

namespace App\Http\Controllers\CmsApi\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['login', 'register']]);
    }
    public function login(Request $request)
    {
    	$validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            $resulte               = [];
            $resulte['success']    = false;
            $resulte['type']       = 'validations_error';
            $resulte['errors']     = $validator->errors();
             return response()->json($resulte, 400);
        }

        if (! $token = auth()->attempt($validator->validated())) {
            $resulte             = [];
            $resulte['success']  = false;
            $resulte['message']  = __('api.somthing_rowng');
            $resulte['data']     = '';
            return response()->json($resulte, 401);
        }
        if(auth()->guard('api')->user()->status != 'ACTIVE') {
            $resulte             = [];
            $resulte['success']  = false;
            $resulte['message']  = __('api.somthing_rowng');
            $resulte['data']     = '';
            return response()->json($resulte, 401);
        }

        return $this->createNewToken($token);
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if($validator->fails()){
            $resulte              = [];
            $resulte['success']   = false;
            $resulte['type']      = 'validations_error';
            $resulte['errors']     = $validator->errors();
             return response()->json($resulte, 400);
        }

        $user = User::create(array_merge($validator->validated(),['password' => bcrypt($request->password)]));

        $resulte              = [];
        $resulte['success']   = true;
        $resulte['message']   = __('api.User successfully registered');
        $resulte['data']      = $user;
        return response()->json($resulte, 200);
    }
    public function logout()
    {
        auth()->logout();

        $resulte                 = [];
        $resulte['success']      = true;
        $resulte['message']      = __('api.User successfully signed out');
        $resulte['data']         = '';
        return response()->json($resulte, 200);
    }
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }
    protected function createNewToken($token)
    {
        $resulte             = [];
        $resulte['success']  = true;
        $resulte['message']  = __('api.user_successfully_signed');
        $resulte['data']     = [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 60,
            'user'         => auth()->guard('api')->user()->load(['city', 'unit', 'money', 'user_units', 'user_units.unit_type_safe', 'type_unit_type', 'actions', 'actions.operations'])
        ];
        return response()->json($resulte, 200);
    }
}
