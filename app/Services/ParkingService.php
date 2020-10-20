<?php

namespace App\Services;

use App\Parking;
use Carbon\Carbon;

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
      'start'          => '08:00',
      'end'            => '18:00',
      'day_rate'       => 3,
      'night_rate'     => 2,
      'full_days_rate' => 58
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
     * @param $data -> contains the information for the new car that wants to access the parking
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
            //Soft delete is used for the Parking model as we would like to check the records
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
            + self::RATE['night_rate'] * (isset($time_result['night_hours']) ? $time_result['night_hours'] : 0)
            + self::RATE['full_days_rate'] * ($time_result['full_days']);
    }

    /**
     * This function returns the number of hours spent at the parking
     * day_hours -> the number of hours which fall under the day_rate of the parking
     * night_hours -> the number of hours which fall under the night_rate of the parking
     * full_days -> the number of full days spent on the parking -> more than 24 hours;
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
                if ($entry->hour > $start->hour
                    && $entry->hour < $end->hour) {

                    if ($entry->hour > $exit_date->hour) {
                        $result['night_hours'] = $end->diffInHours($start->addDay());
                        $result['day_hours'] = $exit_date->hour - $start->hour;
                    } else {
                        $result['day_hours'] = $exit_date->hour - $entry->hour;
                    }

                } else {
                    $result['night_hours'] = (24 - $entry->hour) + $start->hour;
                    $result['day_hours'] = $exit_date->hour - $start->hour;
                }

            } else {

                if ($entry->hour > $start->hour
                    && $entry->hour < $end->hour) {
                    $result['night_hours'] = $exit_date->hour > $end->hour
                        ? $exit_date->hour - $end->hour
                        : (24 - $end->hour) + $exit_date->hour;
                    $result['day_hours'] = $end->hour - $entry->hour;
                } else {

                    if ($exit_date->hour > $end->hour) {

                        if ($entry->hour > $end->hour) {
                            if ($entry->hour > $exit_date->hour) {
                                $result['night_hours'] = (24 - $entry->hour)
                                    + $start->hour + ($exit_date->hour - $end->hour);
                                $result['day_hours'] = $end->hour - $start->hour;
                            } else {
                                $result['night_hours'] = $exit_date->hour - $entry->hour;
                            }
                        } else {
                            $result['night_hours'] = ($start->hour - $entry->hour)
                                + $exit_date->hour - $end->hour;
                            $result['day_hours'] = $end->hour - $start->hour;
                        }

                    } else {
                        if ($entry->hour > $exit_date->hour) {

                            if ($entry->hour > $end->hour) {
                                $result['night_hours'] = (24 - $entry->hour)
                                    + $exit_date->hour;
                            } else {
                                $result['night_hours'] = ($start->hour - $entry->hour)
                                    + (24 - $end->hour) + $exit_date->hour;
                                $result['day_hours'] = $end->hour - $start->hour;
                            }

                        } else {
                            $result['night_hours'] = $exit_date->hour - $entry->hour;
                        }
                    }
                }
            }

            $result['full_days'] = $entry->diffInDays($exit_date);

            return $result;
        } catch (\Exception $e) {
            \Log::error(__CLASS__ . '-->' . __FUNCTION__ . ': ' . $e->getMessage());
            exit();
        }
    }

}
