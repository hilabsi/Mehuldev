<?php

namespace App\Modules\Admin\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\AdminRole;
use App\Modules\Admin\Models\AdminRoleAssignPermission;
use App\Modules\Admin\ApiPresenters\RolePresenter;
use App\Modules\Admin\Enums\RoleResponses;
use App\Support\Traits\ModelManipulations;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminRoleController extends Controller
{
    use ModelManipulations;

    /**
     *
     * @var AdminRole 
     */
    protected $model;

    /**
     * Permissions Type
     *
     * @var string
     */
    protected $type = 'admin-roles';

    /**
     * RoleController constructor.
     */
    public function __construct ()
    {
        $this -> model = new AdminRole();
    }

    /**
     * Show all models rows.
     */
    public function index ()
    {
        return success([
                           'rows' => AdminRole ::all() -> map(function ($item) {
                               return (new RolePresenter()) -> item($item);
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
        $model = $this -> shouldExists('id', $id);

        return success([
                           'role' => (new RolePresenter()) -> show($model)
                       ]);
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
        $this -> validate($request, $this -> model ::validations() -> create());
        
        $permission_data = $request -> only(['permission',]);

        DB ::beginTransaction();
        try {
            $role = $this -> model -> create($request -> only(['name',]));

            DB ::commit();
            
            if(isset($role->id) && !empty($permission_data['permission']))
            {
                $req_data = array();
                
                foreach($permission_data['permission'] as $row)
                {
                    $req_data = array();
                    $req_data = array(
                        'role_id'=>$role->id,
                        'module_id'=>$row['module_u_id'],
                        'view_access'=>isset($row['view']) ? $row['view'] : 0,
                        'add_access'=>isset($row['add']) ? $row['add'] : 0,
                        'update_access'=>isset($row['update']) ? $row['update'] : 0,
                        'delete_access'=>isset($row['delete']) ? $row['delete'] : 0,
                        'status_access'=>isset($row['status']) ? $row['status'] : 0,
                        );
                        
                    if(!empty($req_data))
                    {
                        $rolepmodel = AdminRoleAssignPermission::create($req_data);
                    }
                }
                
                
            }

        } catch (Exception $exception) {

            DB ::rollBack();

            return failed([
                              $exception -> getMessage()
                          ]);
        }

        return success([
                           'id' => $role -> id
                       ]);
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
        
        $role = $this -> shouldExists('id', $id);
        
        $this -> validate($request, $this -> model ::validations() -> edit($id));
        
        $permission_data = $request -> only(['permission',]);
        
        if (!$request -> hasAny(['name',])) {
            return other(RoleResponses::NO_FIELDS_SENT);
        }

        DB ::beginTransaction();
        try {

            $role -> update($request -> only(['name',]));
            DB ::commit();
            
            if(isset($id) && !empty($permission_data['permission']))
            {
                AdminRoleAssignPermission::where('role_id',$id)->delete();
                
                foreach($permission_data['permission'] as $row)
                {
                    $req_data = array();
                    $req_data = array(
                        'role_id'=>$role->id,
                        'module_id'=>$row['module_u_id'],
                        'view_access'=>isset($row['view']) ? $row['view'] : 0,
                        'add_access'=>isset($row['add']) ? $row['add'] : 0,
                        'update_access'=>isset($row['update']) ? $row['update'] : 0,
                        'delete_access'=>isset($row['delete']) ? $row['delete'] : 0,
                        'status_access'=>isset($row['status']) ? $row['status'] : 0,
                        );
                        
                    if(!empty($req_data))
                    {
                        $rolepmodel = AdminRoleAssignPermission::create($req_data);
                    }
                }
            }
            
        } catch (Exception $exception) {
            DB ::rollBack();

            return failed([
                              $exception -> getCode(),
                              $exception -> getMessage()
                          ]);
        }

        return success();
    }
    
    public function destroy($id, Request $request): JsonResponse
    {
        $role = AdminRole::find($id);
        
        if (!isset($role->id) || empty($role->id))
          return other(RoleResponses::ACCOUNT_ALREADY_DELETED);
          
        if($role->getUserno() > 0)
          return other(RoleResponses::USED_USER);
    
        DB::beginTransaction();
        try {
    
          $role->delete();
    
          DB::commit();
    
          return success();
    
        } catch (\Exception $exception) {
    
          DB::rollBack();
    
          return failed();
        }
    }
}
