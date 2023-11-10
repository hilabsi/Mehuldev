<?php

namespace App\Support\Contracts;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

interface GenericCRUD
{
    /**
     * Show all models rows.
     */
    public function index();

    /**
     * Show model's create form
     */
    public function create();

    /**
     * Save model data.
     *
     * @param Request $request
     * @throws ValidationException
     */
    public function store(Request $request);

    /**
     * Show model's edit form
     *
     * @param Int $id
     */
    public function edit(Int $id);

    /**
     * Update model data.
     *
     * @param Int $id
     * @param Request $request
     * @throws ValidationException
     */
    public function update(Int $id, Request $request);

    /**
     * Delete model.
     *
     * @param $id
     */
    public function destroy(Int $id);
}
