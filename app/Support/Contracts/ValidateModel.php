<?php

namespace App\Support\Contracts;

/**
 * Interface ValidateModel
 * @description holds model's validation roles for edit/create operations.
 * @package App\Support\Validators
 */
interface ValidateModel
{
    /**
     * Validate model on edit operation.
     *
     * @param $id
     * @return array
     */
    public function edit($id):array;

    /**
     * Validate model on create operation.
     *
     * @return array
     */
    public function create():array;
}
