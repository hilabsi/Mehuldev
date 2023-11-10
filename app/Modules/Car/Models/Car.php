<?php

namespace App\Modules\Car\Models;

use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Models\DriverDocument;
use App\Modules\Settings\Models\CarBrand;
use App\Modules\Settings\Models\CarModel;
use App\Support\Traits\UsesUUID;
use App\Modules\Partner\Models\Partner;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Car\Validators\Car as Validator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Car extends Model implements HasValidations
{
  use UsesUUID;
  use SpatialTrait;

  /**
   * Indicates if the IDs are auto-incrementing.
   *
   * @var bool
   */
  public $incrementing = false;

  protected $spatialFields = ['location'];

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_cars';

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'id' => 'string'
  ];

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'model_id',
    'lpn',
    'color',
    'partner_id',
    'year',
    'brand_id',
    'type',
    'status',
    'categories',
    'is_verified',
    'driver_id',
    'current_session',
    'location',
  ];

  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }

  /**
   * @return BelongsTo
   */
  public function partner() {
    return $this->belongsTo(Partner::class, 'partner_id');
  }

  /**
   * @return BelongsToMany
   */
  public function categories()
  {
    return $this->belongsToMany(CarCategory::class, 'r_car_category', 'car_id', 'category_id');
  }

  public function setCategoriesAttribute($categories)
  {
    if ($categories && is_array($categories))
      $this->categories()->sync($categories);
    else if (is_string($categories)) $this->categories()->sync(explode(',', $categories));

  }

  public function isBusy()
  {
    return !!$this->driver_id;
  }

  public function sessions()
  {
    return $this->hasMany(CarSession::class, 'car_id');
  }

  public function brand()
  {
    return $this->belongsTo(CarBrand::class,'brand_id');
  }

  public function model()
  {
    return $this->belongsTo(CarModel::class, 'model_id');
  }

  public function getDocument($key)
  {
    $document = $this->documents()->orderBy('created_at', 'desc')->where('document_id', $key)->first();

    if($document)
      return s3($document->file);
    return null;
  }

  public function setDocuments(array $documents)
  {
    foreach ($documents as $key => $document) {
      if ($document) {
        $d = uploader($document, 'cars', $this -> attributes['id']);

        $this->documents()->create(['file' => $d, 'document_id' => $key]);
      }
    }
  }

  public function documents()
  {
    return $this->hasMany(CarDocument::class, 'car_id');
  }

  /**
   * Gets model's operations' validation rules.
   *
   * @return ValidateModel
   */
  public static function validations (): ValidateModel
  {
    return new Validator();
  }
}
