<?php

namespace App\Modules\Campaign\Models;

use App\Modules\Country\Models\Country;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Modules\Language\Models\Language;
use App\Support\Contracts\HasValidations;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Campaign\Validators\Campaign as Validator;

class Campaign extends Model implements HasValidations
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_campaigns';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'title',
    'text_title',
    'country_id',
    'mail_subject',
    'text_message',
    'mail_message',
    'trips_activated',
    'trips_count',
    'trips_status',
    'trips_comparing',
    'user_status',
    'language',
    'has_business',
    'business_status',
    'use_sms',
    'use_push',
    'use_mail',
    'usage',
  ];

  /**
   * @return BelongsTo
   */
  public function country(): BelongsTo
  {
    return $this->belongsTo(Country::class, 'country_id');
  }

  /**
   * @return BelongsTo
   */
  public function language(): BelongsTo
  {
    return $this->belongsTo(Language::class, 'language_id');
  }

  /**
   * @return HasMany
   */
  public function usage(): HasMany
  {
    return $this->hasMany(CampaignUsage::class, 'campaign_id');
  }

  /**
   * Gets model's operations' validation roles.
   *
   * @return ValidateModel
   */
  public static function validations (): ValidateModel
  {
    return new Validator();
  }
}
