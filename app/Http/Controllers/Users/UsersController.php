<?php

namespace App\Http\Controllers\Users;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use DataTables;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helper;
use App\Models\AddressDetails\Country;

class UsersController extends Controller
{
    use Helper;

    protected $locale = 'ar';

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['']]);
    }
    public function OfType($type)
    {
        $type = strtoupper($type);
        $types = config('custom.users_type');
        if(in_array($type, $types)){
            return $type;
        }
    }
    public function UserData(Request $request)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $type = $this->OfType(auth()->user()->type);
        if($type){
            $resulte              = [];
            $resulte['success']   = true;
            $resulte['message']   = __('cms.base.users_data');
            $resulte['data']      = [
                    'user'               => auth()->user(),
                    'path_default_image' => $this->getImageDefaultByType('user')
            ];
            return response()->json($resulte, 200);

        } else {

            $resulte              = [];
            $resulte['success']   = false;
            $resulte['type']      = 'user_type_error';
            $resulte['data']      = '';
             return response()->json($resulte, 400);
        }
    }
    public function AllUsers(Request $request)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $type = $this->OfType(auth()->user()->type);

        if($type){
            $data = User::orderBy('id', 'DESC')->withTrashed();

            if(auth()->user()->type != 'ROOT')
            {
                $data->where('type', '!=','ROOT');
            }

            if($request->has('search') && !impty($request->search))
            {
                $data->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search['value']}%")
                    ->orWhere('email', 'like', "%{$request->search['value']}%")
                    ->orWhere('type', 'like', "%{$request->search['value']}%");
                });
            }

            if($request->type != 'ALL')
            {
                $data->where('type', 'LIKE', "%{$request->type}%");
            }

            $resulte              = [];
            $resulte['success']   = true;
            $resulte['message']   = __('cms.base.successfully');
            $resulte['data']      = $data->get();
            return response()->json($resulte, 200);

        } else {

            $resulte              = [];
            $resulte['success']   = false;
            $resulte['type']      = 'user_type_error';
            $resulte['data']      = '';
             return response()->json($resulte, 400);
        }
    }
    public function store(Request $request)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $type = $this->OfType(auth()->user()->type);

        if($type){
            $validator = [
                'name'               => 'required|string|max:255',
                'email'              => 'required|unique:users|email',
                'password'           => 'required|string|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
                'confirm_password'   => 'required|same:password',
                'type'               => 'required|in:ADMINS,EMPLOYEES,CUSTOMERS,AGENCIES',
                'image'              => 'sometimes|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            ];

            $validator = Validator::make($request->all(), $validator);

            if ($validator->fails()) {
                $resulte              = [];
                $resulte['success']   = false;
                $resulte['type']      = 'validations_error';
                $resulte['errors']     = $validator->errors();
                return response()->json($resulte, 400);
            }

            try{
                DB::transaction(function() use ($request) {
                    $user = User::create([
                        'name'             => $request->name,
                        'email'            => $request->email,
                        'type'             => $request->type,
                        'username'         => $request->username,
                        'phone_number'     => $request->phone_number,
                        'country_id'       => $request->country_id,
                        'city_id'          => $request->city_id,
                        'municipality_id'  => $request->municipality_id,
                        'neighborhood_id'  => $request->neighborhood_id,
                        'password'         => Hash::make($request->password),
                    ]);

                    if($request->has('status'))
                    {
                        $user->status = $request->status;
                    }

                    if($request->hasFile('image'))
                    {
                        $path =  $this->UploadWithResizeImage($request, 'users', [[0 => 100, 1 => 100],[0 => 50, 1 => 50]]);
                        $user->image = $path;
                    }

                    $user->save();
                });
            }catch (Exception $e){
                return response()->json([
                    'success'     => false,
                    'type'        => 'error',
                    'title'       => __('cms::base.msg.error_message.title'),
                    'description' => __('cms::base.msg.error_message.description'),
                    'errors'      => '['. $e->getMessage() .']'
                ], 500);
            }
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('cms::base.msg.success_message.title'),
            'description' => __('cms::base.msg.success_message.description'),
        ], 200);
    }
    public function show(Request $request, $id)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }
        $user = User::find($id);
        $defaultImage = $this->getImageDefaultByType('user');
        $countries = $this->getCountry(app()->getLocale());
        return view('cms::backend.users.update', [
            'user'           => $user,
            'countries'      => $countries,
            'defaultImage'  => $defaultImage
        ]);
    }
    public function update(Request $request)
    {
        dd($request->file('logo'));

        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }

        $UpdateUserValidator = [
            'name'               => 'required|string|max:255',
            'email'              => 'required|email|unique:users,id,'.$request->user_id,
            'password'           => 'nullable|string|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
        ];
        $validator = Validator::make($request->all(), $UpdateUserValidator);
        if(!$validator->fails())
        {
            try{
                DB::transaction(function() use ($request) {
                    $user                   = User::find($request->user_id);
                    $user->name             = $request->name;
                    $user->email            = $request->email;
                    $user->username         = $request->username;
                    $user->phone_number     = $request->phone_number;
                    $user->country_id       = $request->country_id;
                    $user->city_id          = $request->city_id;
                    $user->municipality_id  = $request->municipality_id;
                    $user->neighborhood_id  = $request->neighborhood_id;
                    if($request->has('password') && !is_null($request->password)) {
                        $user->password  = Hash::make($request->password);
                    }
                    $user->save();
                });
            }catch (Exception $e){
                return response()->json([
                    'success'     => false,
                    'type'        => 'error',
                    'title'       => __('cms::base.msg.error_message.title'),
                    'description' => __('cms::base.msg.error_message.description'),
                    'errors'      => '['. $e->getMessage() .']'
                ], 500);
            }
        }else {
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.validation_error.title'),
                'description' => __('cms::base.msg.validation_error.description'),
                'errors'      => $validator->getMessageBag()->toArray()
            ], 402);
        }
        return response()->json([
            'success'       => true,
            'type'          => 'success',
            'title'         => __('cms::base.msg.success_message.title'),
            'description'   => __('cms::base.msg.success_message.description'),
            'redirect_url'  => route('cms::users', ['type' => $request->type])
        ], 200);
    }
    public function softDelete(Request $request, $id)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }

        $user = User::withTrashed()->find($id);
        if(!is_null($user)){
            $user->delete();
        } else {
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.error_message.title'),
                'description' => __('cms::base.msg.error_message.description'),
            ], 500);
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('cms::base.msg.success_message.title'),
            'description' => __('cms::base.msg.success_message.description'),
        ], 200);
    }
    public function delete(Request $request, $id)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }

        $user = User::withTrashed()->find($id);
        if(!is_null($user)){
            $user->forceDelete();
        } else {
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.error_message.title'),
                'description' => __('cms::base.msg.error_message.description'),
            ], 500);
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('cms::base.msg.success_message.title'),
            'description' => __('cms::base.msg.success_message.description'),
        ], 200);
    }
    public function restore(Request $request, $id)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }

        $user = User::withTrashed()->find($id);
        if(!is_null($user)){
            $user->restore();
        } else {
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.error_message.title'),
                'description' => __('cms::base.msg.error_message.description'),
            ], 500);
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('cms::base.msg.success_message.title'),
            'description' => __('cms::base.msg.success_message.description'),
        ], 200);
    }
}
