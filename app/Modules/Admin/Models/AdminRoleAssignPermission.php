<?php

namespace App\Modules\Admin\Models;

use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Admin\Validators\AdminRole as Validator;

class AdminRoleAssignPermission extends Model implements HasValidations
{
  use ModelDefaults;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_role_permission';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'role_id',
    'module_id',
    'view_access',
    'add_access',
    'update_access',
    'delete_access',
    'status_access',
  ];

  /**
   * All related admins.
   *
   * @return BelongsTo
   */
  public function role ()
  {
    return $this -> belongsTo(AdminRole::class, 'role_id');
  }
  /**
   * All related admins.
   *
   * @return BelongsTo
   */
  public function module ()
  {
    return $this -> belongsTo(AdminPermissionModule::class, 'module_id');
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
