<?php

namespace App\Http\Controllers\CmsApi\Categories;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Entities\Operations;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Helpers\Helper;
use App\Helpers\DataLists;
use App\Models\User;
use Keygen\Keygen;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    use Helper, DataLists;

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
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

        $data = Operations::with(['relation'])->orderBy('id', 'DESC');

        if($request->has('search') && !empty($request->search))
        {
            $data->where(function($q) use ($request) {
                $q->where('type_en', 'like', "%{$request->search}%")
                ->orWhere('type_ar', 'like', "%{$request->search}%")
                ->orWhere('add_by_user_id', 'like', "%{$request->search}%");
            });
        }

        $resulte              = [];
        $resulte['success']   = true;
        $resulte['message']   = __('api.operations_data');
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
            'type_en'     => Rule::unique('operations')->where(function ($qu) use($request) {
                $qu->where('relation_id', $request->relation_id);
            }),
            'type_ar'     => 'required|string|max:255',
            'relation_id' => 'required|exists:relations_type,id',
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
                    $operation = Operations::create([
                        'type_en'         => str_replace(' ', '_', strtoupper($request->type_en)),
                        'type_ar'         => strtoupper($request->type_ar),
                        'relation_id'     => $request->relation_id,
                        'add_by_user_id'  => $user->id
                    ]);
                    $operation->save();
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
    public function delete(Request $request, $id)
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

        $operation = Operations::find($id);

        if(!is_null($operation)){
            $operation->delete();
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
