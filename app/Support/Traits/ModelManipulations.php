<?php

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\Model;

trait ModelManipulations
{
    /**
     * @var Model
     */
    protected $modifiedModel;

    /**
     * Skip any forced scopes on model.
     *
     * @param array $scopes
     * @return mixed
     */
    public function ignoreGlobalScopes(Array $scopes)
    {
        $this->modifiedModel = $this->modifiedModel ? $this->modifiedModel->withoutGlobalScopes($scopes) : $this->model->withoutGlobalScopes($scopes);

        return $this;
    }

    /**
     * Check if model exists.
     *
     * @param String $key
     * @param $value
     * @return Model
     */
    public function shouldExists(String $key, $value): Model
    {

        if ($this->modifiedModel) {

            $modifiedModel = $this->modifiedModel;

            $this->modifiedModel = null;

            if (! ($model = $modifiedModel->where([ $key => $value ])->first())) abort(404);

        } else {

            if (! ($model = $this->model->where([ $key => $value ])->first())) abort(404);
        }

        return $model;
    }

    /**
     * Check if model exists.
     *
     * @param array $condition
     * @return Model
     */
    public function shouldExistsAll(Array $condition): Model
    {
        if ($this->modifiedModel) {

            $modifiedModel = $this->modifiedModel;

            $this->modifiedModel = null;

            if (! ($model = $modifiedModel->where($condition)->first())) abort(404);

        } else {

            if (! ($model = $this->model->where($condition)->first())) abort(404);
        }

        return $model;
    }

    /**
     * Check if model exists (without throwing validation error).
     *
     * @param array $params
     * @param bool $returnBool
     * @return bool|mixed
     */
    public function exists(Array $params, bool $returnBool = true, $except = null): bool
    {
      if (!$except)
        $result = $this->model->where($params)->first();
      else
        $result = $this->model->where('id', '!=', $except)->where($params)->first();

      return $returnBool ? !!$result : $result;
    }

}
