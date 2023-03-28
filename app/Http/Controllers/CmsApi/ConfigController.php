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
        if(!in_array(auth()->guard('api')->user()->type, ["ROOT", "ADMIN"]))
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
        //
    }
}
