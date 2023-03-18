<?php

namespace App\Helpers;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Models\Entities\Units;
use App\Models\Entities\UnitsSafe;
use Keygen\Keygen;

trait Transactions {

    use Helper;

    public function generateCode($length = 14)
    {
        $code = Keygen::alphanum($length)->prefix('UN-')->generate(); //->prefix('U')->suffix('G')->generate();

        if(units::where('unit_code', $code)->exists())
        {
            return $this->generateCode($length);
        }

        return strtoupper($code);
    }

    public function Operations($request , $type)
    {
        // $validator = [];
        switch($type){
            case "CENTRAL_OBSTETRICS":
                return $this->generateUnit($request);
                // $validator = [
                //     'unit_type_id'    => 'required|exists:unit_type,id',
                //     'price'           => 'required|min:0',
                //     'unit_value'      => 'required|min:0',
                // ];
                break;
            default:
            // $validator = [];
        }
        // $validator = Validator::make($request->all(), $validator);

        // if ($validator->fails()) {
        //     $resulte                  = [];
        //     $resulte['success']       = false;
        //     $resulte['type']          = 'validations_error';
        //     $resulte['errors']        = $validator->errors();
        //     return response()->json($resulte, 201);
        // }else{
        //     $resulte                  = [];
        //     $resulte['success']       = true;
        //     $resulte['type']          = 'success';
        //     $resulte['title']         = __('cms::base.msg.success_message.title');
        //     return response()->json($resulte, 200);
        // }
    }
    public function generateUnit($request)
    {
        $user = auth()->guard('api')->user();

        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
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
            \DB::transaction(function() use ($request, $user) {
                $data = new units;
                $data->unit_code        = $this->generateCode();
                $data->unit_type_id     = $request->unit_type_id;
                $data->price            = $request->price;
                $data->unit_value       = $request->unit_value;
                $data->add_by           = $user->id;
                $data->status           = $request->status;
                $data->save();

                $safe               = UnitsSafe::where('user_id', $user->id)->first();
                $safe->unit_count   = $request->unit_value;
                $safe->save();
            });
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
    protected static function transaction($type, $user)
    {
        //
    }
}
