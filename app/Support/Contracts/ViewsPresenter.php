<?php

namespace App\Support\Contracts;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class ViewsPresenter
{
    /**
     * Model's routing namespace
     *
     * @var string
     */
    protected $namespace;

    /**
     * @var array
     */
    protected $params;

    /**
     * ViewsPresenter constructor.
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->params       = $params;
    }

    /**
     * Base route.
     *
     * @param $view
     * @param array $params
     * @return Factory|View
     */
    protected function view($view, $params = []) {
        return view("{$this->namespace}.{$view}", $params);
    }

    /**
     * show a model instance.
     *
     * @return View
     */
    public function show():View
    {
        return $this->view("show", $this->params);
    }

    /**
     * Edit a model instance.
     *
     * @return View
     */
    public function edit():View
    {
        return $this->view("edit", $this->params);
    }

    /**
     * Create a model instance.
     *
     * @return View
     */
    public function create():View
    {
        return $this->view("create", $this->params);
    }

    /**
     * View all model instance.
     *
     * @return View
     */
    public function index():View
    {
        return $this->view("index", $this->params);
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
