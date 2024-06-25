<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Feature;
use App\Models\Task;
use App\Models\Member;

class DashboardController extends Controller
{
    public function detail(){
        $project = Project::all();
        $feature = Feature::all();
        $task = Task::all();
        $member = Member::all();

        $totalProject = $project->count();
        $totalFeature = $feature->count();
        $totalTask = $task->count();
        $totalMember = $member->count();

        return response()->json([
            'total_project' => $totalProject,
            'total_feature' => $totalFeature,
            'tota_task'     => $totalTask,
            'total_member'  => $totalMember
        ]);
    }

    public function getProject()
    {
        $projects = Project::with('features', 'members.user')
        ->orderBy('created_at', 'desc')
        ->take(7)
        ->get();

        // Modify the project collection to include full URL for project and user images
        $projects->each(function($project) {
            $projectImagePath = str_replace(url('storage/projects/') . '/', '', $project->image);
            $project->image = url('storage/projects/' . $projectImagePath);

            // Calculate project progress percentage
            $totalFeatures = $project->features->count();
            $completedFeatures = $project->features->where('status', 'completed')->count();
            $progress = $totalFeatures > 0 ? ($completedFeatures / $totalFeatures) * 100 : 0;
            $project->progress_percentage = number_format($progress, 2);

            $project->members->each(function($member) {
                $userImagePath = str_replace(url('storage/users/') . '/', '', $member->user->image);
                $member->user->image = url('storage/users/' . $userImagePath);
            });
        });

        return response()->json([
            'status' => 200,
            'projects' => $projects
        ], 200);
    }

    public function getMember(){
        $members = Member::with(['user', 'designation'])->get();

        // Modify the member collection to include full URL for user images
        $members->each(function($member) {
            // Generate full URL for user image
            $member->user->image = url('storage/users/' . $member->user->image);
        });

        return response()->json([
            'status' => 200,
            'member' => $members
        ], 200);
    }

    public function getTask(){
        $task = Task::latest()->take(7)->get();
        return response()->json([
            'status' => 200,
            'task' => $task
        ], 200);
    }

    public function graph() {
        $tasks = Task::all();
        if ($tasks->isNotEmpty()) {
            $totalTasks = $tasks->count();
            $completedTasks = $tasks->where('status', 'completed')->count();
            $inProgressTasks = $tasks->where('status', 'processing')->count();
            $behindTasks = $tasks->count() - ($completedTasks + $inProgressTasks);

            $completedPercentage = ($completedTasks / $totalTasks) * 100;
            $inProgressPercentage = ($inProgressTasks / $totalTasks) * 100;
            $behindPercentage = ($behindTasks / $totalTasks) * 100;

            return response()->json([
                'status' => 200,
                'completedTasks' => $completedTasks,
                'inProgressTasks' => $inProgressTasks,
                'behindTasks' => $behindTasks,
                'completedPercentage' => round($completedPercentage, 0),
                'inProgressPercentage' => round($inProgressPercentage, 0),
                'behindPercentage' => round($behindPercentage, 0)
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No Tasks Found!'
            ], 404);
        }
    }
}
