<?php

namespace App\Http\Controllers\CmsApi\Categories;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Entities\Categories;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Helpers\Helper;
use App\Helpers\DataLists;
use App\Models\User;
use Keygen\Keygen;

class CategoriesController extends Controller
{
    use Helper, DataLists;

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }
    public function generateCode($length = 14)
    {
        $code = Keygen::numeric($length)->prefix('CA-')->generate();

        if(Categories::where('code', $code)->exists())
        {
            return $this->generateCode($length);
        }

        return $code;
    }
    public function index(Request $request)
    {
        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $data = Categories::with(['user'])->orderBy('id', 'DESC')->withTrashed();

        if($request->has('search') && !empty($request->search))
        {
            $data->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%")
                ->orWhere('status', 'like', "%{$request->search}%");
            });
        }

        $resulte              = [];
        $resulte['success']   = true;
        $resulte['message']   = __('api.category_data');
        $resulte['count']     = $data->count();
        $resulte['data']      = $data->get();
        return response()->json($resulte, 200);
    }
    public function create(Request $request)
    {
        $user = auth()->guard('api')->user();
        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
            return response()->json($resulte, 400);
        }

        $validator = [
            'name'                => 'required|string|max:255',
            'unit_min_limit'      => 'required|integer',
            'unit_max_limit'      => 'required|integer',
            'value_in_price'      => 'required|integer',
            'status'              => 'required|in:ACTIVE,NOT_ACTIVE',
            'percentage'          => 'required',
        ];

        $validator = Validator::make($request->all(), $validator);

        if ($validator->fails()) {
            $resulte                  = [];
            $resulte['success']       = false;
            $resulte['type']          = 'validations_error';
            $resulte['errors']        = $validator->errors();
            return response()->json($resulte, 400);
        }

        try{
            DB::transaction(function() use ($request, $user) {

                $category = Categories::create([
                    'name'               => $request->name,
                    'code'               => $this->generateCode(),
                    'unit_min_limit'     => $request->unit_min_limit,
                    'unit_max_limit'     => $request->unit_max_limit,
                    'value_in_price'     => $request->value_in_price,
                    'status'             => $request->status,
                    'add_by_user_id'     => $user->id,
                    'percentage'         => $request->percentage
                ]);
                $category->save();
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

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('api.success_message.title'),
            'description' => __('api.success_message.description'),
        ], 200);
    }
    public function edit(Request $request, $id)
    {
        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
             return response()->json($resulte, 400);
        }

        try{
            $data = Categories::with(['user'])->whereNull('deleted_at')->findOrFail($id);
            $resulte              = [];
            $resulte['success']   = true;
            $resulte['message']   = __('api.category_data');
            $resulte['data']      = $data;
            return response()->json($resulte, 200);

        }catch(Exception $e){
            $resulte              = [];
            $resulte['success']   = false;
            $resulte['type']      = 'category_data_not_found';
            $resulte['data']      = $e->getMessage();
            return response()->json($resulte, 400);
        }
    }
    public function update(Request $request, $id)
    {
        $user = auth()->guard('api')->user();
        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
            return response()->json($resulte, 400);
        }

        $validator = [
            'name'                => 'required|string|max:255',
            'unit_min_limit'      => 'required|integer',
            'unit_max_limit'      => 'required|integer',
            'value_in_price'      => 'required|integer',
            'status'              => 'required|in:ACTIVE,NOT_ACTIVE',
            'percentage'          => 'required',
        ];

        $validator = Validator::make($request->all(), $validator);

        if ($validator->fails()) {
            $resulte                  = [];
            $resulte['success']       = false;
            $resulte['type']          = 'validations_error';
            $resulte['errors']        = $validator->errors();
            return response()->json($resulte, 400);
        }

            try{
                DB::transaction(function() use ($request, $id, $user) {
                    $category                     = Categories::findOrFail($id);
                    $category->name               = $request->name;
                    $category->unit_min_limit     = $request->unit_min_limit;
                    $category->unit_max_limit     = $request->unit_max_limit;
                    $category->value_in_price     = $request->value_in_price;
                    $category->status             = $request->status;
                    $category->add_by_user_id     = $user->id;
                    $category->percentage         = $request->percentage;
                    $category->save();
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

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('api.success_message.title'),
            'description' => __('api.success_message.description'),
        ], 200);
    }
    public function softDelete(Request $request, $id)
    {
        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('api.permission_denied.title'),
                'description' => __('api.permission_denied.description'),
            ], 402);
        }

        try {
            $categories = Categories::findOrFail($id);
            $categories->delete();
        }catch (Exception $e){
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('api.error_message.title'),
                'description' => __('api.error_message.description'),
                'errors'      => '['. $e->getMessage() .']'
            ], 500);
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('api.success_message.title'),
            'description' => __('api.success_message.description'),
        ], 200);
    }
    public function delete(Request $request, $id)
    {
        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('api.permission_denied.title'),
                'description' => __('api.permission_denied.description'),
            ], 402);
        }

        try {
            $categories = Categories::withTrashed()->findOrFail($id);
            $categories->forceDelete();
        }catch (Exception $e){
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('api.error_message.title'),
                'description' => __('api.error_message.description'),
                'errors'      => '['. $e->getMessage() .']'
            ], 500);
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('api.success_message.title'),
            'description' => __('api.success_message.description'),
        ], 200);
    }
    public function restore(Request $request, $id)
    {
        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('api.permission_denied.title'),
                'description' => __('api.permission_denied.description'),
            ], 402);
        }

        try {
            $categories = Categories::withTrashed()->findOrFail($id);
            $categories->restore();
        }catch (Exception $e){
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('api.error_message.title'),
                'description' => __('api.error_message.description'),
                'errors'      => '['. $e->getMessage() .']'
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
