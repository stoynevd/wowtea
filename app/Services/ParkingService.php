<?php

namespace App\Services;

use App\Parking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\If_;

class ParkingService
{
    /**
     * Max capacity of the parking
     */
    const MAX_CAPACITY = 200;

    /**
     * This constant contains the information for the parking
     * start represents the start of the day rate and at the same time the end of the night rate
     * end represents the end of the day rate and at the same time the beginning of the night rate
     * day_rate represents the cost per hour for the day hours spent on the parking
     * night_rate represents the cost per hour for the night hours spent on the parking
     */
    const RATE = [
      'start'       => '08:00',
      'end'         => '18:00',
      'day_rate'    => 3,
      'night_rate'  => 2,
    ];

    /**
     * This function returns the number of free parking spaces
     * @return int
     */
    public static function getFreeParkingSpaces() {
        return self::MAX_CAPACITY - Parking::all()->count();
    }

    /**
     * This function is used to register a new vehicle in the parking
     * @param $data
     * @return array
     */
    public static function enterParking($data) {
        try {
            if (self::getFreeParkingSpaces() == 0) {
                return [
                    'message' => 'The parking is full',
                    'success' => false
                ];
            }

            if (Parking::where('car_reg_number', $data['car_reg_number'])->first()) {
                return [
                    'message' => 'The car is already in the parking',
                    'success' => false
                ];
            }

            $newCar = Parking::create([
                'car_reg_number' => $data['car_reg_number'],
                'entry_date'     => Carbon::now()
            ]);

            return [
                'message' => 'Car entered the parking successfully',
                'success' => true,
                'car_info' => $newCar
            ];
        } catch (\Exception $e) {
            \Log::error(__CLASS__ . '-->' . __FUNCTION__ . ': ' . $e->getMessage());
            return [
                'message' => 'Something went wrong, please try again.',
                'success' => false
            ];
        }
    }

    /**
     * This function is used to deregister a car from the parking
     * @param $data
     * @return array
     */
    public static function exitParking($data) {
        try {
            $parkingSpot = Parking::where('car_reg_number', $data['car_reg_number'])->first();
            if (!$parkingSpot) {
                return [
                    'message' => 'The car is not in the parking',
                    'success' => false
                ];
            }

            $parkingSpot->update([
                'exit_date'    => Carbon::now(),
                'payed_amount' => self::getParkingCost($parkingSpot->entry_date)
            ]);
            $message = [
                'message' => 'The car successfully exited the parking',
                'success' => true,
                'car'     => $parkingSpot
            ];
            $parkingSpot->delete();
            return $message;
        } catch (\Exception $e) {
            \Log::error(__CLASS__ . '-->' . __FUNCTION__ . ': ' . $e->getMessage());
            return [
                'message' => 'Something went wrong, please try again.',
                'success' => false
            ];
        }
    }

    /**
     * This function returns the due amount for the current period of time spent at the parking,
     * by a specified vehicle
     * @param $entry_date
     * @return float|int
     */
    public static function getParkingCost($entry_date) {
        $time_result = self::getTimeForBothRates($entry_date);

        return self::RATE['day_rate'] * (isset($time_result['day_hours']) ? $time_result['day_hours'] : 0)
            + self::RATE['night_rate'] * (isset($time_result['night_hours']) ? $time_result['night_hours'] : 0);
    }

    /**
     * This function returns the number of hours spent at the parking
     * day_hours -> the number of hours which fall under the day_rate of the parking
     * night_hours -> the number of hours which fall under the night_rate of the parking
     * @param $entry_date
     * @return array
     */
    public static function getTimeForBothRates($entry_date) {
        try {
            $exit_date = Carbon::now();
            $entry = Carbon::parse($entry_date);
            $start = Carbon::createFromTimeString(self::RATE['start']);
            $end = Carbon::createFromTimeString(self::RATE['end']);
            $result = [];

            if ($exit_date->between($start, $end)) {
                if ($entry->between($start, $end)) {
                    if ($entry->format('H') > $end) {
                        $result['day_hours'] = $end->format('H') - $entry->format('H')
                            + $exit_date->diffInHours($start);
                        $result['night_hours'] = $end->diffInHours($start->addDay());
                    } else {
                        $result['day_hours'] = $exit_date->format('H') - $entry->format('H');
                    }
                } else {
                    if ($entry->format('H') > $end) {
                        $result['night_hours'] = $entry->diffInHours($start->addDay());
                        $result['day_hours'] = $exit_date->format('H') - $start->format('H');
                    } else {
                        $result['night_hours'] = $entry->diffInHours($start);
                        $result['day_hours'] = $exit_date->format('H') - $start->format('H');
                    }
                }
            } else {
                if ($entry->between($start, $end)) {
                    if ($exit_date->format('H') > $end) {
                        $result['night_hours'] = $end->diffInHours($exit_date);
                        $result['day_hours'] = $entry->diffInHours($end);
                    } else {
                        $result['night_hours'] = $end->diffInHours($exit_date->addDay());
                        $result['day_hours'] = $entry->diffInHours($end);
                    }
                } else {
                    if ($exit_date->format('H') > $end) {
                        if ($exit_date->format('H') > $entry->format('H')) {
                            $result['night_hours'] = $exit_date->format('H') - $entry->format('H');
                        } else {
                            $result['night_hours'] = $entry->diffInHours($start->addDay())
                                + $end->diffInHours($exit_date);
                            $result['day_hours'] = $entry->diffInHours($end);
                        }
                    } else {
                        if ($exit_date->format('H') > $end) {
                            $result['night_hours'] = $exit_date->format('H') - $entry->format('H');
                        } else {
                            $result['night_hours'] = $entry->diffInHours($start)
                                + $end->diffInHours($exit_date->addDay());
                            $result['day_hours'] = $entry->diffInHours($end);
                        }
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error(__CLASS__ . '-->' . __FUNCTION__ . ': ' . $e->getMessage());
            exit();
        }
    }

}
