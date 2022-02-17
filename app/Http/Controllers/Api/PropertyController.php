<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use App\Models\Property;

class PropertyController extends AppBaseController
{
    // Fetch Data from MLS_ID
    public function getDetails(Request $request)
    {
        $Ml_num = $request->mls_id;
        $msg = 'Property details fetched successfully.';
        $response = Property::with('images')->where(['Ml_num' => $Ml_num])->orderby('id', 'DESC')->get();
        return $this->sendResponse($msg, $response);
    }

    // Fetch Data
    public function filter(Request $request)
    {
        $data = $request->all();

        // vars
        $bedRoom = !empty($data['bedRoom']) ? (int) $data['bedRoom'] : '';
        $min_price = !empty($data['minPrice']) ? (int) $data['minPrice'] : '';
        $max_price = !empty($data['maxPrice']) ? (int) $data['maxPrice'] : '';
        $listedFor = !empty($data['listedFor']) ? $data['listedFor'] : '';
        $propType = !empty($data['propertyType']) ? $data['propertyType'] : '';
        $bath = !empty($data['bath']) ? (int) $data['bath'] : '';
        $openHouse = !empty($data['openHouse']) ? (bool) $data['openHouse'] : '';
        $addedFrom =  !empty($data['addedFrom']) ? $data['addedFrom'] : '';
        $key = !empty($data['key']) ? $data['key'] : '';
        $addr = !empty($data['addr']) ? $data['addr'] : '';

        $Sqft = !empty($data['sqft']) ? (int) $data['sqft'] : '';

        // Condition of Zoning and Type = $propType
        $propTypeKey = '';
        $propTypeValue = '';
        $propZoningKey = '';
        $propZoningValue = '';
        if ($propType) {
            $ty = explode("=", $propType);
            if ($ty[0] == 'type') {
                $propTypeKey = $ty[0];
                $propTypeValue = $ty[1];
            }
            if ($ty[0] == 'zonig') {
                $propZoningKey = $ty[0];
                $propZoningValue = $ty[1];
            }
        }

        $msg = 'Property fetched successfully.';

        // select * from `properties` where `Br` >= ? and `Lp_dol` >= ? and `Lp_dol` <= ? and `S_r` = ? and `property_type` LIKE ? and `Bath_tot` >= ? and `Patio_ter` not in (?, ?, ?) and `Idx_dt` <= ? and `Ad_text` LIKE ? and `Addr` LIKE ? or (`Ml_num` LIKE ?) or (`Municipality_district` LIKE ?) or (`Municipality` LIKE ?) or (`Community` LIKE ?) and `Sqft` >= ? order by `id` desc
        $response = Property::with('image')


            // Bed Rooms -- done
            ->when($bedRoom, function ($query) use ($data) {
                $br = (int) $data['bedRoom'];
                return $query->where('Br', '>=', $br);
            })

            // Min Price - done
            ->when($min_price, function ($query) use ($data) {
                $minp = (float) $data['minPrice'];
                return $query->where('Lp_dol', '>=', $minp);
            })

            // Max Price - Done
            ->when($max_price, function ($query) use ($data) {
                $mxp = (float) $data['maxPrice'];
                return $query->where('Lp_dol', '<=', $mxp);
            })

            // Listed for - Rent or sell - done
            ->when($listedFor, function ($query) use ($data) {
                $S_r = $data['listedFor'];
                return $query->where('S_r', $S_r);
            })

            // Property type - Done
            ->when($propTypeKey, function ($query) use ($propTypeValue) {
                return $query->where('property_type', 'LIKE', "%{$propTypeValue}%");
            })

            // Zoning type - Done
            ->when($propZoningKey, function ($query) use ($propZoningValue) {
                return $query->where('Zoning', 'LIKE', "%{$propZoningValue}%");
            })

            // bath - Done
            ->when($bath, function ($query) use ($data) {
                $bath = (int) $data['bath'];
                return $query->where('Bath_tot', '>=', $bath);
            })

            // openHouse - Done
            ->when($openHouse, function ($query) use ($data) {
                $openHouse_ = [NULL, 'None', ''];
                return $query->whereNotIn('Patio_ter', $openHouse_);
            })

            // addedFrom -- Idx_dt - Done
            ->when($addedFrom, function ($query) use ($data) {
                $addedFrom_ = $data['addedFrom'];
                return $query->where('Idx_dt', '<=', $addedFrom_);
            })

            // key - Done
            ->when($key, function ($query) use ($data) {
                $key_ = $data['key'];
                return $query->where('Ad_text', 'LIKE', "%{$key_}%");
            })

            // sqft - working
            ->when($Sqft, function ($query) use ($data) {
                $Sqft_ = (int) $data['sqft'];
                return $query->whereRaw('CAST(Sqft AS UNSIGNED) >= ' . $Sqft_);
            })

            // Addr - Done
            ->when($addr, function ($query) use ($data) {

                $addrr = $data['addr'];

                $id = Property::where('Addr', 'LIKE', "%{$addrr}%")
                    ->orWhere(function ($query) use ($addrr) {
                        $query->where('Ml_num', 'LIKE', "%{$addrr}%");
                    })
                    ->orWhere(function ($query) use ($addrr) {
                        $query->where('Municipality_district', 'LIKE', "{$addrr}%");
                    })
                    ->orWhere(function ($query) use ($addrr) {
                        $query->where('Municipality', 'LIKE', "{$addrr}%");
                    })
                    ->orWhere(function ($query) use ($addrr) {
                        $query->where('Community', 'LIKE', "{$addrr}%");
                    })
                    ->select('id')->get();

                return $query->whereIn('id', $id);
            })
            ->select(
                'id',
                'Ml_num',
                'Addr',
                'Ad_text',
                'S_r',
                'Lp_dol',
                'Rltr',
                'updated_at',
                'Bath_tot',
                'Br',
                'Br_plus'
            )
            ->orderby('id', 'DESC')
            // ->toSql();
            ->paginate('15')->withQueryString();

        // $response = $request->all();
        return $this->sendResponse($msg, $response);
    }

    public function type(Request $request)
    {
        $type = $request->type;
        $msg = 'Property details fetched successfully.';
        $response = Property::with('image')->where(['property_type' => $type])
            ->select(
                'id',
                'Ml_num',
                'Addr',
                'Ad_text',
                'S_r',
                'Lp_dol',
                'Rltr',
                'updated_at',
                'Bath_tot',
                'Br',
                'Br_plus'
            )->paginate('15')->withQueryString();
        return $this->sendResponse($msg, $response);
    }
}
