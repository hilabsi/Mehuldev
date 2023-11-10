<?php

namespace App\Modules\Driver\ApiPresenters;

use App\Modules\Trip\Models\Trip;
use App\Modules\Trip\Models\TripRequest;
use App\Support\Contracts\ApisPresenter;
use Carbon\Carbon;

class DriverPresenter extends ApisPresenter
{
  /**
   * Base representation of collection.
   *
   * @return array
   */
  public function present (): array
  {
    return $this -> collection -> map(function ($item) {
      return $this -> item($item);
    }) -> toArray();
  }

  public function item ($item)
  {
    $stats = TripRequest::whereDriverId($item->id)->whereBetween('created_at', [Carbon::now()->addDays(-1), Carbon::now()])->get();
    $ostats = TripRequest::whereDriverId($item->id)->whereStatus('accepted')->get();
    return [
      'id'          => $item -> id,
      'status'      => $item -> status,
      'picture'      => $item -> picture,
      'gender'      => $item -> gender,
      'first_name'  => $item -> first_name,
      'last_name'   => $item -> last_name,
      'fcmable'     => $item->hasDeviceId(),
      'partner'     => [
        'id'            => $item -> partner ->id,
        'company_name'  => $item -> partner ->company_name,
      ],
      'current_car' => $item->car ? [
        'brand' => $item->car->brand->title,
        'model' => $item->car->model->title,
        'year'  => $item->car->year,
        'color'  => $item->car->color,
        'lpn' => $item->car->lpn,
        'lat' => $item->car->location->getLat(),
        'lng' => $item->car->location->getLng(),
      ]: null,
      'documents'       => $item->documents->map(function ($document) {
        return [
          'document_id' => $document->document_id,
          'file'  => s3($document->file),
        ];
      }),
      'country' => [
        'id'  => $item->country->id,
        'name'=> $item->country->name,
      ],
      'city' => [
        'id'  => $item->city->id,
        'name'=> $item->city->name,
      ],
      'id_type'     => $item->id_type,
      'id_number'   => $item->id_number,
      'birthday'    => $item-> birthday,
      'phone'       => $item-> phone,
      'email'       => $item-> email,
      'current_trip'=> $item-> current_trip,
      'active_24'   => 0,
      'rate'        => $item->avgRating(),
      'trips_no'    => Trip::whereDriverId($item->id)->whereStatus('completed')->count(),
      'license_type'=> [
        'id'    => $item->licenseType->id,
        'name' => $item->licenseType->name,
      ],
      'profile' => $item->getDocument('profile'),
      'id_image' => $item->getDocument('id_image'),
      'license_front' => $item->getDocument('license_front'),
      'license_back' => $item->getDocument('license_back'),
      'trips_stats' => [
        'total'   => $stats->count(),
        'accepted'=> $stats->filter(function ($item) {return $item->status === 'accepted';})->count(),
        'completed'=> $stats->filter(function ($item) {return Trip::find($item->trip_id)->status === 'completed';})->count(),
        'cancelled'=> $stats->filter(function ($item) {return Trip::find($item->trip_id)->status === 'cancelled';})->count(),
        'rejected'=> $stats->filter(function ($item) {return $item->status === 'rejected';})->count(),
        'ignored'=> $stats->filter(function ($item) {return $item->status === 'ignored';})->count(),
      ],
      'license_number'=>$item->license_number,
      'is_online'     => $item-> is_online,
      'is_verified'   => !!$item-> is_verified,
      'name'          => $item-> name,
      'created_at'    => $item -> created_at,
    ];
  }
}
