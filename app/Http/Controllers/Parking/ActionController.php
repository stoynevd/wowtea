<?php

namespace App\Http\Controllers\Parking;

use App\Http\Controllers\Controller;
use App\Parking;
use App\Services\ParkingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActionController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFreeSpaces() {
        return response()->json(['free_parking_spaces' => ParkingService::getFreeParkingSpaces()]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enterParking(Request $request) {
        $validator = Validator::make($request->all(), [
           'car_reg_number' => 'bail|required|max:10',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid data',
                'success' => false
            ]);
        }

        return response()->json(ParkingService::enterParking($request->all()));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function exitParking(Request $request) {
        $validator = Validator::make($request->all(), [
            'car_reg_number' => 'bail|required|max:10',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid data',
                'success' => false
            ]);
        }

        return response()->json(ParkingService::exitParking($request->all()));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkCost(Request $request) {
        $validator = Validator::make($request->all(), [
            'car_reg_number' => 'bail|required|max:10',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid data',
                'success' => false
            ]);
        }

        if (!$parkingSpot = Parking::where('car_reg_number', $request->car_reg_number)->first()) {
            return response()->json([
                'message' => 'Car not in the parking',
                'success' => false
            ]);
        }

        return response()->json(['due_amount' => ParkingService::getParkingCost($parkingSpot->entry_date)]);
    }

}
