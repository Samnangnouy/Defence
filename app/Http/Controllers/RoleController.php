<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
    

class RoleController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index','show']]);
         $this->middleware('permission:role-create', ['only' => ['create','store']]);
         $this->middleware('permission:role-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }

    
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $perPage = $request->input('per_page', 5);
        $roles = Role::query(); // Initialize the query builder

        // Apply the search filter if keyword is present
        if ($keyword) {
            $roles->where('name', 'like', "%$keyword%");
        }

        // Retrieve the roles
        $roles = $roles->paginate($perPage);

        // Check if roles are found
        if ($roles->count() > 0) {
            return response()->json([
                'status' => 200,
                'roles' => $roles
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No Roles Found!'
            ], 404);
        }
    }

    public function list(Request $request)
    {
        $keyword = $request->input('keyword');
        $roles = Role::query(); // Initialize the query builder

        // Apply the search filter if keyword is present
        if ($keyword) {
            $roles->where('name', 'like', "%$keyword%");
        }

        // Retrieve the roles
        $roles = $roles->get();

        // Check if roles are found
        if ($roles->count() > 0) {
            return response()->json([
                'status' => 200,
                'roles' => $roles
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No Roles Found!'
            ], 404);
        }
    }


    public function create()
    {
        $permission = Permission::get();
        return response()->json(['permissions' => $permission], 200);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);
    
        $role = Role::create(['name' => $request->input('name')]);
        $role->syncPermissions($request->input('permission'));
    
        return response()->json([
            'status' => 200,
            'message' => 'Role created successfully'
        ], 200);
    }

    public function show($id)
    {
        $role = Role::find($id);
        $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
            ->where("role_has_permissions.role_id",$id)
            ->get();
    
        return response()->json(['role' => $role, 'permissions' => $rolePermissions], 200);
    }

    public function edit($id)
    {
        $role = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
            ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
            ->all();
    
        return response()->json([
            'status' => 200,
            'role' => $role, 
            'permissions' => $permission, 
            'rolePermissions' => $rolePermissions
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
        ]);
    
        $role = Role::find($id);
        $role->name = $request->input('name');
        $role->save();
    
        $role->syncPermissions($request->input('permission'));
    
        return response()->json([
            'status' => 200,
            'message' => 'Role updated successfully'
        ], 200);
    }
    
    public function destroy($id)
    {
        DB::table("roles")->where('id',$id)->delete();
        return response()->json([
            'status' => 200,
            'message' => 'Role deleted successfully'
        ], 200);
    }
}
