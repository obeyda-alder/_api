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
use App\Models\Entities\RelationUnitTypeWithOperations;
use App\Models\Entities\MoneyHistory;
use App\Models\Entities\UnitsMovement;
use App\Models\Entities\PackingOrder;
use \Illuminate\Support\Str;
use App\Models\Entities\Config;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;

trait Transactions {

    use Helper;

    protected static function transaction($type, $user)
    {
        //
    }
    public function generateCode($length = 14, $pre, $money = false)
    {
        $code = Keygen::alphanum($length)->prefix($pre)->generate();
        if(($money && MoneyHistory::where('money_code', $code)->exists()) || (!$money && units::where('unit_code', $code)->exists()))
        {
            return $this->generateCode($length, $pre);
        }
        return strtoupper($code);
    }
    public function TransactionsOperations($request)
    {
            $relations = RelationUnitTypeWithOperations::with('from_unit_type','to_unit_type','operation', 'operation.relation')->where('operation_id', $request->operations['id'])->first();
            if(!is_null($relations)){
                $request->merge(['relations' => $relations]);
                switch($request->operations['operation']){
                    case "CENTRAL_OBSTETRICS":
                        return $this->generateUnit($request);
                        break;
                    default:
                        return $this->actions($request);
                        break;
                }
            }else{
                return response()->json([
                    'success'     => false,
                    'type'        => 'error',
                    'title'       => __('api.error_message.title'),
                    'description' => __('api.this_operation_type_dont_have_any_operations_in_unit_type.description'),
                ], 500);
            }
    }
    public function generateUnit($request)
    {
        $user = $request->user;
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
            'currencies' => 'required|exists:ince_transfer_config,id',
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

        $from_unit_type_id = $request->relations->from_unit_type_id;
        try{
            \DB::transaction(function() use ($request, $user, $from_unit_type_id) {
                $currencies = Config::findOrFail($request->currencies);
                $price = $currencies->price * $request->unit_value;
                $data                   = new units;
                $data->unit_code        = $this->generateCode(14, 'UN-');
                $data->unit_type_id     = $from_unit_type_id;
                $data->price            = $price;
                $data->unit_value       = $request->unit_value;
                $data->add_by           = $user->id;
                $data->status           = "ACTIVE";
                $data->save();

                $user->money()->where('config_currency_id', $currencies->id)->decrement('amount', $price);
                $user->unit()->increment('unit_count', $request->unit_value);
                $unit_type = $user->user_units->where('unit_type_id', $from_unit_type_id)->first();
                $unit_safe = $unit_type->unit_type_safe;
                $unit_safe->unit_code = $data->unit_code;
                $unit_safe->increment('unit_type_count', $request->unit_value);
                $unit_safe->save();

                $operation = $request->operations['operation'];
                $this->mony_history($operation, $price, $user->id, $user->id);
                $this->units_movement($data->unit_code, $operation, $request->unit_value, $user->id, $user->id);
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
    public function actions($request)
    {
        $user = $request->user;
        if(!in_array($user->type, ["ADMIN","EMPLOYEE","MASTER_AGENT","SUB_AGENT"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
             return response()->json($resulte, 400);
        }
        $operation = $request->operations['operation'];
        $validator = [
            'to_user_id'   => [Rule::requiredIf($operation != "PACKING"), Rule::exists('users', 'id')->where('type', Str::after($request->type, '_TO_'))],
            'unit_value'   => 'required|min:0',
            'currencies'   => [Rule::requiredIf($operation != "PACKING"), Rule::exists('ince_transfer_config', 'id')],
        ];

        $validator = Validator::make($request->all(), $validator);

        if ($validator->fails()) {
            $resulte                  = [];
            $resulte['success']       = false;
            $resulte['type']          = 'error';
            $resulte['errors']        = $validator->errors();
            return response()->json($resulte, 400);
        }


        $from_user = $user;
        if($operation != "PACKING"){
            $to_user   = User::with(['city', 'unit', 'money', 'user_units', 'user_units.unit_type_safe', 'type_unit_type', 'actions', 'actions.operations'])->find($request->to_user_id);
            if($from_user->unit->unit_count >= $request->unit_value) {
                try{
                    \DB::transaction(function() use ($request, $from_user, $to_user, $operation) {
                        $currencies = Config::findOrFail($request->currencies);
                        $price = $currencies->price * $request->unit_value;
                        $from_unit_type_id = $request->relations->from_unit_type_id;
                        $to_unit_type_id = $request->relations->to_unit_type_id;

                        // from_user =>
                        $from_user->money()->where('config_currency_id', $currencies->id)->increment('amount', $price);
                        $from_user->unit()->decrement('unit_count', $request->unit_value);
                        $f_unit_type = $from_user->user_units->where('unit_type_id', $from_unit_type_id)->first();
                        $f_unit_safe = $f_unit_type->unit_type_safe;
                        $f_unit_safe->decrement('unit_type_count', $request->unit_value);


                        // to_user =>
                        $to_user->money()->where('config_currency_id', $currencies->id)->decrement('amount', $price);
                        $to_user->unit()->increment('unit_count', $request->unit_value);
                        $t_unit_type = $to_user->user_units->where('unit_type_id', $to_unit_type_id)->first();
                        $t_unit_safe = $t_unit_type->unit_type_safe;
                        $t_unit_safe->unit_code = $f_unit_safe->unit_code;
                        $t_unit_safe->increment('unit_type_count', $request->unit_value);
                        $t_unit_safe->save();
                        // history
                        $this->mony_history($operation, $price, $to_user->id, $from_user->id);
                        $this->units_movement($f_unit_safe->unit_code, $operation, $request->unit_value, $to_user->id, $from_user->id);
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
                'order_from_user_id'  => $from_user->id,
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
            'money_code'     => $this->generateCode(14, 'MO-', true),
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
