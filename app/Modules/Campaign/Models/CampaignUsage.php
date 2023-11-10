<?php

namespace App\Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignUsage extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_campaign_usages';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'campaign_id',
    'via',
    'users'
  ];

  /**
   * @return BelongsTo
   */
  public function campaign()
  {
    return $this->belongsTo(Campaign::class, 'campaign_id');
  }
}
