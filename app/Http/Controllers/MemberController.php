<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Feature;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller
{

    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $members = Member::with(['user', 'designation', 'category']);
        if($keyword){
            $members->whereHas('user', function ($query) use ($keyword) {
                $query->where('name', 'like', "%$keyword%");
            });
        }
        $members = $members->get()->map(function ($member) {
            // Assigning imageUrl to each user
            $member->user->imageUrl = url('storage/users/' . $member->user->image);
            return $member;
        });
        if ($members->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No Member Found!'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'members' => $members
        ], 200);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'category_id' => 'required|integer|exists:categories,id',
            'designation_id' => 'required|integer|exists:designations,id', 
            'user_id' => 'required|integer|exists:users,id',  
            'description'  => 'required|string'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }else{
            $member = Member::create([
                'category_id'  => $request->category_id,
                'designation_id' => $request->designation_id,
                'user_id' => $request->user_id,
                'description' => $request->description
            ]);
        }
        if($member){
            return response()->json([
                'status' => 200,
                'message' => "Member Created Successfully"
            ], 200);
        }else{
            return response()->json([
                'status' => 500,
                'message' => "Something Went Wrong!"
            ], 500);
        }
    }

    public function show($id){
        $member = Member::with(['category' ,'user', 'designation'])->find($id);
        if($member){
            return response()->json([
                'status' => 200,
                'Member' => $member
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Member Found!"
            ], 404);
        }
    }

    public function edit($id){
        $member = Member::with(['user', 'designation'])->find($id);
        if($member){
            return response()->json([
                'status' => 200,
                'Member' => $member
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Member Found!"
            ], 404);
        }
    }

    public function update(Request $request, int $id){
        $validator = Validator::make($request->all(),[
            'category_id' => 'required|integer|exists:categories,id',
            'designation_id' => 'required|integer|exists:designations,id', 
            'user_id' => 'required|integer|exists:users,id',  
            'description'  => 'required|string'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }else{
            $member = Member::find($id);
        }
        if($member){
            $member->update([
                'category_id'  => $request->category_id,
                'designation_id' => $request->designation_id,
                'user_id' => $request->user_id,
                'description' => $request->description
            ]);
            return response()->json([
                'status' => 200,
                'message' => "Member Updated Successfully"
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Member Found!"
            ], 404);
        }
    }

    public function destroy($id){
        $member = Member::find($id);
        if($member){
            $member->delete();
            return response()->json([
                'status' => 200,
                'message' => "Member Deleted Successfully!"
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Member Found!"
            ], 404);
        }
    }

    public function getEmployeesByFeature($featureId) {
        // Find the feature by ID
        $feature = Feature::findOrFail($featureId);
    
        // Get the project ID associated with the feature
        $projectId = $feature->project_id;
    
        // Get the members associated with the project
        // $members = Project::findOrFail($projectId)->members;
        $members = Project::findOrFail($projectId)->members()->with('user');
        $members = $members->get()->map(function ($member) {
            // Assigning imageUrl to each user
            $member->user->imageUrl = url('storage/users/' . $member->user->image);
            return $member;
        });
    
        return response()->json(['members' => $members], 200);
    }
    
}