<?php

namespace App\Http\Controllers\Agencies;

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
use App\Models\User;

class AgenciesController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['']]);
    }
    public function OfType($agencies_type)
    {
        $agencies_type = strtolower($agencies_type);
        $agencies_types = config('custom.agencies_type');
        if(in_array($agencies_type, $agencies_types)){
            return $agencies_type;
        }
    }
    public function index(Request $request, $agencies_type)
    {
        $agencies_type = $this->OfType($agencies_type);
        return view('cms::backend.agencies.index', ['agencies_type' => $agencies_type ]);
    }
    public function data(Request $request)
    {
        if($request->agencies_type == 'master_agent'){
            $data = MasterAgencies::orderBy('id', 'DESC')->withTrashed();
        } else if($request->agencies_type == 'sub_agent') {
            $data = SubAgencies::orderBy('id', 'DESC')->withTrashed();
        }

        $data->get();
        return Datatables::of($data)
        ->filter(function($query) use ($request) {
            if(!is_null($request->search['value']))
            {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search['value']}%")
                    ->orWhere('email', 'like', "%{$request->search['value']}%")
                    ->orWhere('type', 'like', "%{$request->search['value']}%");
                });
            }
        })
        ->addIndexColumn()
        ->addColumn('title', function ($agent) {
            return $agent->translate()->title ?? '';
        })
        ->addColumn('first_name', function ($agent) {
            return $agent->first_name ?? '';
        })
        ->addColumn('last_name', function ($agent) {
            return $agent->last_name ?? '';
        })
        ->addColumn('phone_number', function ($agent) {
            return $agent->phone_number ?? '';
        })
        ->addColumn('email', function ($agent) {
            return $agent->email ?? '';
        })
        ->addColumn('status', function ($agent) {
            return $agent->status ?? '';
        })
        ->addColumn('actions', function($agent) use($request){
            $actions   = [];
            if($agent->trashed()) {
                $actions[] = [
                    'id'            => $agent->id,
                    'label'         => __('cms::base.restore'),
                    'type'          => 'icon',
                    'link'          => route('cms::agencies::restore', ['id' => $agent->id, 'agencies_type' => $request->agencies_type ]),
                    'method'        => 'POST',
                    'request_type'  => 'agent_restore_'.$agent->id,
                    'class'         => 'restore-action',
                    'icon'          => 'fas fa-trash-restore'
                ];
                $actions[] = [
                    'id'            => $agent->id,
                    'label'         => __('cms::base.delete'),
                    'type'          => 'icon',
                    'link'          => route('cms::agencies::delete', ['id' => $agent->id, 'agencies_type' => $request->agencies_type ]),
                    'method'        => 'POST',
                    'request_type'  => 'delete_'.$agent->id,
                    'class'         => 'delete-action',
                    'icon'          => 'fas fa-user-times'
                ];
            } else {
                $actions[] = [
                    'id'            => $agent->id,
                    'label'         => __('cms::base.soft_delete'),
                    'type'          => 'icon',
                    'link'          => route('cms::agencies::soft_delete', ['id' => $agent->id, 'agencies_type' => $request->agencies_type ]),
                    'method'        => 'POST',
                    'request_type'  => 'soft_delete_'.$agent->id,
                    'class'         => 'soft-delete-action',
                    'icon'          => 'fas fa-trash'
                ];
                $actions[] = [
                    'id'            => $agent->id,
                    'label'         => __('cms::base.update'),
                    'type'          => 'icon',
                    'link'          => route('cms::agencies::edit', ['id' => $agent->id, 'agencies_type' => $request->agencies_type ]),
                    'method'        => 'GET',
                    'request_type'  => 'update_'.$agent->id,
                    'class'         => 'update-action',
                    'icon'          => 'fas fa-edit'
                ];
            }
            return $actions;
        })
        ->rawColumns([])
        ->make(true);
    }
    public function create(Request $request)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }

        $agencies_type  = $this->OfType($request->agencies_type);
        $countries      = $this->getCountry(app()->getLocale());
        $users          = User::where('type', 'AGENCIES')->get();
        $agencies       = MasterAgencies::get();
        return view('cms::backend.agencies.create', [
            'agencies_type' => $agencies_type,
            'countries'     => $countries,
            'users'         => $users,
            'agencies'      => $agencies
        ]);
    }
    public function store(Request $request)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }

        $userValidator = [
            'first_name'         => 'required|string|max:255',
            'last_name'          => 'required|string|max:255',
            'email'              => 'required|unique:master_agencies|email',
            'title'              => 'nullable',
            'description'        => 'nullable',
            'agencies_type'      => 'required|in:master_agent,sub_agent',
            'user_id'            => 'required_if:agencies_type,=,master_agent|exists:users,id',
            'master_agent_id'    => 'required_if:agencies_type,=,sub_agent|exists:master_agencies,id',
        ];

        $validator = Validator::make($request->all(), $userValidator);
        if(!$validator->fails())
        {
            try{
                DB::transaction(function() use ($request) {
                    if($request->agencies_type == 'master_agent'){
                        $data                   = new MasterAgencies;
                        $data->user_id          = $request->user_id;
                        $data->has_sub_agent    = '0';
                        $data->sub_agent_count  = '0';
                    } else if($request->agencies_type == 'sub_agent') {
                        $data                   = new SubAgencies;
                        $data->master_agent_id  = $request->master_agent_id;
                    }

                    foreach (\LaravelLocalization::getSupportedLanguagesKeys() as $loc) {
                        $data->{'title:' . $loc} = "{$request->title}";
                        $data->{'description:' . $loc} = "{$request->description}";
                    }

                    $data->first_name           = $request->first_name;
                    $data->last_name            = $request->last_name;
                    $data->email                = $request->email;
                    $data->agent_type           = $request->agencies_type;
                    $data->country_id           = $request->country_id;
                    $data->city_id              = $request->city_id;
                    $data->municipality_id      = $request->municipality_id;
                    $data->neighborhood_id      = $request->neighborhood_id;
                    $data->desc_address         = $request->desc_address;
                    $data->latitude             = $request->latitude;
                    $data->longitude            = $request->longitude;
                    $data->iban                 = $request->iban;
                    $data->iban_name            = $request->iban_name;
                    $data->iban_type            = $request->iban_type;
                    $data->phone_number         = $request->phone_number;
                    $data->status               = $request->status;
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
        }else {
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.validation_error.title'),
                'description' => __('cms::base.msg.validation_error.description'),
                'errors'      => $validator->getMessageBag()->toArray()
            ], 402);
        }
        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('cms::base.msg.success_message.title'),
            'description' => __('cms::base.msg.success_message.description'),
            'redirect_url'  => route('cms::agencies', ['agencies_type' => $request->agencies_type])
        ], 200);
    }
    public function show(Request $request, $id)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }
        $agencies_type  = $this->OfType($request->agencies_type);
        $countries      = $this->getCountry(app()->getLocale());
        $users          = User::where('type', 'AGENCIES')->get();
        $agencies       = MasterAgencies::get();
        if($agencies_type == 'master_agent'){
            $data = MasterAgencies::find($id);
        }else if($agencies_type == 'sub_agent'){
            $data = SubAgencies::find($id);
        }
        return view('cms::backend.agencies.update', [
            'agencies_type' => $agencies_type,
            'countries'     => $countries,
            'users'         => $users,
            'data'          => $data,
            'agencies'      => $agencies
        ]);
    }
    public function update(Request $request)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }

        $userValidator = [
            'first_name'         => 'required|string|max:255',
            'last_name'          => 'required|string|max:255',
            // 'email'              => 'required|email|unique:master_agencies,id'.$request->email,
            'title'              => 'nullable',
            'description'        => 'nullable',
            'agencies_type'      => 'required|in:master_agent,sub_agent',
            'user_id'            => 'required_if:agencies_type,=,master_agent|exists:users,id',
            'master_agent_id'    => 'required_if:agencies_type,=,sub_agent|exists:master_agencies,id',
        ];

        $validator = Validator::make($request->all(), $userValidator);
        if(!$validator->fails())
        {
            try{
                DB::transaction(function() use ($request) {
                    if($request->agencies_type == 'master_agent'){
                        $data                   =  MasterAgencies::find($request->agen_id);
                        $data->user_id          = $request->user_id;
                        // $data->has_sub_agent    = '0';
                        // $data->sub_agent_count  = '0';
                    } else if($request->agencies_type == 'sub_agent') {
                        $data                   = SubAgencies::find($request->agen_id);
                        $data->master_agent_id  = $request->master_agent_id;
                    }

                    foreach (\LaravelLocalization::getSupportedLanguagesKeys() as $loc) {
                        $data->{'title:' . $loc} = "{$request->title}";
                        $data->{'description:' . $loc} = "{$request->description}";
                    }

                    $data->first_name           = $request->first_name;
                    $data->last_name            = $request->last_name;
                    $data->email                = $request->email;
                    $data->agent_type           = $request->agencies_type;
                    $data->country_id           = $request->country_id;
                    $data->city_id              = $request->city_id;
                    $data->municipality_id      = $request->municipality_id;
                    $data->neighborhood_id      = $request->neighborhood_id;
                    $data->desc_address         = $request->desc_address;
                    $data->latitude             = $request->latitude;
                    $data->longitude            = $request->longitude;
                    $data->iban                 = $request->iban;
                    $data->iban_name            = $request->iban_name;
                    $data->iban_type            = $request->iban_type;
                    $data->phone_number         = $request->phone_number;
                    $data->status               = $request->status;
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
        }else {
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.validation_error.title'),
                'description' => __('cms::base.msg.validation_error.description'),
                'errors'      => $validator->getMessageBag()->toArray()
            ], 402);
        }
        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('cms::base.msg.success_message.title'),
            'description' => __('cms::base.msg.success_message.description'),
            'redirect_url'  => route('cms::agencies', ['agencies_type' => $request->agencies_type])
        ], 200);

    }
    public function softDelete(Request $request, $id)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }

        if($request->agencies_type == 'master_agent'){
            $agent = MasterAgencies::withTrashed()->find($id);
        }else if($request->agencies_type == 'sub_agent'){
            $agent = SubAgencies::withTrashed()->find($id);
        }

        if(!is_null($agent)){
            $agent->delete();
        } else {
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.error_message.title'),
                'description' => __('cms::base.msg.error_message.description'),
            ], 500);
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('cms::base.msg.success_message.title'),
            'description' => __('cms::base.msg.success_message.description'),
        ], 200);
    }
    public function delete(Request $request, $id)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }

        if($request->agencies_type == 'master_agent'){
            $agent = MasterAgencies::withTrashed()->find($id);
        }else if($request->agencies_type == 'sub_agent'){
            $agent = SubAgencies::withTrashed()->find($id);
        }

        if(!is_null($agent)){
            $agent->forceDelete();
        } else {
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.error_message.title'),
                'description' => __('cms::base.msg.error_message.description'),
            ], 500);
        }

        return response()->json([
            'success'     => true,
            'type'        => 'success',
            'title'       => __('cms::base.msg.success_message.title'),
            'description' => __('cms::base.msg.success_message.description'),
        ], 200);
    }
    public function restore(Request $request, $id)
    {
        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            return response()->json([
                'success'     => false,
                'type'        => 'permission_denied',
                'title'       => __('cms::base.permission_denied.title'),
                'description' => __('cms::base.permission_denied.description'),
            ], 402);
        }

        if($request->agencies_type == 'master_agent'){
            $agent = MasterAgencies::withTrashed()->find($id);
        }else if($request->agencies_type == 'sub_agent'){
            $agent = SubAgencies::withTrashed()->find($id);
        }

        if(!is_null($agent)){
            $agent->restore();
        } else {
            return response()->json([
                'success'     => false,
                'type'        => 'error',
                'title'       => __('cms::base.msg.error_message.title'),
                'description' => __('cms::base.msg.error_message.description'),
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
