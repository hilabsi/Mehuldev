<?php

namespace App\Support\Contracts;

use Illuminate\Database\Eloquent\Model;

class UrlPresenter
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * Model's routing namespace
     *
     * @var string
     */
    protected $namespace;

    /**
     * UrlPresenter constructor.
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Base route.
     *
     * @param $route
     * @param array $params
     * @return string
     */
    protected function route($route, $params = []) {
        return route("{$this->namespace}.{$route}", $params);
    }

    /**
     * show a model instance.
     *
     * @return string
     */
    public function show():string
    {
        return $this->route("show", ['id' => $this->model->id]);
    }

    /**
     * Edit a model instance.
     *
     * @return string
     */
    public function edit():string
    {
        return $this->route("edit", ['id' => $this->model->id]);
    }

    /**
     * Update a model instance.
     *
     * @return string
     */
    public function update():string
    {
        return $this->route("update", ['id' => $this->model->id]);
    }

    /**
     * Delete a model instance.
     *
     * @return string
     */
    public function destroy():string
    {
        return $this->route("destroy", ['id' => $this->model->id]);
    }

    /**
     * Get functions via attribute calls.
     *
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        if(method_exists($this, $key))
            return $this->$key();
        return $this->$key;
    }
}
