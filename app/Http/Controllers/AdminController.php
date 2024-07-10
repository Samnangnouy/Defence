<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $perPage = $request->input('per_page', 5);
        $admins = Admin::with(['user']);
        if($keyword){
            $admins->whereHas('user', function ($query) use ($keyword) {
                $query->where('name', 'like', "%$keyword%");
            });
        }
        $admins = $admins->paginate($perPage);
        
        if ($admins->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No Admin Found!'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'admins' => $admins
        ], 200);
    }

    public function list(Request $request)
    {
        $keyword = $request->input('keyword');
        $admins = Admin::with(['user']);
        if($keyword){
            $admins->whereHas('user', function ($query) use ($keyword) {
                $query->where('name', 'like', "%$keyword%");
            });
        }
        $admins = $admins->get();
        
        if ($admins->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No Admin Found!'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'admins' => $admins
        ], 200);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'name'    => 'required|string', 
            'user_id' => 'required|integer|exists:users,id',  
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }else{
            $admin = Admin::create([
                'name'    => $request->name,
                'user_id' => $request->user_id,
            ]);
        }
        if($admin){
            return response()->json([
                'status' => 200,
                'message' => "Admin Created Successfully"
            ], 200);
        }else{
            return response()->json([
                'status' => 500,
                'message' => "Something Went Wrong!"
            ], 500);
        }
    }

    public function show($id){
        $admin = Admin::with(['user'])->find($id);
        if($admin){
            return response()->json([
                'status' => 200,
                'admin' => $admin
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Admin Found!"
            ], 404);
        }
    }

    public function edit($id){
        $admin = Admin::with(['user'])->find($id);
        if($admin){
            return response()->json([
                'status' => 200,
                'admin' => $admin
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Admin Found!"
            ], 404);
        }
    }

    public function update(Request $request, int $id){
        $validator = Validator::make($request->all(),[
            'name'    => 'required|string', 
            'user_id' => 'required|integer|exists:users,id', 
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }else{
            $admin = Admin::find($id);
        }
        if($admin){
            $admin->update([
                'name'  => $request->name,
                'user_id' => $request->user_id,
            ]);
            return response()->json([
                'status' => 200,
                'message' => "Admin Updated Successfully"
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Admin Found!"
            ], 404);
        }
    }

    public function destroy($id){
        $admin = Admin::find($id);
        if($admin){
            $admin->delete();
            return response()->json([
                'status' => 200,
                'message' => "Admin Deleted Successfully!"
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Member Found!"
            ], 404);
        }
    }
}
