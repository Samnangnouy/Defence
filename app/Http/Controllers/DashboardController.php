<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Feature;
use App\Models\Task;
use App\Models\Member;
use App\Models\Admin;

class DashboardController extends Controller
{
    public function detail(Request $request)
{
    $userId = auth()->id();
    $isAdmin = Admin::where('user_id', $userId)->exists();

    // Initialize counts
    $totalProject = 0;
    $totalFeature = 0;
    $totalTask = 0;
    $totalMember = 0;

    if ($isAdmin) {
        // If user is admin, count all projects, features, tasks, and members
        $totalProject = Project::count();
        $totalFeature = Feature::count();
        $totalTask = Task::count();
        $totalMember = Member::count();
    } else {
        // If user is not admin, count only related projects, features, tasks, and members
        $totalProject = Project::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();

        $totalFeature = Feature::whereHas('project.members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();

        $totalTask = Task::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();
    }
    $totalMember = Member::count();

    return response()->json([
        'total_project' => $totalProject,
        'total_feature' => $totalFeature,
        'total_task'    => $totalTask,
        'total_member'  => $totalMember
    ]);
}

public function getProject(Request $request)
{
    $userId = auth()->id();
    $isAdmin = Admin::where('user_id', $userId)->exists();

    $projectsQuery = Project::query()
        ->with('features', 'members.user')
        ->orderBy('created_at', 'desc')
        ->take(7);

    if (!$isAdmin) {
        // If user is not an admin, filter projects related to the member
        $projectsQuery->whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        });
    }

    $projects = $projectsQuery->get();

    // Modify the project collection to include full URL for project and user images
    $projects->each(function ($project) {
        $projectImagePath = str_replace(url('storage/projects/') . '/', '', $project->image);
        $project->image = url('storage/projects/' . $projectImagePath);

        // Calculate project progress percentage
        $totalFeatures = $project->features->count();
        $completedFeatures = $project->features->where('status', 'completed')->count();
        $progress = $totalFeatures > 0 ? ($completedFeatures / $totalFeatures) * 100 : 0;
        $project->progress_percentage = number_format($progress, 2);

        $project->members->each(function ($member) {
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

    public function getTask(Request $request)
{
    $userId = auth()->id();
    $isAdmin = Admin::where('user_id', $userId)->exists();

    $tasksQuery = Task::query()->latest()->take(7);

    if (!$isAdmin) {
        // If user is not an admin, filter tasks related to projects the member is associated with
        $tasksQuery->whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        });
    }

    $tasks = $tasksQuery->get();

    // Modify the tasks collection to include any necessary transformations

    return response()->json([
        'status' => 200,
        'task' => $tasks
    ], 200);
}
public function graph(Request $request)
{
    $userId = auth()->id();
    $isAdmin = Admin::where('user_id', $userId)->exists();

    if ($isAdmin) {
        $tasks = Task::all();
    } else {
        $tasks = Task::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get();
    }

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
