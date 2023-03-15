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
use App\Helpers\DataLists;
use App\Models\AddressDetails\Country;

class UsersController extends Controller
{
    use Helper, DataLists;

    protected $locale = 'ar';

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }
    public function OfType($type)
    {
        $type = strtoupper($type);
        $types = config('custom.users_type');
        if(in_array($type, $types)){
            return $type;
        }
    }
    public function UserData(Request $request, $id)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->user()->type, ["ROOT", "ADMINS"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $data = User::findOrFail($id);

        $type = $this->OfType(auth()->user()->type);
        if($type){
            $resulte              = [];
            $resulte['success']   = true;
            $resulte['message']   = __('cms.base.users_data');
            $resulte['data']      = $this->getUserData($data, false);
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

        if(!in_array(auth()->user()->type, ["ROOT", "ADMINS"]))
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

            if($request->has('search') && !empty($request->search))
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
            $resulte['count']     = $data->count();
            $resulte['data']      = $this->getUserData($data->get(), true);
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

        if(!in_array(auth()->user()->type, ["ROOT", "ADMINS"]))
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
    public function update(Request $request, $id)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->user()->type, ["ROOT", "ADMINS"]))
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
                'email'              => 'required|email|unique:users,id,'.$request->user_id,
                'password'           => 'nullable|string|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
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
                DB::transaction(function() use ($request, $id) {
                    $user                   = User::find($id);
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

                    if($request->has('status'))
                    {
                        $user->status = $request->status;
                    }

                    if(!is_null($user->image))
                    {
                        $this->deleteImgByFileName('users', $user->image); //[[0 => 100, 1 => 100],[0 => 50, 1 => 50]]
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
    public function softDelete(Request $request, $id)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMINS"]))
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
        if(!in_array(auth()->user()->type, ["ROOT", "ADMINS"]))
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
        if(!in_array(auth()->user()->type, ["ROOT", "ADMINS"]))
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
