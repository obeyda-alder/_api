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
    public function generateCode($length = 10)
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
        if(!in_array(auth()->user()->type, ["ROOT", "ADMINS"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $data = Categories::with(['operationType', 'operationType.operation', 'operationType.operation.relation'])->orderBy('id', 'DESC')->whereNull('deleted_at');

        if($request->has('search') && !empty($request->search))
        {
            $data->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search['value']}%")
                ->orWhere('code', 'like', "%{$request->search['value']}%")
                ->orWhere('status', 'like', "%{$request->search['value']}%");
            });
        }

        $resulte              = [];
        $resulte['success']   = true;
        $resulte['message']   = __('cms.base.category_data');
        $resulte['count']     = $data->count();
        $resulte['data']      = $data->get();
        return response()->json($resulte, 200);
    }
    public function create(Request $request)
    {
        $user = auth()->user();
        if(!in_array(auth()->user()->type, ["ROOT", "ADMINS"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
            return response()->json($resulte, 400);
        }

        $validator = [
            'name'                => 'required|string|max:255',
            'unit_min_limit'      => 'required|integer',
            'unit_max_limit'      => 'required|integer',
            'value_in_price'      => 'required|integer',
            'status'              => 'required|in:ACTIVE,NOT_ACTIVE',
            'percentage'          => 'required',
            'operation_type_id'   => 'required|unique:categories,operation_type_id|exists:operation_type,id',
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
                        'operation_type_id'  => $request->operation_type_id,
                        'add_by_user_id'     => $user->id,
                        'percentage'         => $request->percentage
                    ]);
                    $category->save();
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

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('cms::base.msg.success_message.title'),
            'description' => __('cms::base.msg.success_message.description'),
        ], 200);
    }
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if(!in_array(auth()->user()->type, ["ROOT", "ADMINS"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
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
                    'title'       => __('cms::base.msg.error_message.title'),
                    'description' => __('cms::base.msg.error_message.description'),
                    'errors'      => '['. $e->getMessage() .']'
                ], 500);
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
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }

        try {
            $categories = Categories::findOrFail($id);
            $categories->delete();
        }catch (Exception $e){
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.error_message.title'),
                'description' => __('cms::base.msg.error_message.description'),
                'errors'      => '['. $e->getMessage() .']'
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

        try {
            $categories = Categories::withTrashed()->findOrFail($id);
            $categories->forceDelete();
        }catch (Exception $e){
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.error_message.title'),
                'description' => __('cms::base.msg.error_message.description'),
                'errors'      => '['. $e->getMessage() .']'
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

        try {
            $categories = Categories::withTrashed()->findOrFail($id);
            $categories->restore();
        }catch (Exception $e){
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.error_message.title'),
                'description' => __('cms::base.msg.error_message.description'),
                'errors'      => '['. $e->getMessage() .']'
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
