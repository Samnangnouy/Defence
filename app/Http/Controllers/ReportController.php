<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Feature;
use App\Models\Admin;

class ReportController extends Controller
{
    public function getProject(Request $request)
{
    $userId = auth()->id();
    $isAdmin = Admin::where('user_id', $userId)->exists();
    $perPage = $request->input('per_page', 5);
    $projects = Project::with('features', 'members.user');

    if (!$isAdmin) {
        // If user is not an admin, filter projects related to those the member is associated with
        $projects->whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        });
    }

    $projects = $projects->paginate($perPage);

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


public function getFeature(Request $request)
{
    $userId = auth()->id();
    $isAdmin = Admin::where('user_id', $userId)->exists();
    $perPage = $request->input('per_page', 5);
    $features = Feature::with('tasks', 'project');

    if (!$isAdmin) {
        // If user is not an admin, filter features related to projects the member is associated with
        $features->whereHas('project.members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        });
    }

    $features = $features->paginate($perPage);

    // Calculate feature progress percentage
    $features->each(function($feature) {
        $totalTasks = $feature->tasks->count();
        $completedTasks = $feature->tasks->where('status', 'completed')->count();
        $featureProgress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
        $feature->progress_percentage = number_format($featureProgress, 2);
    });

    return response()->json([
        'status' => 200,
        'features' => $features
    ], 200);
}

}
