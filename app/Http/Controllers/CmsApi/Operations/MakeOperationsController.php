<?php

namespace App\Http\Controllers\CmsApi\Operations;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
use App\Models\Entities\RelationUnitTypeWithOperations;
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
        $user = auth()->guard('api')->user()->load(['unit', 'money', 'user_units', 'user_units.unit_type_safe', 'type_unit_type', 'actions', 'actions.operations']);
        if(in_array($user->type, config('custom.users_type')))
        {
            try{
                $operations = collect(...$user->actions->map(function ($t) {
                    return $t->operations->map(function ($i) {
                        return [
                            'operation' => $i->type_en
                        ];
                    });
                }));

                if(!empty($operations->where('operation', $operation_type))) {
                    $opera =  $this->TransactionsOperations($request, $operation_type, $user);
                    if($opera->getStatusCode() != 200){
                         return $opera;
                    }
                } else {
                    $resulte                 = [];
                    $resulte['success']      = false;
                    $resulte['type']         = 'operation_not_valid';
                    $resulte['title']        = __('api.operation_not_valid.title');
                    $resulte['description']  = __('api.operation_not_valid.description');
                    return response()->json($resulte, 400);
                }
            }catch (Exception $e){
                return response()->json([
                    'success'     => false,
                    'type'        => 'error',
                    'title'       => __('api.error_message.title'),
                    'description' => __('api.error_message.description'),
                    'errors'      => '['. $e->getMessage() .']'
                ], 500);
            }
        }else{
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
            return response()->json($resulte, 400);
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('api.success_message.title'),
            'description' => __('api.success_message.description'),
        ], 200);
    }
}
