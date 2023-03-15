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
use App\Helpers\Helper;

trait DataLists {

    use Helper;

    public function getUserData($user, $collection = false)
    {
        $Return = [];
        if(!empty($user))
        {
            if(!$collection)
            {
                $Return['id']                 = $user->id;
                $Return['type']               = $user->type;
                $Return['name']               = $user->name;
                $Return['username']           = $user->username;
                $Return['phone_number']       = $user->phone_number;
                $Return['email']              = $user->email;
                $Return['image']              = !empty($user->image) ? $this->getImgByFileName('users', $user->image) :  $this->getImageDefaultByType('user') ;
                $Return['verification_code']  = $user->verification_code;
                $Return['status']             = $user->status;
                $Return['country_id']         = $user->country_id;
                $Return['city_id']            = $user->city_id;
                $Return['municipality_id']    = $user->municipality_id;
                $Return['neighborhood_id']    = $user->neighborhood_id;
                $Return['registration_type']  = $user->registration_type;
                $Return['social_id']          = $user->social_id;
                $Return['deleted_at']         = $user->deleted_at;
                $Return['created_at']         = $user->created_at;
                $Return['updated_at']         = $user->updated_at;
            } else {
                foreach($user as $key => $use)
                {
                    $Return[$key]['id']                 = $use->id;
                    $Return[$key]['type']               = $use->type;
                    $Return[$key]['name']               = $use->name;
                    $Return[$key]['username']           = $use->username;
                    $Return[$key]['phone_number']       = $use->phone_number;
                    $Return[$key]['email']              = $use->email;
                    $Return[$key]['image']              = !empty($use->image) ? $this->getImgByFileName('users', $use->image) :  $this->getImageDefaultByType('user') ;
                    $Return[$key]['verification_code']  = $use->verification_code;
                    $Return[$key]['status']             = $use->status;
                    $Return[$key]['country_id']         = $use->country_id;
                    $Return[$key]['city_id']            = $use->city_id;
                    $Return[$key]['municipality_id']    = $use->municipality_id;
                    $Return[$key]['neighborhood_id']    = $use->neighborhood_id;
                    $Return[$key]['registration_type']  = $use->registration_type;
                    $Return[$key]['social_id']          = $use->social_id;
                    $Return[$key]['deleted_at']         = $use->deleted_at;
                    $Return[$key]['created_at']         = $use->created_at;
                    $Return[$key]['updated_at']         = $use->updated_at;
                }
            }
        }
        return $Return;
    }
}