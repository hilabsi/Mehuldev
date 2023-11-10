<?php

namespace App\Modules\User\Jobs;

use Exception;
use App\Jobs\Job;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\User\Models\UserPasswordReset;

class CheckResetPasswordCodes extends Job
{
  /**
   * Execute the job.
   *
   * @return void
   * @throws Exception
   */
  public function handle ()
  {
    foreach (UserPasswordReset ::all() as $code) {
      DB ::beginTransaction();

      try {

        if ($code->created_at->diffInMinutes(Carbon::now()) === settings('user_reset_password_timeout_in_minutes')) {
          $code->delete();
        }

        DB ::commit();

      } catch (Exception $exception) {
        DB ::rollBack();
      }

    }
  }
}
