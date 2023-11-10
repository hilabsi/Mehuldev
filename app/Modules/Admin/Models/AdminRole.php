<?php

namespace App\Modules\Admin\Models;

use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Admin\Validators\AdminRole as Validator;

class AdminRole extends Model implements HasValidations
{
  use ModelDefaults;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_admin_roles';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name',
  ];

  /**
   * All related admins.
   *
   * @return BelongsTo
   */
  public function rolepermission()
  {
    return $this->hasMany(AdminRoleAssignPermission::class, 'role_id');
  }
  
  public function admins ()
  {
    return $this -> belongsTo(Admin::class, 'role_id');
  }
  
  public function getUserno()
  {
    return Admin::where('role_id',$this->id)->count();
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
