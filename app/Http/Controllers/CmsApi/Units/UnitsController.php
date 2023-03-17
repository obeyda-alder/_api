<?php

namespace App\Http\Controllers\CmsApi\Units;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Entities\Units;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Helpers\Helper;
use App\Models\User;
use Keygen\Keygen;

class UnitsController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }
    public function generateCode($length = 14)
    {
        $code = Keygen::alphanum($length)->prefix('UN-')->generate(); //->prefix('U')->suffix('G')->generate();

        if(units::where('unit_code', $code)->exists())
        {
            return $this->generateCode($length);
        }

        return strtoupper($code);
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

        $data = units::with(['unit_type', 'unit_type.relation', 'user'])->orderBy('id', 'DESC')->whereNull('deleted_at');

        if($request->has('search') && !empty($request->search))
        {
            $data->where(function($q) use ($request) {
                $q->where('unit_code', 'like', "%{$request->search['value']}%")
                ->orWhere('unit_value', 'like', "%{$request->search['value']}%")
                ->orWhere('status', 'like', "%{$request->search['value']}%");
            });
        }

        $resulte              = [];
        $resulte['success']   = true;
        $resulte['message']   = __('cms.base.units_data');
        $resulte['count']     = $data->count();
        $resulte['data']      = $data->get();
        return response()->json($resulte, 200);
    }
    public function generate(Request $request)
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
            'unit_type_id'    => 'required|exists:unit_type,id',
            'price'           => 'required|min:0',
            'unit_value'      => 'required|min:0',
            'status'          => 'required|in:ACTIVE,NOT_ACTIVE',
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
                $data = new units;
                $data->unit_code        = $this->generateCode();
                $data->unit_type_id     = $request->unit_type_id;
                $data->price            = $request->price;
                $data->unit_value       = $request->unit_value;
                $data->add_by           = $user->id;
                $data->status           = $request->status;
                $data->save();
            });
        }catch (Exception $e){
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.error_message.title'),
                'description' => __('cms::base.msg.error_message.description'),
                'errors'      => '['. $e->getLine() .']'
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
            $units = units::findOrFail($id);
            $units->delete();
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
            $units = units::withTrashed()->findOrFail($id);
            $units->forceDelete();
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
            $units = units::withTrashed()->findOrFail($id);
            $units->restore();
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
