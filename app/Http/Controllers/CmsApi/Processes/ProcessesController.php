<?php

namespace App\Http\Controllers\CmsApi\Processes;

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
use App\Helpers\Transactions;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Models\Entities\Operations;
use App\Models\Entities\RelationsType;
use App\Models\Entities\OperationType;
use App\Models\Entities\Categories;

class ProcessesController extends Controller
{
    use Helper, DataLists, Transactions;

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }
    public function makeProcesses(Request $request, $relation,  $process_type)
    {
        $rela =  Operations::with(['relation'])->whereHas('relation', function($query) use ($relation) {
                    return $query->where('type', $relation);
                })->where('type_en', $process_type)->exists();

        if($rela){
            return true;
        }else{
            return false;
        }

    }
}
