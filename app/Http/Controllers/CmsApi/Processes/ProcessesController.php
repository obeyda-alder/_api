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

class ProcessesController extends Controller
{
    use Helper, DataLists, Transactions;

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }
    public function makeProcesses(Request $request, $process_type)
    {
        return $process_type;
    }
}
