<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleFormRequest;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private RoleService $roleService)
    {
    }

    public function store(RoleFormRequest $request)
    {
        return $this->roleService->store($request->all());
    }

    public function update(RoleFormRequest $request, Role $role)
    {
        return $this->roleService->update($role, $request->all());
    }

    public function delete(Request $request, Role $role)
    {
        return $this->roleService->delete($request, $role);
    }

    public function get(Role $role)
    {
        return response()->json($role->load('permissions'), 200);
    }

    public function paginate(Request $request)
    {
        return $this->roleService->paginate($request);
    }

    public function roles()
    {
        return response()->json(Role::with('permissions')->get(), 200);
    }

    public function users(Role $role)
    {
        return response()->json($role->users, 200);
    }

    public function paginatePermissions(Request $request)
    {
        return $this->roleService->paginatePermissions($request);
    }

    public function permissions()
    {
        return response()->json(Permission::orderBy('name')->get(), 200);
    }

    public function all()
    {
        return response()->json(Role::where('id', '!=', 1)->orderBy('name')->get(), 200);
    }

    public function keyValue(Request $request)
    {
        // code...
        return $this->roleService->keyValue($request);
    }
}
