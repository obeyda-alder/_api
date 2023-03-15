<?php

namespace App\Helpers;
use App\Models\AddressDetails\City;
use App\Models\AddressDetails\Country;
use App\Models\AddressDetails\Municipality;
use App\Models\AddressDetails\Neighborhood;
use Illuminate\Support\Facades\Validator;
use Exception;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;

trait Helper {

    public function getCountry($request, $locale = 'ar')
    {
        $Country = Country::with('Cities')->orderBy('name_'.$locale, 'ASC');

        if($request->has('id')){
            $Country->where('id', $request->id);
        }

        return $Country->get();
    }
    public function getCitiy($request, $locale = 'ar')
    {
        $City =  City::with('country')->orderBy('name_'.$locale, 'ASC');

        if($request->has('country_id')){
            $City->where('country_id', $request->country_id);
        }

        return $City->get();
    }
    public function getMunicipality($request, $locale = 'ar')
    {
        $Municipality = Municipality::with('city')->orderBy('name_'.$locale, 'ASC');

        if($request->has('city_id')){
            $Municipality->where('city_id', $request->city_id);
        }

        return $Municipality->get();

    }
    public function getNeighborhood($request, $locale = 'ar')
    {
        if($request->has('municipality_id')){
            $municipality = Municipality::findOrFail($request->municipality_id);
            $neighborhoods = Neighborhood::where('neighborhood_municipality_key', $municipality->municipality_key)
                ->with('municipality')->orderBy('name_'.$locale, 'ASC');
        } else {
            $neighborhoods = Neighborhood::with('municipality')->orderBy('name_'.$locale, 'ASC');
        }

        return $neighborhoods->get();
    }
    public function getImageDefaultByType($type)
    {
        return asset('/uploads/default_images/'.$type.'.png');
    }
    public function UploadWithResizeImage($request, $folder_name, $size_img = [])
    {
        $image = $request->file('image');
        $extension = time().'.'.$image->getClientOriginalExtension();
        $destinationPath = public_path('/uploads/'.$folder_name.'/');
        // resize ...
        if(!empty($size_img)) {
            foreach($size_img as $key => $val)
            {
                $resize = Image::make($image);
                if (!file_exists($destinationPath.$val[0].'x'.$val[1].'/')) {
                    mkdir($destinationPath.$val[0].'x'.$val[1].'/', 0777, true);
                }
                $resize->resize($val[0], $val[1])->save($destinationPath.$val[0].'x'.$val[1].'/'.$extension);
            }
        }
        $image->move($destinationPath.'original/', $extension);
        return $extension;
    }
    public function getImgByFileName($folder, $imgName , $size = '/original/')
    {
        return asset('/uploads/'.$folder.$size.$imgName);
    }
    public function deleteImgByFileName($folder, $imgName , $size = '/original/')
    {
        if(File::exists(public_path('/uploads/'.$folder.$size.$imgName)))
        {
            File::delete(public_path('/uploads/'.$folder.$size.$imgName));
        }
    }
}
