<?php

namespace App\Http\Controllers\CmsApi\Categories;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Entities\RelationsType;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Helpers\Helper;
use App\Helpers\DataLists;
use App\Models\User;
use Keygen\Keygen;

class RelationsTypeController extends Controller
{
    use Helper, DataLists;

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }
    public function index(Request $request)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $data = RelationsType::with(['user'])->orderBy('id', 'DESC');

        if($request->has('search') && !empty($request->search))
        {
            $data->where(function($q) use ($request) {
                $q->where('relation_type', 'like', "%{$request->search['value']}%")
                ->orWhere('add_by_user_id', 'like', "%{$request->search['value']}%");
            });
        }

        $resulte              = [];
        $resulte['success']   = true;
        $resulte['message']   = __('cms.base.relations_type_data');
        $resulte['count']     = $data->count();
        $resulte['data']      = $data->get();
        return response()->json($resulte, 200);
    }
    public function create(Request $request)
    {
        $user = auth()->user();
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
            return response()->json($resulte, 400);
        }

        $validator = [
            'relation_type'     => 'required|string|max:255', //|in:'. implode(',', config('custom.relation_type')),
            'user_type'         => 'required|string|max:255'
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
                    $operation = RelationsType::create([
                        'relation_type'    => str_replace(' ', '_', strtoupper($request->relation_type)),
                        'user_type'        => str_replace(' ', '_', strtoupper($request->user_type)),
                        'add_by_user_id'  => $user->id
                    ]);
                    $operation->save();
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
    public function delete(Request $request, $id)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $operation = RelationsType::find($id);

        if(!is_null($operation)){
            $operation->delete();
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
