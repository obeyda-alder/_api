<?php

namespace App\Http\Controllers\CmsApi\Agencies;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Entities\MasterAgencies;
use App\Models\Entities\SubAgencies;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Helpers\Helper;
use App\Helpers\DataLists;
use App\Models\User;
use Illuminate\Validation\Rule;

class AgenciesController extends Controller
{
    use Helper, DataLists;

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }
    public function index(Request $request, $type)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        if($type == 'master_agent'){
            $data = MasterAgencies::with(['user', 'translations'])->whereNull('deleted_at');
        }else if($type == 'sub_agent'){
            $data = SubAgencies::with(['master', 'master.user', 'translations'])->whereNull('deleted_at');
        }

        $data->orderBy('id', 'DESC');

        if($request->has('search') && !empty($request->search))
        {
            $data->where(function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search['value']}%")
                ->orWhere('last_name', 'like', "%{$request->search['value']}%")
                ->orWhere('email', 'like', "%{$request->search['value']}%");
            });
        }

        $resulte              = [];
        $resulte['success']   = true;
        $resulte['message']   = __('cms.base.'.$type.'_data');
        $resulte['count']     = $data->count();
        $resulte['data']      = $data->get();
        return response()->json($resulte, 200);
    }
    public function create(Request $request, $type)
    {
        $user = auth()->user();
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
            'first_name'         => 'required|string|max:255',
            'last_name'          => 'required|string|max:255',
            'title'              => 'required|string|max:255',
            'description'        => 'required|string|max:255',
            'country_id'         => 'sometimes|exists:countries,id',
            'city_id'            => 'sometimes|exists:cities,id',
            'municipality_id'    => 'sometimes|exists:municipalities,id',
            'neighborhood_id'    => 'sometimes|exists:neighborhoods,id',
            'desc_address'       => 'nullable|string|max:255',
            'latitude'           => 'nullable|string|max:255',
            'longitude'          => 'nullable|string|max:255',
            'iban'               => 'nullable|string|max:255',
            'iban_name'          => 'nullable|string|max:255',
            'iban_type'          => 'nullable|string|max:255',
            'status'             => 'required|in:ACTIVE,SUSPENDED,PENDING',
            'image'              => 'sometimes|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'user_id'            => [Rule::requiredIf($type == 'master_agent'),Rule::exists('users', 'id')->where('type', 'AGENCIES')],
            'master_agencies_id' => [Rule::requiredIf($type == 'sub_agent'),'exists:master_agencies,id'],
        ];

        if($type == 'master_agent'){
            $validator = array_merge($validator, [
                'email'          => ['required', 'email', Rule::unique('master_agencies', 'email')],
                'phone_number'   => ['required' , Rule::unique('master_agencies', 'phone_number')],
            ]);
        }else if($type == 'sub_agent'){
            $validator = array_merge($validator, [
                'email'          => ['required', 'email', Rule::unique('sub_agencies', 'email')],
                'phone_number'   => ['required' , Rule::unique('sub_agencies', 'phone_number')],
            ]);
        }

        $validator = Validator::make($request->all(), $validator);

        if ($validator->fails()) {
            $resulte              = [];
            $resulte['success']   = false;
            $resulte['type']      = 'validations_error';
            $resulte['errors']     = $validator->errors();
            return response()->json($resulte, 400);
        }

        try{
            DB::transaction(function() use ($request, $user , $type ) {

                if($type == 'master_agent'){
                    $data = new MasterAgencies;
                    $data->user_id          = $request->user_id;
                    $data->has_sub_agent    = '0';
                    $data->sub_agent_count  = '0';
                }else if($type == 'sub_agent'){
                    $data = new SubAgencies;
                    $data->master_agencies_id  = $request->master_agencies_id;
                }

                foreach (\LaravelLocalization::getSupportedLanguagesKeys() as $loc) {
                    $data->{'title:' . $loc} = "{$request->title}";
                    $data->{'description:' . $loc} = "{$request->description}";
                }

                $data->first_name        = $request->first_name;
                $data->last_name         = $request->last_name;
                $data->email             = $request->email;
                $data->country_id        = $request->country_id;
                $data->city_id           = $request->city_id;
                $data->municipality_id   = $request->municipality_id;
                $data->neighborhood_id   = $request->neighborhood_id;
                $data->desc_address      = $request->desc_address;
                $data->latitude          = $request->latitude;
                $data->longitude         = $request->longitude;
                $data->iban              = $request->iban;
                $data->iban_name         = $request->iban_name;
                $data->iban_type         = $request->iban_type;
                $data->phone_number      = $request->phone_number;
                $data->status            = $request->status;

                if(!is_null($data->image))
                {
                    $this->deleteImgByFileName('agencies', $data->image); //[[0 => 100, 1 => 100],[0 => 50, 1 => 50]]
                }


                if($request->hasFile('image'))
                {
                    $path =  $this->UploadWithResizeImage($request, 'agencies', [[0 => 100, 1 => 100],[0 => 50, 1 => 50]]);
                    foreach (\LaravelLocalization::getSupportedLanguagesKeys() as $loc) {
                        $data->{'image:' . $loc} = $path;
                    }
                }

                $data->save();
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

        if($type == 'sub_agent'){
            try{
                DB::transaction(function() use ($request) {
                    $update_master = MasterAgencies::findOrFail($request->master_agencies_id);
                    $update_master->has_sub_agent    = 1;
                    $update_master->sub_agent_count  = $update_master->sub_agent_count + 1;
                    $update_master->save();
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
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('cms::base.msg.success_message.title'),
            'description' => __('cms::base.msg.success_message.description'),
        ], 200);
    }
    public function update(Request $request, $id, $type)
    {
        $user = auth()->user();
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
            'first_name'         => 'required|string|max:255',
            'last_name'          => 'required|string|max:255',
            'title'              => 'required|string|max:255',
            'description'        => 'required|string|max:255',
            'country_id'         => 'sometimes|exists:countries,id',
            'city_id'            => 'sometimes|exists:cities,id',
            'municipality_id'    => 'sometimes|exists:municipalities,id',
            'neighborhood_id'    => 'sometimes|exists:neighborhoods,id',
            'desc_address'       => 'nullable|string|max:255',
            'latitude'           => 'nullable|string|max:255',
            'longitude'          => 'nullable|string|max:255',
            'iban'               => 'nullable|string|max:255',
            'iban_name'          => 'nullable|string|max:255',
            'iban_type'          => 'nullable|string|max:255',
            'status'             => 'required|in:ACTIVE,SUSPENDED,PENDING',
            'image'              => 'sometimes|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
        ];


        if($type == 'master_agent'){
            $validator = array_merge($validator, [
                'email'          => ['required', 'email', Rule::unique('master_agencies', 'email')->ignore($id)],
                'phone_number'   => ['required' , Rule::unique('master_agencies', 'phone_number')->ignore($id)],
            ]);
        }else if($type == 'sub_agent'){
            $validator = array_merge($validator, [
                'email'          => ['required', 'email', Rule::unique('sub_agencies', 'email')->ignore($id)],
                'phone_number'   => ['required', Rule::unique('sub_agencies', 'phone_number')->ignore($id)],
            ]);
        }

        $validator = Validator::make($request->all(), $validator);

        if ($validator->fails()) {
            $resulte              = [];
            $resulte['success']   = false;
            $resulte['type']      = 'validations_error';
            $resulte['errors']     = $validator->errors();
            return response()->json($resulte, 400);
        }

        try{
            DB::transaction(function() use ($request, $user , $id, $type ) {

                if($type == 'master_agent'){
                    $data = MasterAgencies::findOrFail($id);
                }else if($type == 'sub_agent'){
                    $data = SubAgencies::findOrFail($id);
                }

                foreach (\LaravelLocalization::getSupportedLanguagesKeys() as $loc) {
                    $data->{'title:' . $loc} = "{$request->title}";
                    $data->{'description:' . $loc} = "{$request->description}";
                }

                $data->first_name        = $request->first_name;
                $data->last_name         = $request->last_name;
                $data->email             = $request->email;
                $data->phone_number      = $request->phone_number;
                $data->country_id        = $request->country_id;
                $data->city_id           = $request->city_id;
                $data->municipality_id   = $request->municipality_id;
                $data->neighborhood_id   = $request->neighborhood_id;
                $data->desc_address      = $request->desc_address;
                $data->latitude          = $request->latitude;
                $data->longitude         = $request->longitude;
                $data->iban              = $request->iban;
                $data->iban_name         = $request->iban_name;
                $data->iban_type         = $request->iban_type;
                $data->status            = $request->status;

                if(!is_null($data->image))
                {
                    $this->deleteImgByFileName('agencies', $data->image); //[[0 => 100, 1 => 100],[0 => 50, 1 => 50]]
                }


                if($request->hasFile('image'))
                {
                    $path =  $this->UploadWithResizeImage($request, 'agencies', [[0 => 100, 1 => 100],[0 => 50, 1 => 50]]);
                    foreach (\LaravelLocalization::getSupportedLanguagesKeys() as $loc) {
                        $data->{'image:' . $loc} = $path;
                    }
                }

                $data->save();
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
    public function softDelete(Request $request, $id, $type)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        try{
            DB::transaction(function() use ($request, $type, $id) {
                if($type == 'master_agent'){
                    $agent = MasterAgencies::withTrashed()->findOrFail($id);
                }elseif($type == 'sub_agent'){
                    $agent = SubAgencies::withTrashed()->findOrFail($id);
                }
                $agent->delete();
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
    public function delete(Request $request, $id, $type)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        try{
            DB::transaction(function() use ($request, $type, $id) {
                if($type == 'master_agent'){
                    $agent = MasterAgencies::withTrashed()->findOrFail($id);
                }elseif($type == 'sub_agent'){
                    $agent = SubAgencies::withTrashed()->findOrFail($id);
                }
                $agent->forceDelete();
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
    public function restore(Request $request, $id, $type)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        try{
            DB::transaction(function() use ($request, $type, $id) {
                if($type == 'master_agent'){
                    $agent = MasterAgencies::withTrashed()->findOrFail($id);
                }elseif($type == 'sub_agent'){
                    $agent = SubAgencies::withTrashed()->findOrFail($id);
                }
                $agent->restore();
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
}
