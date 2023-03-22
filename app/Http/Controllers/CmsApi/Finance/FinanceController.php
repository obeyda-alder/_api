<?php

namespace App\Http\Controllers\CmsApi\Finance;

use Illuminate\Routing\Controller;
use App\Models\Entities\Categories;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Helpers\Helper;
use App\Helpers\DataLists;
use App\Models\User;
use Illuminate\Http\Request;
use Keygen\Keygen;
use App\Models\Entities\MoneySafe;
use App\Models\Entities\MoneyHistory;

class FinanceController extends Controller
{
    use Helper, DataLists;

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }
    public function generateCode($length = 14)
    {
        $code = Keygen::numeric($length)->prefix('MO-')->generate();

        if(MoneyHistory::where('money_code', $code)->exists())
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

        $money = MoneyHistory::orderBy('id', 'DESC')->whereNull('deleted_at');

        if($request->has('search') && !empty($request->search))
        {
            $money->where(function($q) use ($request) {
                $q->where('money_code', 'like', "%{$request->search}%")
                ->orWhere('amount', 'like', "%{$request->search}%")
                ->orWhere('status', 'like', "%{$request->search}%");
            });
        }

        $resulte              = [];
        $resulte['success']   = true;
        $resulte['message']   = __('api.money_history_data');
        $resulte['count']     = $money->count();
        $resulte['data']      = $money->get();
        return response()->json($resulte, 200);
    }
    public function BatchCreation(Request $request)
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
            'transfer_type'  => 'required|string|max:255',
            'amount'         => 'required|integer',
            'to_user_id'     => 'required|exists:users,id',
            'status'         => 'required|in:ADD',
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
                $trans = MoneyHistory::create([
                    'money_code'     => $this->generateCode(),
                    'transfer_type'  => $request->transfer_type,
                    'amount'         => $request->amount,
                    'to_user_id'     => $request->to_user_id,
                    'from_user_id'   => $user->id,
                    'status'         => $request->status,
                ]);

                User::with(['money'])->find($request->to_user_id)
                ->money()->increment('amount', $request->amount);

                $trans->save();
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
            $MoneyHistory = MoneyHistory::findOrFail($id);
            $MoneyHistory->delete();
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
            $MoneyHistory = MoneyHistory::withTrashed()->findOrFail($id);
            $MoneyHistory->forceDelete();
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
            $MoneyHistory = MoneyHistory::withTrashed()->findOrFail($id);
            $MoneyHistory->restore();
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
