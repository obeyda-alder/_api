<?php

namespace App\Http\Controllers\CmsApi;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Helpers\Helper;
use App\Helpers\DataLists;
use App\Models\Entities\Config;
use App\Models\AddressDetails\Country;

class ConfigController extends Controller
{
    use Helper, DataLists;

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }
    public function index(Request $request)
    {
        if(!in_array(auth()->guard('api')->user()->type, config('custom.users_type')))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $config = Config::where('type', $request->type)->orderBy('id', 'DESC');

        if($request->has('search') && !empty($request->search))
        {
            $config->where(function($q) use ($request) {
                $q->where('type', 'like', "%{$request->search}%")
                ->orWhere('name', 'like', "%{$request->search}%")
                ->orWhere('currency', 'like', "%{$request->search}%");
            });
        }

        $resulte              = [];
        $resulte['success']   = true;
        $resulte['message']   = __('api.config_data');
        $resulte['count']     = $config->count();
        $resulte['data']      = $config->get();
        return response()->json($resulte, 200);
    }
    public function create(Request $request)
    {
        $user = auth()->guard('api')->user();
        if(!in_array($user->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
            return response()->json($resulte, 400);
        }

        $validator = [
            'currency_id'    => 'required|exists:countries,id',
            'price'          => 'required',
            'type'           => 'required',
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
                $Country = Country::find($request->currency_id);
                $config = Config::create([
                    'type'       => $request->type,
                    'name'       => $Country->name_en,
                    'flag'       => $Country->currency_icon,
                    'currency'   => $Country->currency,
                    'price'      => $request->price,
                ]);

                $config->save();
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
        $user = auth()->guard('api')->user();
        if(!in_array($user->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
            return response()->json($resulte, 400);
        }

        try {
            $config = Config::findOrFail($id);
            $config->delete();
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
