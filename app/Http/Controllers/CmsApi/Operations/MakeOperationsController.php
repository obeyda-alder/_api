<?php

namespace App\Http\Controllers\CmsApi\Operations;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Entities\MasterAgencies;
use App\Models\Entities\SubAgencies;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Helpers\Helper;
use App\Helpers\DataLists;
use App\Helpers\Transactions;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Models\Entities\Operations;
use App\Models\Entities\RelationsType;
use App\Models\Entities\OperationType;
use App\Models\Entities\Categories;

class MakeOperationsController extends Controller
{
    use Helper, DataLists, Transactions;

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }
    public function operation(Request $request, $operation_type)
    {
        $user = auth()->guard('api')->user();

        if(in_array($user->type, ['ADMIN','EMPLOYEES','AGENCIES']))
        {
            try{
                $check =  Operations::with(['relation', 'relation.unit_Type'])->whereHas('relation', function($query) use($user) {
                                return $query->where('user_type', $user->type);
                            })->where('type_en', $operation_type)->exists();
                if($check){
                  return  $this->Operations($request, $operation_type);
                //    $return =
                //    if($return->getStatusCode() == 201){
                //        return $return;
                //    }else if($return->getStatusCode() == 200){
                //         return  $this->generateUnit($request);
                //    }
                }else{
                    $resulte                 = [];
                    $resulte['success']      = false;
                    $resulte['type']         = 'operation_not_valid';
                    $resulte['title']        = __('cms::base.operation_not_valid.title');
                    $resulte['description']  = __('cms::base.operation_not_valid.description');
                    return response()->json($resulte, 400);
                }
            }catch (Exception $e){
                return response()->json([
                    'success'     => false,
                    'type'        => 'error',
                    'title'       => __('cms::base.msg.error_message.title'),
                    'description' => __('cms::base.msg.error_message.description'),
                    'errors'      => '['. $e->getMessage() .']'
                ], 500);
            }
        }else{
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
            return response()->json($resulte, 400);
        }
    }
}
