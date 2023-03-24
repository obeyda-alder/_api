<?php

namespace App\Http\Controllers\CmsApi\Users;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helper;
use App\Helpers\DataLists;
use App\Models\Entities\MoneySafe;
use App\Models\Entities\UnitsSafe;
use App\Models\Entities\UnitType;
use App\Models\Entities\UnitTypesSafe;
use App\Models\Entities\UserUnits;
use Illuminate\Validation\Rule;


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

        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $type = $this->OfType(auth()->guard('api')->user()->type);

        if($type){
            try{
                $data = User::with(['city', 'unit', 'money', 'user_units', 'user_units.unit_type_safe', 'type_unit_type', 'actions', 'actions.operations'])->whereNull('deleted_at')->findOrFail($id);
                $resulte              = [];
                $resulte['success']   = true;
                $resulte['message']   = __('api.users_data');
                $resulte['data']      = $this->getUserData($data, false);
                return response()->json($resulte, 200);

            }catch(Exception $e){
                $resulte              = [];
                $resulte['success']   = false;
                $resulte['type']      = 'user_not_found';
                $resulte['data']      = $e->getMessage();
                 return response()->json($resulte, 400);
            }
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

        // if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        // {
        //     $resulte                 = [];
        //     $resulte['success']      = false;
        //     $resulte['type']         = 'permission_denied';
        //     $resulte['title']        = __('api.permission_denied.title');
        //     $resulte['description']  = __('api.permission_denied.description');
        //      return response()->json($resulte, 400);
        // }

        $type = $this->OfType(auth()->guard('api')->user()->type);

        if($type){
            $data = User::with(['city', 'unit', 'money', 'user_units', 'user_units.unit_type_safe', 'type_unit_type', 'actions', 'actions.operations'])->where('type', '!=','ROOT')->orderBy('id', 'DESC');

            if(in_array($type, ["ROOT", "ADMIN"]))
            {
                $data->withTrashed();
            }

            if($request->has('search') && !is_null($request->search))
            {
                $data->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('type', 'like', "%{$request->search}%");
                });
            }

            if($request->type != 'ALL')
            {
                $data->where('type', 'LIKE', "%{$request->type}%");
            }

            $resulte              = [];
            $resulte['success']   = true;
            $resulte['message']   = __('api.successfully');
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

        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $type = $this->OfType(auth()->guard('api')->user()->type);

        if($type){
            $validator = [
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|unique:users|email',
                'password'              => 'required|string|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
                'confirm_password'      => 'required|same:password',
                'type'                  => 'required|in:'.implode(',', config('custom.users_type')),
                // 'image'                 => 'sometimes|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
                "master_agent_user_id"  => ["required_if:type,==,SUB_AGENT",
                    Rule::exists('users', 'id')->where(function ($query) use($request) {
                        $query->where('type', 'MASTER_AGENT');
                    })
                ]
            ];

            $validator = Validator::make($request->all(), $validator);

            if ($validator->fails()) {
                $resulte                 = [];
                $resulte['success']      = false;
                $resulte['type']         = 'error';
                $resulte['title']        = __('api.error_message.title');
                $resulte['description']  = __('api.error_message.description');
                $resulte['errors']       = $validator->errors();
                return response()->json($resulte, 201);
            }

            try{
                DB::transaction(function() use ($request) {
                    $user = User::create([
                        'name'                  => $request->name,
                        'email'                 => $request->email,
                        'master_agent_user_id'  => $request->master_agent_user_id,
                        'type'                  => $request->type,
                        'username'              => $request->username,
                        'phone_number'          => $request->phone_number,
                        'country_id'            => $request->country_id,
                        'city_id'               => $request->city_id,
                        'municipality_id'       => $request->municipality_id,
                        'neighborhood_id'       => $request->neighborhood_id,
                        'password'              => Hash::make($request->password),
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

                    $money_safe          = new MoneySafe;
                    $money_safe->user_id = $user->id;
                    $money_safe->save();

                    $unit_safe          = new UnitsSafe;
                    $unit_safe->user_id = $user->id;
                    $unit_safe->save();

                    $unit_type = UnitType::where('continued', $request->type)->get();
                    foreach($unit_type as $unit){
                        $user_unit               = new UserUnits();
                        $user_unit->unit_type_id = $unit->id;
                        $user_unit->user_id      = $user->id;
                        $user_unit->save();

                        $unit_type_safe                = new UnitTypesSafe;
                        $unit_type_safe->user_units_id = $user_unit->id;
                        $unit_type_safe->user_id       = $user->id;
                        $unit_type_safe->save();
                    }
                });
            }catch (Exception $e){
                return response()->json([
                    'success'     => false,
                    'type'        => 'error',
                    'title'       => __('api.error_message.title'),
                    'description' => __('api.error_message.description'),
                    'errors'      => '['. $e->getMessage() .']'
                ], 500);
            }
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('api.success_message.title'),
            'description' => __('api.success_message.description'),
        ], 200);
    }
    public function update(Request $request, $id)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $type = $this->OfType(auth()->guard('api')->user()->type);

        if($type){
            $validator = [
                'name'               => 'required|string|max:255',
                'email'              => 'required|email|unique:users,id,'.$request->user_id,
                'password'           => 'nullable|string|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
                'type'               => 'required|in:'.implode(',', config('custom.users_type')),
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
                    $user                   = User::findOrFail($id);
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
                    'title'       => __('api.error_message.title'),
                    'description' => __('api.error_message.description'),
                    'errors'      => '['. $e->getMessage() .']'
                ], 500);
            }
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('api.success_message.title'),
            'description' => __('api.success_message.description'),
        ], 200);
    }
    public function softDelete(Request $request, $id)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
            return response()->json($resulte, 400);
        }

        $type = $this->OfType(auth()->guard('api')->user()->type);

        if($type){
            $user = User::withTrashed()->find($id);
            if(!is_null($user)){
                $user->delete();
            } else {
                return response()->json([
                    'success'     => false,
                    'type'        => 'error',
                    'title'       => __('api.error_message.title'),
                    'description' => __('api.error_message.description'),
                ], 500);
            }

            return response()->json([
                'success'     => true,
                'type'        => 'success',
                'title'       => __('api.success_message.title'),
                'description' => __('api.success_message.description'),
            ], 200);
        }
    }
    public function delete(Request $request, $id)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
            return response()->json($resulte, 400);
        }

        $type = $this->OfType(auth()->guard('api')->user()->type);

        if($type){
            $user = User::withTrashed()->find($id);
            if(!is_null($user)){
                $user->forceDelete();
            } else {
                return response()->json([
                    'success'     => false,
                    'type'        => 'error',
                    'title'       => __('api.error_message.title'),
                    'description' => __('api.error_message.description'),
                ], 500);
            }

            return response()->json([
                'success'     => true,
                'type'        => 'success',
                'title'       => __('api.success_message.title'),
                'description' => __('api.success_message.description'),
            ], 200);
        }
    }
    public function restore(Request $request, $id)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
            return response()->json($resulte, 400);
        }

        $type = $this->OfType(auth()->guard('api')->user()->type);

        if($type){
            $user = User::withTrashed()->find($id);
            if(!is_null($user)){
                $user->restore();
            } else {
                return response()->json([
                    'success'     => false,
                    'type'        => 'error',
                    'title'       => __('api.error_message.title'),
                    'description' => __('api.error_message.description'),
                ], 500);
            }

            return response()->json([
                'success'     => true,
                'type'        => 'success',
                'title'       => __('api.success_message.title'),
                'description' => __('api.success_message.description'),
            ], 200);
        }
    }
    public function default($file)
    {
        return $this->getImageDefaultByType($file);
    }
}
