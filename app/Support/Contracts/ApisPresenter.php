<?php

namespace App\Support\Contracts;

use Illuminate\Database\Eloquent\Collection;

abstract class ApisPresenter
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * ApiPresenter constructor.
     * @param  Collection|null  $collection
     */
    public function __construct(Collection $collection = null) {
        $this->collection = $collection;
    }

    /**
     * Base representation of collection.
     *
     * @return array
     */
    public function present():array {
        return [];
    }
}
