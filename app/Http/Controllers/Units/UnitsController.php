<?php

namespace App\Http\Controllers\Units;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CMS\Entities\Units;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Helpers\Helper;
use App\Models\User;
use Keygen\Keygen;
use Modules\CMS\Entities\Categories;

class UnitsController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['']]);
    }
    public function generateCode($length = 10)
    {
        $code = Keygen::numeric($length)->prefix('UN-')->generate();

        if(units::where('code', $code)->exists())
        {
            return $this->generateCode($length);
        }

        return $code;
    }
    public function getCategory(Request $request)
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

        return Categories::find($request->category_id);
    }
    public function index(Request $request)
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
        return view('cms::backend.units.index');
    }
    public function data(Request $request)
    {
        $data = Units::orderBy('id', 'DESC')->withTrashed();

        $data->get();
        return Datatables::of($data)
        ->filter(function($query) use ($request) {
            if(!is_null($request->search['value']))
            {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search['value']}%")
                    ->orWhere('code', 'like', "%{$request->search['value']}%")
                    ->orWhere('price', 'like', "%{$request->search['value']}%");
                });
            }
        })
        ->addIndexColumn()
        ->addColumn('name', function ($units) {
            return $units->name ?? '';
        })
        ->addColumn('code', function ($units) {
            return $units->code ?? '';
        })
        ->addColumn('from_to', function ($units) {
            return __('cms::base.units.from_to', ['from' => $units->from, 'to' => $units->to]) ?? '';
        })
        ->addColumn('price', function ($units) {
            return $units->price ?? '';
        })
        ->addColumn('status', function ($units) {
            return $units->status ?? '';
        })
        ->addColumn('actions', function($units) use($request){
            $actions   = [];
            if($units->trashed()) {
                $actions[] = [
                    'id'            => $units->id,
                    'label'         => __('cms::base.restore'),
                    'type'          => 'icon',
                    'link'          => route('cms::units::restore', ['id' => $units->id ]),
                    'method'        => 'POST',
                    'request_type'  => 'units_restore_'.$units->id,
                    'class'         => 'restore-action',
                    'icon'          => 'fas fa-trash-restore'
                ];
                $actions[] = [
                    'id'            => $units->id,
                    'label'         => __('cms::base.delete'),
                    'type'          => 'icon',
                    'link'          => route('cms::units::delete', ['id' => $units->id ]),
                    'method'        => 'POST',
                    'request_type'  => 'delete_'.$units->id,
                    'class'         => 'delete-action',
                    'icon'          => 'fas fa-user-times'
                ];
            } else {
                $actions[] = [
                    'id'            => $units->id,
                    'label'         => __('cms::base.soft_delete'),
                    'type'          => 'icon',
                    'link'          => route('cms::units::soft_delete', ['id' => $units->id ]),
                    'method'        => 'POST',
                    'request_type'  => 'soft_delete_'.$units->id,
                    'class'         => 'soft-delete-action',
                    'icon'          => 'fas fa-trash'
                ];
                $actions[] = [
                    'id'            => $units->id,
                    'label'         => __('cms::base.update'),
                    'type'          => 'icon',
                    'link'          => route('cms::units::edit', ['id' => $units->id ]),
                    'method'        => 'GET',
                    'request_type'  => 'update_'.$units->id,
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
        $users      = User::where('type', 'CUSTOMERS')->get();
        $categories = Categories::get();
        return view('cms::backend.units.create', ['users' => $users, 'categories' => $categories ]);
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
            'name'        => 'required_if:units_type,=,new|string|max:255',
            'code'        => 'required_if:units_type,=,new|string|max:255',
            'price'       => 'required_if:units_type,=,new|numeric',
            'status'      => 'required_if:units_type,=,new|in:ACTIVE,NOT_ACTIVE',
            'user_id'     => 'required|exists:users,id',
            'category_id' => 'required_if:units_type,=,from_categories', //exists:categories,id
        ];

        $validator = Validator::make($request->all(), $userValidator);
        if(!$validator->fails())
        {
            try{
                DB::transaction(function() use ($request) {
                    $data = new units;
                    if($request->units_type == 'new') {
                        $set = $request;
                    }else if($request->units_type == 'from_categories'){
                        $cat = Categories::find($request->category_id);
                        $set = $cat;
                        $data->category_id = $request->category_id;
                    }
                    $data->name    = $set->name;
                    $data->user_id = $request->user_id;
                    $data->code    = $set->code;
                    $data->price   = $set->price;
                    $data->status  = $set->status;
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
            'redirect_url'  => route('cms::units')
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
        $users      = User::where('type', 'CUSTOMERS')->get();
        $units      = units::find($id);
        $categories = Categories::get();
        return view('cms::backend.units.update', ['data' => $units, 'users' => $users , 'categories' => $categories ]);
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
            'name'        => 'required_if:units_type,=,new|string|max:255',
            'code'        => 'required_if:units_type,=,new|string|max:255',
            'price'       => 'required_if:units_type,=,new|numeric',
            'status'      => 'required_if:units_type,=,new|in:ACTIVE,NOT_ACTIVE',
            'user_id'     => 'required|exists:users,id',
            'category_id' => 'required_if:units_type,=,from_categories', //exists:categories,id
        ];

        $validator = Validator::make($request->all(), $userValidator);
        if(!$validator->fails())
        {
            try{
                DB::transaction(function() use ($request) {
                    $data = units::find($request->unit_id);
                    if($request->units_type == 'new') {
                        $set = $request;
                    }else if($request->units_type == 'from_categories'){
                        $cat = Categories::find($request->category_id);
                        $set = $cat;
                        $data->category_id = $request->category_id;
                    }
                    $data->name    = $set->name;
                    $data->user_id = $request->user_id;
                    $data->code    = $set->code;
                    $data->price   = $set->price;
                    $data->status  = $set->status;
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
            'redirect_url'  => route('cms::units')
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

        $units = units::withTrashed()->find($id);

        if(!is_null($units)){
            $units->delete();
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

        $units = units::withTrashed()->find($id);

        if(!is_null($units)){
            $units->forceDelete();
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

        $units = units::withTrashed()->find($id);

        if(!is_null($units)){
            $units->restore();
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
