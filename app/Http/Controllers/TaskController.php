<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Task;
use App\Models\Feature;
use App\Models\Member;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskController extends Controller
{
    // public function index(){
    //     $tasks = Task::with(['members.user', 'feature'])->get();
    //     if($tasks->count() > 0){
    //         return response()->json([
    //             'status' => 200,
    //             'tasks' => $tasks
    //         ], 200);
    //     }else{
    //         return response()->json([
    //             'status' => 404,
    //             'message' => 'No Task Found!'
    //         ], 404);
    //     }
    // }

    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status');
        $perPage = $request->input('per_page', 5);
        $userId = auth()->id();
        $isAdmin = Admin::where('user_id', $userId)->exists();
        $tasks = Task::query();
        $tasks->with(['members.user', 'feature']);
        if ($keyword) {
            $tasks->where('name', 'like', "%$keyword%");
        }
    
        if ($status) {
            $tasks->where('status', $status);
        }

        if (!$isAdmin) {
            // If user is not an admin, filter tasks related to projects the member is associated with
            $tasks->whereHas('members', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });
            // $tasks->whereHas('members', function ($query) use ($userId) {
            //     $query->where('user_id', $userId);
            // })->with(['client', 'admin.user', 'members.user']);
        }

        $tasks = $tasks->paginate($perPage);

        if ($tasks->count() > 0) {
            // Prepend the image URL to each user's image filename
            $tasks->transform(function ($task) {
                $task->members->transform(function ($member) {
                    if ($member->user && $member->user->image) {
                        $member->user->image_url = url('storage/users/' . $member->user->image);
                    }
                    return $member;
                });
                return $task;
            });

            return response()->json([
                'status' => 200,
                'tasks' => $tasks
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No Task Found!'
            ], 404);
        }
    }


    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string',
            'feature_id'  => 'required|integer|exists:features,id', 
            'member_id'   => 'required|array',
            'member_id.*' => 'exists:members,id',
            'status'      => 'required|in:pending,completed,processing,planning,done,hold,recheck',
            'priority'    => 'required|in:high,medium,low',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date',
            'description' => 'required|string'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        // Validate if member IDs belong to the project
        $project = Feature::find($request->feature_id)->project;
        $projectMemberIds = $project->members->pluck('id')->toArray();
        $invalidMemberIds = array_diff($request->member_id, $projectMemberIds);

        if (!empty($invalidMemberIds)) {
            return response()->json([
                'status' => 422,
                'message' => 'Invalid member IDs provided.'
            ], 422);
        }

        // Get the ID of the currently authenticated user
        $userId = Auth::id();
    
        // Create the task
        $task = Task::create([
            'name'        => $request->name,
            'feature_id'  => $request->feature_id,
            'status'      => $request->status,
            'priority'    => $request->priority,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'description' => $request->description,
            'created_by'  => $userId,
            'updated_by'  => $userId
        ]);
        if ($task) {
            // Attach only valid users to the task
            $task->members()->attach($request->member_id);
    
            return response()->json([
                'status' => 200,
                'message' => "Task Created Successfully",
                'task' => $task->load('members') // Load the task with its members
            ], 200);
        } else {
            return response()->json([
                'status' => 500,
                'message' => "Something Went Wrong!"
            ], 500);
        }
    }

    public function show($id){
        $task = Task::with(['feature.project', 'members.user'])->find($id);

        if($task){

            if (isset($task->feature->project->image)) {
                $task->feature->project->image = url('storage/projects/' . $task->feature->project->image);
            }
    
            foreach ($task->members as $member) {
                if (isset($member->user->image)) {
                    $member->user->image = url('storage/users/' . $member->user->image);
                }
            }

            // Calculate the number of days between start_date and end_date
            $startDate = \Carbon\Carbon::parse($task->start_date);
            $endDate = \Carbon\Carbon::parse($task->end_date);
            $dayCount = $startDate->diffInDays($endDate);

            // Add the day_count to the task object
            $task->day_count = $dayCount;

            return response()->json([
                'status' => 200,
                'task' => $task,
                'day_count' => $dayCount
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Task Found!"
            ], 404);
        }
    }

    public function edit($id){
        $task = Task::with(['feature', 'members'])->find($id);
        if($task){
            return response()->json([
                'status' => 200,
                'task' => $task
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Task Found!"
            ], 404);
        }
    }

    public function update(Request $request, int $id){
        $validator = Validator::make($request->all(),[
            'name'        => 'required|string',
            'feature_id'  => 'required|integer|exists:features,id', 
            'member_id'   => 'required|array',
            'member_id.*' => 'exists:members,id',
            'status'      => 'required|in:pending,completed,processing,planning,done,hold,recheck',
            'priority'    => 'required|in:high,medium,low',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date',
            'description' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }
    
        $task = Task::find($id);
    
        if (!$task) {
            return response()->json([
                'status' => 404,
                'message' => 'Task not found'
            ], 404);
        }

        // Validate if member IDs belong to the project
        $project = Feature::find($request->feature_id)->project;
        $projectMemberIds = $project->members->pluck('id')->toArray();
        $invalidMemberIds = array_diff($request->member_id, $projectMemberIds);

        if (!empty($invalidMemberIds)) {
            return response()->json([
                'status' => 422,
                'message' => 'Invalid member IDs provided.'
            ], 422);
        }

        // Get the ID of the currently authenticated user
        $userId = Auth::id();
    
        $task->update([
            'name' => $request->name,
            'feature_id' => $request->feature_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'priority' => $request->priority,
            'description' => $request->description,
            'updated_by'  => $userId
        ]);
    
        // Sync users with the task
        $task->members()->sync($request->member_id);
    
        return response()->json([
            'status' => 200,
            'message' => "Task Updated Successfully",
            'task' => $task->load('members') // Optionally load the task with its members for the response
        ], 200);
    }

    public function destroy($id){
        $task = Task::find($id);
        if($task){
            $task->delete();
            return response()->json([
                'status' => 200,
                'message' => "Task Deleted Successfully!"
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No Such Task Found!"
            ], 404);
        }
    }

    public function getTasksByFeature($featureId, Request $request)
    {
        $status = $request->input('status');
        $priority = $request->input('priority');
        
        $tasks = Task::where('feature_id', $featureId)->with(['members.user', 'feature']);

        if ($status) {
            $tasks->where('status', $status);
        }
        
        if ($priority) {
            $tasks->where('priority', $priority);
        }

        $tasks = $tasks->get();

        if ($tasks->count() > 0) {
            // Transform tasks to include user image URL
            $transformedTasks = $tasks->map(function ($task) {
                $task->members->each(function ($member) {
                    $member->user->image_url = url('storage/users/' . $member->user->image);
                });
                return $task;
            });

            return response()->json([
                'status' => 200,
                'tasks' => $transformedTasks
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No Tasks Found for this task!'
            ], 404);
        }
    }

    

    public function updateUserIds(Request $request, int $id)
    {
        // Validate the user_ids input
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|array', // Array validation
            'member_id.*' => 'exists:members,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'status' => 404,
                'message' => 'Task not found'
            ], 404);
        }

        // Validate if user IDs belong to the project
        $project = Feature::find($task->feature_id)->project;
        $projectUserIds = $project->members->pluck('id')->toArray();
        $invalidUserIds = array_diff($request->member_id, $projectUserIds);

        if (!empty($invalidUserIds)) {
            return response()->json([
                'status' => 422,
                'message' => 'Invalid user IDs provided.'
            ], 422);
        }

        // Sync users with the task
        $task->members()->sync($request->member_id);

        return response()->json([
            'status' => 200,
            'message' => "User IDs updated successfully"
        ], 200);
    }

}
