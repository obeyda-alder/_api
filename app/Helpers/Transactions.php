<?php

namespace App\Helpers;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Models\Entities\Units;
use App\Models\Entities\UnitsSafe;
use App\Models\Entities\MoneySafe;
use App\Models\Entities\UnitTypesSafe;
use Keygen\Keygen;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Entities\Operations;
use App\Models\Entities\MoneyHistory;
use App\Models\Entities\UnitsMovement;
use App\Models\Entities\PackingOrder;
use \Illuminate\Support\Str;

trait Transactions {

    use Helper;

    protected static function transaction($type, $user)
    {
        //
    }
    public function generateCode($length = 14, $pre)
    {
        $code = Keygen::alphanum($length)->prefix($pre)->generate();
        if(units::where('unit_code', $code)->exists())
        {
            return $this->generateCode($length, $pre);
        }
        return strtoupper($code);
    }
    public function TransactionsOperations($request , $type,  $user)
    {
        $request->merge([
            'FROM_UNIT_NAME' => $request->get('FROM_UNIT_NAME'),
            'TO_UNIT_NAME'   => $request->get('TO_UNIT_NAME'),
        ]);

        switch($type){
            case "CENTRAL_OBSTETRICS":
                return $this->generateUnit($request, $user, $type);
                break;
            default:
                return $this->actions($request, $user, $type);
                break;
        }
    }
    public function generateUnit($request, $user, $type)
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

        $validator = [
            'price'      => 'required|min:0',
            'unit_value' => 'required|min:0',
        ];

        $validator = Validator::make($request->all(), $validator);

        if ($validator->fails()) {
            $resulte                  = [];
            $resulte['success']       = false;
            $resulte['type']          = 'error';
            $resulte['errors']        = $validator->errors();
            return response()->json($resulte, 400);
        }


        try{
            \DB::transaction(function() use ($request, $user, $type) {
                $unit_type_id = $user->type_unit_type->where('type', $request->FROM_UNIT_NAME)->first()->id;
                $data                   = new units;
                $data->unit_code        = $this->generateCode(14, 'UN-');
                $data->unit_type_id     = $unit_type_id;
                $data->price            = $request->price;
                $data->unit_value       = $request->unit_value;
                $data->add_by           = $user->id;
                $data->status           = "ACTIVE";
                $data->save();

                $user->money()->decrement('amount', $request->price);
                $user->unit()->increment('unit_count', $request->unit_value);
                $unit_type = $user->user_units->where('unit_type_id', $unit_type_id)->first();
                $unit_safe = $unit_type->unit_type_safe->first();
                $unit_safe->unit_code = $data->unit_code;
                $unit_safe->increment('unit_type_count', $request->unit_value);
                $unit_safe->save();

                $this->mony_history($type, $request->price, $user->id, $user->id);
                $this->units_movement($data->unit_code, $type, $data->unit_value, $user->id, $user->id);
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
    public function actions($request, $user, $type)
    {
        if(!in_array(auth()->guard('api')->user()->type, ["ADMIN","EMPLOYEE","MASTER_AGENT","SUB_AGENT"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $validator = [
            'to_user_id'   => [Rule::requiredIf($type != "PACKING"), Rule::exists('users', 'id')->where('type', Str::after($request->type, '_TO_'))],
            'unit_value'   => 'required|min:0',
            'price'        => [Rule::requiredIf($type != "PACKING"), 'min:0'],
        ];

        $validator = Validator::make($request->all(), $validator);

        if ($validator->fails()) {
            $resulte                  = [];
            $resulte['success']       = false;
            $resulte['type']          = 'error';
            $resulte['errors']        = $validator->errors();
            return response()->json($resulte, 400);
        }


        $from = $user->load(['money', 'unit']);
        if($type != "PACKING"){
            $to   = User::with(['city', 'unit', 'money', 'user_units', 'user_units.unit_type_safe', 'type_unit_type', 'actions', 'actions.operations'])->find($request->to_user_id);
            if($from->unit->unit_count >= $request->unit_value) {
                try{
                    \DB::transaction(function() use ($request, $from, $to, $type) {
                        $from_unit_type_id = $from->type_unit_type->where('type', $request->FROM_UNIT_NAME)->first()->id;
                        $to_unit_type_id = $to->type_unit_type->where('type', $request->TO_UNIT_NAME)->first()->id;

                        //from =>
                        $from->money()->increment('amount', $request->price);
                        $from->unit()->decrement('unit_count', $request->unit_value);
                        $f_unit_type = $from->user_units->where('unit_type_id', $from_unit_type_id)->first();
                        $f_unit_safe = $f_unit_type->unit_type_safe->first();
                        $f_unit_safe->decrement('unit_type_count', $request->unit_value);

                        //to =>
                        $to->money()->increment('amount', $request->price);
                        $to->unit()->decrement('unit_count', $request->unit_value);
                        $t_unit_type = $to->user_units->where('unit_type_id', $to_unit_type_id)->first();
                        $t_unit_safe = $t_unit_type->unit_type_safe->first();
                        $t_unit_safe->decrement('unit_type_count', $request->unit_value);

                        $this->mony_history($type, $request->price, $to->id, $from->id);
                        $this->units_movement($f_unit_safe->unit_code, $type, $request->unit_value, $to->id, $from->id);
                        $this->units_movement($t_unit_safe->unit_code, $type, $request->unit_value, $to->id, $from->id);
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
            } else {
                return response()->json([
                    'success'     => false,
                    'type'        => 'error',
                    'title'       => __('api.you_do_not_have_enough_units_to_process.title'),
                    'description' => __('api.you_do_not_have_enough_units_to_process.description'),
                ], 201);
            }
        }else{
            $order = PackingOrder::create([
                'order_from_user_id'  => $from->id,
                'quantity'            => $request->unit_value,
                'order_status'        => 'Unfinished',
            ]);
            $order->save();
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('api.success_message.title'),
            'description' => __('api.success_message.description'),
        ], 200);
    }
    public function mony_history($transfer_type, $amount, $to_user_id, $from_user_id)
    {
        $trans = MoneyHistory::create([
            'money_code'     => $this->generateCode(14, 'MO-'),
            'transfer_type'  => $transfer_type,
            'amount'         => $amount,
            'to_user_id'     => $to_user_id,
            'from_user_id'   => $from_user_id,
            'status'         => "ACTIVE",
        ]);
        $trans->save();
    }
    public function units_movement($unit_code, $transfer_type, $quantity, $to_user_id, $from_user_id)
    {
        $trans = UnitsMovement::create([
            'unit_code'      => $unit_code,
            'transfer_type'  => $transfer_type,
            'quantity'       => $quantity,
            'to_user_id'     => $to_user_id,
            'from_user_id'   => $from_user_id,
            'status'         => "ACTIVE",
        ]);
        $trans->save();
    }
}
