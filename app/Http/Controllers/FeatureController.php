<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feature;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FeatureController extends Controller
{
    public function index(Request $request){
        $keyword = $request->input('keyword');
        $features = Feature::with('project');
        if($keyword){
            $features->where('name', 'like', "%$keyword%");
        }
        $features = $features->get();
        if($features->count() > 0){
            return response()->json([
                'status' => 200,
                'features' => $features
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => 'No Feature Found!'
            ], 404);
        }
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'name'          => 'required|string|max:255',
            'project_id'    => 'required|integer|exists:projects,id',
            'status'        => 'required|in:completed,incompleted',
            'description'   => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }else{
            $feature = Feature::create([
                'name'          => $request->name,
                'project_id'    => $request->project_id,
                'status'        => $request->status,
                'description'   => $request->description,
            ]);
        }
        if($feature){
            return response()->json([
                'status' => 200,
                'message' => "Feature Created Successfully"
            ], 200);
        }else{
            return response()->json([
                'status' => 500,
                'message' => "Something Went Wrong!"
            ], 500);
        }
    }

    // public function show($id){
    //     $feature = Feature::with('project')->find($id);
    //     if($feature){
    //         return response()->json([
    //             'status' => 200,
    //             'feature' => $feature
    //         ], 200);
    //     }else{
    //         return response()->json([
    //             'status' => 404,
    //             'message' => "No Such Feature Found!"
    //         ], 404);
    //     }
    // }

    public function show($id){
        // Eager load the project with admin and user's details
        $feature = Feature::with(['project.admin.user'])->find($id);
    
        if($feature){
            if ($feature->project && $feature->project->image) {
                $feature->project->imageUrl = url('storage/projects/' . $feature->project->image);
            }
    
            // Include the admin's user name
            $adminUserName = $feature->project->admin && $feature->project->admin->user 
                             ? $feature->project->admin->user->name 
                             : null;

            // Count the number of tasks associated with this feature
            $taskCount = $feature->tasks->count();
            $completedTaskCount = $feature->tasks->where('status', 'completed')->count();
            $incompleteTaskCount = $taskCount - $completedTaskCount;
            $featureProgress = $taskCount > 0 ? round(($completedTaskCount / $taskCount) * 100, 0) : 0;
            $incompleteProgress = $taskCount > 0 ? round(($incompleteTaskCount / $taskCount) * 100, 0) : 0;
             // Count the number of overdue tasks
            $currentDate = Carbon::now();
            $overdueTaskCount = $feature->tasks->where('status', '!=', 'completed')
                                            ->where('end_date', '<', $currentDate)
                                            ->count();
    
            return response()->json([
                'status' => 200,
                'feature' => $feature,
                'admin_user_name' => $adminUserName,
                'task_count' => $taskCount,
                'task_complete' => $completedTaskCount,
                'task_incomplete' => $incompleteTaskCount,
                'task_overdue' => $overdueTaskCount,
                'feature_progress' => $featureProgress,
                'incompleteProgress' => $incompleteProgress
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Feature Found!"
            ], 404);
        }
    }    

    public function edit($id){
        $feature = Feature::with('project')->find($id);
        if($feature){
            return response()->json([
                'status' => 200,
                'feature' => $feature
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Feature Found!"
            ], 404);
        }
    }

    public function update(Request $request, int $id){
        $validator = Validator::make($request->all(),[
            'name'          => 'required|string|max:255',
            'project_id'    => 'required|integer|exists:projects,id',
            'status'        => 'required|in:completed,incompleted',
            'description'   => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }else{
            $feature = Feature::find($id);
        }
        if($feature){
            $feature->update([
                'name'          => $request->name,
                'project_id'    => $request->project_id,
                'status'        => $request->status,
                'description'   => $request->description,
            ]);
            return response()->json([
                'status' => 200,
                'message' => "Feature Updated Successfully"
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Feature Found!"
            ], 404);
        }
    }

    public function destroy($id){
        $feature = Feature::find($id);
        if($feature){
            $feature->delete();
            return response()->json([
                'status' => 200,
                'message' => "Feature Deleted Successfully!"
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Feature Found!"
            ], 404);
        }
    }

    public function getFeaturesByProject($projectId){
        // Eager load the project relationship
        $features = Feature::with('project')->where('project_id', $projectId)->get();
    
        if($features->count() > 0){
            // Add imageUrl for each feature's project
            foreach ($features as $feature) {
                if ($feature->project && $feature->project->image) {
                    $feature->project->imageUrl = url('storage/projects/' . $feature->project->image);
                }
            }
    
            return response()->json([
                'status' => 200,
                'features' => $features
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No Features Found for this Project!'
            ], 404);
        }
    }
    
}
