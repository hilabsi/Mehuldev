<?php

namespace App\Modules\Partner\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerDocument extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_partner_documents';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'partner_id',
    'document_id',
    'file',
  ];

  public function partner()
  {
    return $this->belongsTo(Partner::class, 'partner_id');
  }
}
