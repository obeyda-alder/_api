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
            case "INDEPENDENCE":
                return $this->independenceUnit($request, $user, $type);
                break;
            default:
                return false;
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
                $user->user_units->where('unit_type_id', $unit_type_id)->first()
                   ->unit_type_safe->first()->increment('unit_type_count', $request->unit_value);

                $this->mony_history($request->price, $user->id, $user->id, $type);
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
    public function independenceUnit($request, $user, $type)
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
            'to_user_id'   => ['required', Rule::exists('users', 'id')->where('type', 'EMPLOYEE')],
            'unit_value'   => 'required|min:0',
            'price'        => 'required|min:0',
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
        $to   = User::with(['city', 'unit', 'money', 'user_units', 'user_units.unit_type_safe', 'type_unit_type', 'actions', 'actions.operations'])->find($request->to_user_id);
        if($from->unit->unit_count >= $request->unit_value) {
            try{
                \DB::transaction(function() use ($request, $from, $to, $type) {
                    $from_unit_type_id = $from->type_unit_type->where('type', $request->FROM_UNIT_NAME)->first()->id;
                    $to_unit_type_id = $to->type_unit_type->where('type', $request->TO_UNIT_NAME)->first()->id;

                    //from => admin
                    $from->money()->increment('amount', $request->price);
                    $from->unit()->decrement('unit_count', $request->unit_value);
                    $from->user_units->where('unit_type_id', $from_unit_type_id)->first()
                    ->unit_type_safe->first()->decrement('unit_type_count', $request->unit_value);

                    //to => employee
                    $to->money()->increment('amount', $request->price);
                    $to->unit()->decrement('unit_count', $request->unit_value);
                    $to->user_units->where('unit_type_id', $to_unit_type_id)->first()
                    ->unit_type_safe->first()->decrement('unit_type_count', $request->unit_value);
                    $this->mony_history($request->price, $to->id, $from->id, $type);
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

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('api.success_message.title'),
            'description' => __('api.success_message.description'),
        ], 200);
    }
    public function mony_history($price, $to, $from, $type)
    {
        $trans = MoneyHistory::create([
            'money_code'     => $this->generateCode(14, 'MO-'),
            'transfer_type'  => $type,
            'amount'         => $price,
            'to_user_id'     => $to,
            'from_user_id'   => $from,
            'status'         => "ACTIVE",
        ]);
        $trans->save();
    }
}
