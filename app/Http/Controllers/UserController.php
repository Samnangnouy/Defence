<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use DB;
use Hash;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{

    // function __construct()
    // {
    //      $this->middleware('permission:user-list|user-create|user-edit|user-delete', ['only' => ['index','store']]);
    //      $this->middleware('permission:user-create', ['only' => ['create','store']]);
    //      $this->middleware('permission:user-edit', ['only' => ['edit','update']]);
    //      $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    // }

    // public function index(Request $request)
    // {
    //     $keyword = $request->input('keyword');
    //     $users = User::with('roles');
    //     if($keyword){
    //         $users->where('name', 'like', "%$keyword%");
    //     }
    //     $users = $users->get();
    //     if($users->count() > 0){
    //         return response()->json([
    //             'status' => 200,
    //             'users' => $users
    //         ], 200);
    //     } else {
    //         return response()->json([
    //             'status' => 404,
    //             'message' => 'No User Found!'
    //         ], 404);
    //     }
    // }

    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $perPage = $request->input('per_page', 5);
        $users = User::with('roles');
        
        if ($keyword) {
            $users->where('name', 'like', "%$keyword%");
        }
        
        // Use pagination
        $users = $users->paginate($perPage);

        // Map over the users to add the image URL
        $users->getCollection()->transform(function ($user) {
            $user->imageUrl = url('storage/users/' . $user->image);
            return $user;
        });
        
        if ($users->count() > 0) {
            return response()->json([
                'status' => 200,
                'users' => $users
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No User Found!'
            ], 404);
        }
    }

    public function list(Request $request)
    {
        $keyword = $request->input('keyword');
        $users = User::with('roles');
        
        if ($keyword) {
            $users->where('name', 'like', "%$keyword%");
        }
        
        $users = $users->get();
        
        if ($users->count() > 0) {
            // Map over the users to add the image URL
            $users = $users->map(function ($user) {
                $user->imageUrl = url('storage/users/' . $user->image);
                return $user;
            });
            
            return response()->json([
                'status' => 200,
                'users' => $users
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No User Found!'
            ], 404);
        }
    }


    // public function store(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required',
    //         'email' => 'required|email|unique:users,email',
    //         'password' => 'required|confirmed',
    //         'roles' => 'required',
    //         'image' => 'required|file|image|max:2048',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 422);
    //     }

    //     $input = $request->all();
    //     $input['password'] = Hash::make($input['password']);

    //     $user = User::create($input);
    //     $user->assignRole($request->input('roles'));

    //     return response()->json([
    //         'status' => 200,
    //         'message' => 'User created successfully', 
    //         'user' => $user
    //     ], 201);
    // }

    public function store(Request $request) {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed',
            'roles' => 'required',
            'image' => 'required|file|image|max:2048', // The image is required and must be an image file no larger than 2MB
        ]);
    
        // If validation fails, return a JSON response with the errors
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
    
        // Hash the password
        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
    
        // Handle the image upload
        if ($request->hasFile('image')) {
            $completeFileName = $request->file('image')->getClientOriginalName();
            $fileNameOnly = pathinfo($completeFileName, PATHINFO_FILENAME);
            $extension = $request->file('image')->getClientOriginalExtension();
            $compPic = str_replace(' ', '_', $fileNameOnly) . '-' . rand() . '_' . time() . '.' . $extension;
            $path = $request->file('image')->storeAs('public/users', $compPic);
            $input['image'] = $compPic;
        }
    
        // Create the user
        $user = User::create($input);
    
        // Assign roles to the user
        $user->assignRole($request->input('roles'));
    
        // Construct the image URL
        $userImageUrl = url('storage/users/' . $compPic);
    
        // Return a JSON response with the created user and the image URL
        return response()->json([
            'status' => 201,
            'message' => 'User created successfully',
            'user' => $user,
            'imageUrl' => $userImageUrl
        ], 201);
    }    

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $roles = $user->getRoleNames();
        $permissions = $user->getAllPermissions()->pluck('name');
        // Construct the image URL
        $userImageUrl = url('storage/users/' . $user->image);

        return response()->json([
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
            'imageUrl' => $userImageUrl
        ]);
    }

    // public function edit($id)
    // {
    //     $user = User::find($id);

    //     if (!$user) {
    //         return response()->json(['message' => 'User not found'], 404);
    //     }

    //     $roles = $user->getRoleNames();
    //     $permissions = $user->getAllPermissions()->pluck('name');
    //     $userImageUrl = url('storage/users/' . $user->image);

    //     return response()->json([
    //         'user' => $user,
    //         'roles' => $roles,
    //         'permissions' => $permissions,
    //         'imageUrl' => $userImageUrl
    //     ]);
    // }

    public function edit($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $roles = $user->getRoleNames();
        $permissions = $user->getAllPermissions()->pluck('name');
        $imageUrl = $user->image ? url('storage/users/' . $user->image) : null;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $imageUrl,
                'roles' => $roles
            ],
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'password' => 'required|confirmed',
            'roles' => 'required',
            'image' => 'sometimes|file|image|max:2048'
        ]);
    
        $input = $request->all();
        if(!empty($input['password'])){ 
            $input['password'] = Hash::make($input['password']);
        }else{
            $input = Arr::except($input,array('password'));    
        }

         // Handle the image upload
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($user->image) {
                Storage::delete('public/users/' . $user->image);
            }

            $completeFileName = $request->file('image')->getClientOriginalName();
            $fileNameOnly = pathinfo($completeFileName, PATHINFO_FILENAME);
            $extension = $request->file('image')->getClientOriginalExtension();
            $compPic = str_replace(' ', '_', $fileNameOnly) . '-' . rand() . '_' . time() . '.' . $extension;
            $path = $request->file('image')->storeAs('public/users', $compPic);
            $input['image'] = $compPic;
        }
    
        $user->update($input);
        DB::table('model_has_roles')->where('model_id',$id)->delete();
    
        $user->assignRole($request->input('roles'));
        // Construct the image URL
        $userImageUrl = url('storage/users/' . $user->image);
    
        return response()->json([
            'status' => 200,
            'message' => 'User updated successfully',
            'user' => $user,
            'imageUrl' => $userImageUrl
        ], 201);
    }

    // public function destroy($id)
    // {
    //     $user = User::find($id);
    //     if (!$user) {
    //         return response()->json(['message' => 'User not found'], 404);
    //     }
    //     $user->delete();
    //     return response()->json([
    //         'status' => 200,
    //         'message' => 'User deleted successfully'
    //     ], 200);
    // }

    public function destroy($id)
    {
        $user = User::find($id);

        if (is_null($user)) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found.'
            ], 404);
        }

        // Delete the image file from storage
        $imageDeleted = Storage::delete('public/users/' . $user->image);

        // If the image was successfully deleted or the image does not exist, delete the product
        if ($imageDeleted || !Storage::exists('public/users/' . $user->image)) {
            $userDeleted = $user->delete();

            if ($userDeleted) {
                return response()->json([
                    'status' => 200,
                    'message' => 'User and image deleted successfully!'
                ], 200);
            } else {
                // If there was an issue deleting the product, respond with an error
                return response()->json([
                    'status' => 500,
                    'message' => 'User could not be deleted.'
                ], 500);
            }
        } else {
            // If there was an issue deleting the image, respond with an error
            return response()->json([
                'status' => 500,
                'message' => 'Image could not be deleted.'
            ], 500);
        }
    }

    public function updateProfilePicture(Request $request)
    {
        $user = Auth::user(); // Retrieve the currently logged-in user

        $this->validate($request, [
            'image' => 'required|file|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($user->image) {
                Storage::delete('public/users/' . $user->image); // Delete the old image if it exists
            }

            $completeFileName = $request->file('image')->getClientOriginalName();
            $fileNameOnly = pathinfo($completeFileName, PATHINFO_FILENAME);
            $extension = $request->file('image')->getClientOriginalExtension();
            $compPic = str_replace(' ', '_', $fileNameOnly) . '-' . rand() . '_' . time() . '.' . $extension;
            $path = $request->file('image')->storeAs('public/users', $compPic);
            $user->image = $compPic;
        }

        $user->save(); // Save the updated user record

        $userImageUrl = url('storage/users/' . $user->image); // Construct the image URL

        return response()->json([
            'status' => 200,
            'message' => 'Profile picture updated successfully',
            'imageUrl' => $userImageUrl
        ], 200);
    }

}
