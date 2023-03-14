<?php

namespace App\Http\Controllers\Categories;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Entities\Categories;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Helpers\Helper;
use App\Models\User;
use Keygen\Keygen;

class CategoriesController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['']]);
    }
    public function generateCode($length = 10)
    {
        $code = Keygen::numeric($length)->prefix('CA-')->generate();

        if(Categories::where('code', $code)->exists())
        {
            return $this->generateCode($length);
        }

        return $code;
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
        return view('cms::backend.categories.index');
    }
    public function data(Request $request)
    {
        $data = Categories::orderBy('id', 'DESC')->withTrashed();

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
        ->addColumn('name', function ($category) {
            return $category->name ?? '';
        })
        ->addColumn('code', function ($category) {
            return $category->code ?? '';
        })
        ->addColumn('from_to', function ($category) {
            return __('cms::base.categories.from_to', ['from' => $category->from, 'to' => $category->to]) ?? '';
        })
        ->addColumn('price', function ($category) {
            return $category->price ?? '';
        })
        ->addColumn('status', function ($category) {
            return $category->status ?? '';
        })
        ->addColumn('actions', function($category) use($request){
            $actions   = [];
            if($category->trashed()) {
                $actions[] = [
                    'id'            => $category->id,
                    'label'         => __('cms::base.restore'),
                    'type'          => 'icon',
                    'link'          => route('cms::categories::restore', ['id' => $category->id ]),
                    'method'        => 'POST',
                    'request_type'  => 'category_restore_'.$category->id,
                    'class'         => 'restore-action',
                    'icon'          => 'fas fa-trash-restore'
                ];
                $actions[] = [
                    'id'            => $category->id,
                    'label'         => __('cms::base.delete'),
                    'type'          => 'icon',
                    'link'          => route('cms::categories::delete', ['id' => $category->id ]),
                    'method'        => 'POST',
                    'request_type'  => 'delete_'.$category->id,
                    'class'         => 'delete-action',
                    'icon'          => 'fas fa-user-times'
                ];
            } else {
                $actions[] = [
                    'id'            => $category->id,
                    'label'         => __('cms::base.soft_delete'),
                    'type'          => 'icon',
                    'link'          => route('cms::categories::soft_delete', ['id' => $category->id ]),
                    'method'        => 'POST',
                    'request_type'  => 'soft_delete_'.$category->id,
                    'class'         => 'soft-delete-action',
                    'icon'          => 'fas fa-trash'
                ];
                $actions[] = [
                    'id'            => $category->id,
                    'label'         => __('cms::base.update'),
                    'type'          => 'icon',
                    'link'          => route('cms::categories::edit', ['id' => $category->id ]),
                    'method'        => 'GET',
                    'request_type'  => 'update_'.$category->id,
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

        return view('cms::backend.categories.create');
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
            'name'    => 'required|string|max:255',
            'code'    => 'required|string|max:255',
            'from'    => 'required|numeric',
            'to'      => 'required|numeric',
            'price'   => 'required|numeric',
            'status'  => 'required|in:ACTIVE,NOT_ACTIVE',
        ];

        $validator = Validator::make($request->all(), $userValidator);
        if(!$validator->fails())
        {
            try{
                DB::transaction(function() use ($request) {
                    $data = new Categories;
                    $data->name    = $request->name;
                    $data->code    = $request->code;
                    $data->from    = $request->from;
                    $data->to      = $request->to;
                    $data->price   = $request->price;
                    $data->status  = $request->status;
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
            'redirect_url'  => route('cms::categories')
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
        $categories = Categories::find($id);
        return view('cms::backend.categories.update', ['categories' => $categories]);
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
            'name'    => 'required|string|max:255',
            'code'    => 'required|string|max:255',
            'from'    => 'required|numeric',
            'to'      => 'required|numeric',
            'price'   => 'required|numeric',
            'status'  => 'required|in:ACTIVE,NOT_ACTIVE',
        ];

        $validator = Validator::make($request->all(), $userValidator);
        if(!$validator->fails())
        {
            try{
                DB::transaction(function() use ($request) {
                    $data = Categories::find($request->cat_id);
                    $data->name    = $request->name;
                    $data->code    = $request->code;
                    $data->from    = $request->from;
                    $data->to      = $request->to;
                    $data->price   = $request->price;
                    $data->status  = $request->status;
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
            'redirect_url'  => route('cms::categories')
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

        $categories = Categories::withTrashed()->find($id);

        if(!is_null($categories)){
            $categories->delete();
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

        $categories = Categories::withTrashed()->find($id);

        if(!is_null($categories)){
            $categories->forceDelete();
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

        $categories = Categories::withTrashed()->find($id);

        if(!is_null($categories)){
            $categories->restore();
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
