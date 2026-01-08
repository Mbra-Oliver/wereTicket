<?php

namespace App\Http\Helpers;

class GeoSearch
{
  public static function getCoordinates($address, $apiKey)
  {
    $encodedAddress = urlencode($address);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$encodedAddress}&key={$apiKey}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($data['status'] === 'OK') {
      $location = $data['results'][0]['geometry']['location'];
      return [
        'lat' => $location['lat'],
        'lng' => $location['lng']
      ];
    } else {
      return [
        'error' => $data['status']
      ];
    }
  }

  public static function getDistance($lat1, $lon1, $lat2, $lon2)
  {
    $lat1 = floatval($lat1);
    $lon1 = floatval($lon1);
    $lat2 = floatval($lat2);
    $lon2 = floatval($lon2);

    $earthRadius = 6371000; // meters

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
      cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
      sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    $distance = $earthRadius * $c;
    return floatval($distance); // in meters
  }
}
