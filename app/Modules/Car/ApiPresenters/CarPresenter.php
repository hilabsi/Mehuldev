<?php

namespace App\Modules\Car\ApiPresenters;

use App\Modules\Trip\Models\Trip;
use App\Modules\Trip\Models\TripRequest;
use App\Support\Contracts\ApisPresenter;
use Carbon\Carbon;

class CarPresenter extends ApisPresenter
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
    $stats = Trip::whereCarId($item->id)->whereBetween('created_at', [Carbon::now()->addDays(-1), Carbon::now()])->get();
    $ostats = Trip::whereCarId($item->id)->get();

    return [
      'id'          => $item -> id,
      'status'      => $item -> status,
      'partner'     => [
        'id'            => $item->partner->id,
        'company_name'  => $item->partner->company_name,
      ],
      'driver' => $item->driver ? [
        'id'        => $item->driver->id,
        'first_name'=> $item->driver->first_name,
        'last_name' => $item->driver->last_name,
        'id_number' => $item->driver->id_number,
        'email' => $item->driver->email,
        'phone' => $item->driver->getFullPhoneNumber(),
      ]: null,
      'trips_no'    => $ostats->count(),
      'is_busy'     => $item->isBusy(),
      'trips_stats' => [
        'total'   => $stats->count(),
        'completed'=> $stats->filter(function ($item) {return $item->status === 'completed';})->count(),
        'cancelled'=> $stats->filter(function ($item) {return $item->status === 'cancelled';})->count(),
      ],
      'is_verified'   => $item->is_verified,
      'brand_id'      => $item->brand_id,
      'brand'         => [
        'id' => $item->brand->id,
        'title' => $item->brand->title,
      ],
      'categories'    => $item->categories->map(function ($item) {
        return [
          'id'    => $item->id,
          'name'  => $item->name,
        ];
      }),
      'documents'       => $item->documents->map(function ($document) {
        return [
          'document_id' => $document->document_id,
          'file'  => s3($document->file),
        ];
      }),
      'categories_string' => implode(', ', $item->categories->map(function ($item) {
        return $item->name;
      })->toArray()),
      'category_front' => $item->getDocument('category_front'),
      'category_back' => $item->getDocument('category_back'),
      'location'      => ['lat' => optional($item->location)->getLat(), 'lng' => optional($item->location)->getLng()],
      'model_id'         => $item->model_id,
      'model'   => [
        'id'    => $item->model->id,
        'title' => $item->model->title,
      ],
      'type' => $item->type,
      'lpn'           => $item->lpn,
      'color'         => $item->color,
      'year'          => $item->year,
      'created_at'    => $item->created_at,
    ];
  }
}
