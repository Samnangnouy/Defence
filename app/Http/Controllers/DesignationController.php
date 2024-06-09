<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Designation;
use Illuminate\Support\Facades\Validator;

class DesignationController extends Controller
{

    public function index(Request $request){
        $keyword = $request->input('keyword');
        $designations = Designation::with('category');
        if($keyword){
            $designations->where('name', 'like', "%$keyword%");
        }
        $designations = $designations->get();
        if($designations->count() > 0){
            return response()->json([
                'status' => 200,
                'designations' => $designations
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => 'No Designation Found!'
            ], 404);
        }
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'name'  => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id', 
            'description'  => 'required|string'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }else{
            $designation = Designation::create([
                'name'  => $request->name,
                'category_id'  => $request->category_id,
                'description' => $request->description
            ]);
        }
        if($designation){
            return response()->json([
                'status' => 200,
                'message' => "Designation Created Successfully"
            ], 200);
        }else{
            return response()->json([
                'status' => 500,
                'message' => "Something Went Wrong!"
            ], 500);
        }
    }

    public function show($id){
        $designation = Designation::with('category')->find($id);
        if($designation){
            return response()->json([
                'status' => 200,
                'designation' => $designation
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Designation Found!"
            ], 404);
        }
    }

    public function edit($id){
        $designation = Designation::find($id);
        if($designation){
            return response()->json([
                'status' => 200,
                'designation' => $designation
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Designation Found!"
            ], 404);
        }
    }

    public function update(Request $request, int $id){
        $validator = Validator::make($request->all(),[
            'name'  => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id', 
            'description'  => 'required|string'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }else{
            $designation = Designation::find($id);
        }
        if($designation){
            $designation->update([
                'name'  => $request->name,
                'category_id'  => $request->category_id,
                'description' => $request->description
            ]);
            return response()->json([
                'status' => 200,
                'message' => "Designation Updated Successfully"
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Designation Found!"
            ], 404);
        }
    }

    public function destroy($id){
        $designation = Designation::find($id);
        if($designation){
            $designation->delete();
            return response()->json([
                'status' => 200,
                'message' => "Designation Deleted Successfully!"
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Designation Found!"
            ], 404);
        }
    }

    public function getDesignationsByCategory(Request $request)
    {
        $categoryId = $request->query('category_id');
        $designations = Designation::where('category_id', $categoryId)->get();
        return response()->json(['designations' => $designations]);
    }

}
