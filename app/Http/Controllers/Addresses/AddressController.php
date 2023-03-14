<?php

namespace App\Http\Controllers\Addresses;

use Illuminate\Routing\Controller as BaseController;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Validator;

class AddressController extends BaseController
{
    use Helper;

    protected $locale = 'ar';

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['']]);
        $this->locale = app()->getLocale();
    }
    public function getCountries(Request $request)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        return $this->getCountry($request, $this->locale);
    }
    public function getCities(Request $request)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                 = [];
            $resulte['success']      = false;
            $resulte['type']         = 'permission_denied';
            $resulte['title']        = __('cms::base.permission_denied.title');
            $resulte['description']  = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

       return  $this->getCitiy($request, $this->locale);
    }
    public function getMunicipalites(Request $request)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                = [];
            $resulte['success']     = false;
            $resulte['type']        = 'permission_denied';
            $resulte['title']       = __('cms::base.permission_denied.title');
            $resulte['description'] = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        return $this->getMunicipality($request, $this->locale);
    }
    public function getNeighborhoodes(Request $request)
    {
        $this->locale = $request->hasHeader('locale') ? $request->header('locale') : app()->getLocale();

        if(!in_array(auth()->user()->type, ["ROOT", "ADMIN"]))
        {
            $resulte                = [];
            $resulte['success']     = false;
            $resulte['type']        = 'permission_denied';
            $resulte['title']       = __('cms::base.permission_denied.title');
            $resulte['description'] = __('cms::base.permission_denied.description');
             return response()->json($resulte, 400);
        }

        return $this->getNeighborhood($request, $this->locale);
    }
}
