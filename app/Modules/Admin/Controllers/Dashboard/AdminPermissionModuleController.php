<?php

namespace App\Modules\Admin\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\AdminPermissionModule;
use App\Modules\Admin\ApiPresenters\PermissionModulePresenter;
use App\Support\Traits\ModelManipulations;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminPermissionModuleController extends Controller
{
    use ModelManipulations;

    /**
     *
     * @var AdminPermissionModule
     */
    protected $model;

    /**
     * Permissions Type
     *
     * @var string
     */
    protected $type = 'admin-permission-module';

    /**
     * RoleController constructor.
     */
    public function __construct ()
    {
        $this -> model = new AdminPermissionModule();
    }

    /**
     * Show all models rows.
     */
    public function index ()
    {
        return success([
                           'rows' => AdminPermissionModule ::all() -> map(function ($item) {
                               return (new PermissionModulePresenter()) -> item($item);
                           })
                       ]);
    }

    /**
     * Fetch Single Role Information
     *
     * @param Int $id
     *
     * @return JsonResponse
     */
    public function show (int $id): JsonResponse
    {
        //
    }

    /**
     * Save model data.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store (Request $request): JsonResponse
    {
        //
    }

    /**
     * Update model data.
     *
     * @param Int     $id
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update (int $id, Request $request): JsonResponse
    {
        //
    }
}
