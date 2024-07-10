<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:category-list|category-create|category-edit|category-delete', ['only' => ['index','show']]);
         $this->middleware('permission:category-create', ['only' => ['create','store']]);
         $this->middleware('permission:category-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:category-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request){
        $keyword = $request->input('keyword');
        $perPage = $request->input('per_page', 5);
        $categories = Category::query();
        if($keyword){
            $categories->where('name', 'like', "%$keyword%");
        }
        // Retrieve the roles
        $categories = $categories->paginate($perPage);

        if($categories->count() > 0){
            return response()->json([
                'status' => 200,
                'categories' => $categories
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => 'No Category Found!'
            ], 404);
        }
    }

    public function list(Request $request){
        $keyword = $request->input('keyword');
        $categories = Category::query();
        if($keyword){
            $categories->where('name', 'like', "%$keyword%");
        }
        // Retrieve the roles
        $categories = $categories->get();

        if($categories->count() > 0){
            return response()->json([
                'status' => 200,
                'categories' => $categories
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => 'No Category Found!'
            ], 404);
        }
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'name'  => 'required|string|max:255',
            'note'  => 'required|string'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }else{
            $category = Category::create([
                'name'  => $request->name,
                'note'  => $request->note,
            ]);
        }
        if($category){
            return response()->json([
                'status' => 200,
                'message' => "Category Created Successfully"
            ], 200);
        }else{
            return response()->json([
                'status' => 500,
                'message' => "Something Went Wrong!"
            ], 500);
        }
    }

    public function show($id){
        $category = Category::find($id);
        if($category){
            return response()->json([
                'status' => 200,
                'category' => $category
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Category Found!"
            ], 404);
        }
    }

    public function edit($id){
        $category = Category::find($id);
        if($category){
            return response()->json([
                'status' => 200,
                'category' => $category
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Category Found!"
            ], 404);
        }
    }

    public function update(Request $request, int $id){
        $validator = Validator::make($request->all(),[
            'name'  => 'required|string|max:255',
            'note'  => 'required|string'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }else{
            $category = Category::find($id);
        }
        if($category){
            $category->update([
                'name'          => $request->name,
                'note' => $request->note,
            ]);
            return response()->json([
                'status' => 200,
                'message' => "Category Updated Successfully"
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Category Found!"
            ], 404);
        }
    }

    public function destroy($id){
        $category = Category::find($id);
        if($category){
            $category->delete();
            return response()->json([
                'status' => 200,
                'message' => "Category Deleted Successfully!"
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Category Found!"
            ], 404);
        }
    }
}
