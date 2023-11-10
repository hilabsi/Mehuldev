<?php

namespace App\Modules\User\Observers;

use App\Modules\User\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  User  $user
     *
     * @return void
     */
    public function created(User $user)
    {
        $user->createAsStripeCustomer();
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  User  $user
     *
     * @return void
     */
    public function updated(User $user)
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  User  $user
     *
     * @return void
     */
    public function deleted(User $user)
    {
        //
    }

    /**
     * Handle the User "forceDeleted" event.
     *
     * @param  User  $user
     *
     * @return void
     */
    public function forceDeleted(User $user)
    {
        //
    }
}
