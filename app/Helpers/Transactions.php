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
        $UNIT_NAME = $request->get('UNIT_NAME');
        $unit_type_id = null;
        // dd($UNIT_NAME, $type, $user->type_unit_type->where('type', $UNIT_NAME)->first()->id);
        switch($type){
            case "CENTRAL_OBSTETRICS":
                $unit_type_id = $user->type_unit_type->where('type', $UNIT_NAME)->first()->id;
                return $this->generateUnit($request, $user, $unit_type_id);
                break;
            case "INDEPENDENCE":
                return $this->independenceUnit($request, $user);
                break;
            default:
                return false;
        }
    }
    public function generateUnit($request, $user, $unit_type_id)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
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
            $resulte['type']          = 'validations_error';
            $resulte['errors']        = $validator->errors();
            return response()->json($resulte, 400);
        }


        try{
            \DB::transaction(function() use ($request, $user, $unit_type_id) {
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


                $trans = MoneyHistory::create([
                    'money_code'     => $this->generateCode(14, 'MO-'),
                    'transfer_type'  => "GENERATE UNITS",
                    'amount'         => $request->price,
                    'to_user_id'     => $user->id,
                    'from_user_id'   => $user->id,
                    'status'         => "ACTIVE",
                ]);
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
    public function independenceUnit($request, $user)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('api.permission_denied.title');
            $resulte['description']  = __('api.permission_denied.description');
             return response()->json($resulte, 400);
        }

        $validator = [
            'from_unit_type_id' => 'required|exists:unit_type,id',
            'to_unit_type_id'   => 'required|exists:unit_type,id',
            'to_user_id'        => ['required', Rule::exists('users', 'id')->where('type', 'EMPLOYEE')],
            'unit_value'        => 'required|min:0',
        ];

        $validator = Validator::make($request->all(), $validator);

        if ($validator->fails()) {
            $resulte                  = [];
            $resulte['success']       = false;
            $resulte['type']          = 'validations_error';
            $resulte['errors']        = $validator->errors();
            return response()->json($resulte, 400);
        }

        $from = $user->load(['money', 'unit']);
        $to   = User::with(['money', 'unit'])->find($request->to_user_id);

        if($from->unit->unit_count >= $request->unit_value) {

        try{
            \DB::transaction(function() use ($request, $from, $to) {
                    //from => admin
                    $this->decrement_increment_model(UnitTypesSafe::class,'decrement',$request->from_unit_type_id, null, $request->unit_value);
                    $this->decrement_increment_model(UnitsSafe::class,'decrement', null, $from->id, $request->unit_value);

                    //to => employee
                    $this->decrement_increment_model(UnitTypesSafe::class,'increment',$request->to_unit_type_id, null, $request->unit_value);
                    $this->decrement_increment_model(UnitsSafe::class,'increment', null, $to->id, $request->unit_value);
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
}
