<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Admin;
use App\Models\Member;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use DB;

class ProfileController extends Controller
{
    public function getProject(){
       // Get the authenticated user
       $user = Auth::user();

       // Check if the user is associated with an admin record
       $admin = Admin::where('user_id', $user->id)->first();

       // Check if the user is associated with a member record
       $member = Member::where('user_id', $user->id)->first();

       $projects = collect();

    if ($admin) {
        // Eager load projects and their members for the admin
        $adminProjects = $admin->projects()->with('members.user')->get();
        $projects = $projects->merge($adminProjects);
    }

    if ($member) {
        // Eager load projects and their members for the member
        $memberProjects = $member->projects()->with('members.user')->get();
        $projects = $projects->merge($memberProjects);
    }


       if ($projects->isEmpty()) {
           return response()->json([
               'status' => 404,
               'message' => 'No projects found for the authenticated user.'
           ], 404);
       }

       $projects->each(function ($project) {

            $projectImagePath = str_replace(url('storage/projects/') . '/', '', $project->image);
            $project->image = url('storage/projects/' . $projectImagePath);

            $project->members->each(function ($member) {
                $userImagePath = str_replace(url('storage/users/') . '/', '', $member->user->image);
                $member->user->image = url('storage/users/'. $userImagePath); // Add image_url attribute to user
            });
        });

       return response()->json([
           'status' => 200,
           'projects' => $projects
       ], 200);
    }

    public function userDetail()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is associated with an admin record
        $admin = Admin::where('user_id', $user->id)->first();

        // Check if the user is associated with a member record
        $member = Member::where('user_id', $user->id)->first();

        $roles = $user->getRoleNames(); // returns a collection

        if ($admin) {
            // Fetch admin details along with user details
            $adminDetails = $admin->load('user');
            return response()->json([
                'status' => 200,
                'roles' => $roles,
                'detail' => $adminDetails,
            ], 200);
        } elseif ($member) {
            // Fetch member details along with user details
            $memberDetails = $member->load('user', 'category', 'designation');
            return response()->json([
                'status' => 200,
                'roles' => $roles,
                'detail' => $memberDetails,
            ], 200);
        } else {
            // If the user is neither an admin nor a member
            return response()->json([
                'status' => 404,
                'message' => 'User role not found.'
            ], 404);
        }
    }

    public function getUserproject(){
        // Get the authenticated user
        $user = Auth::user();
 
        // Check if the user is associated with an admin record
        $admin = Admin::where('user_id', $user->id)->first();
 
        // Check if the user is associated with a member record
        $member = Member::where('user_id', $user->id)->first();
 
        $projects = collect();
 
     if ($admin) {
         // Eager load projects and their members for the admin
         $adminProjects = $admin->projects()->with('members.user')->get();
         $projects = $projects->merge($adminProjects);
     }
 
     if ($member) {
         // Eager load projects and their members for the member
         $memberProjects = $member->projects()->with('members.user')->get();
         $projects = $projects->merge($memberProjects);
     }
 
 
        if ($projects->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No projects found for the authenticated user.'
            ], 404);
        }
 
        // Count total features and tasks for each project
        $projects->each(function ($project) {
            $project->total_features = $project->features->count();
            $project->total_tasks = $project->features->flatMap->tasks->count();

            $totalFeatures = $project->features->count();
            $completedFeatures = $project->features->where('status', 'completed')->count();
            $progress = $totalFeatures > 0 ? ($completedFeatures / $totalFeatures) * 100 : 0;
            $project->progress_percentage = number_format($progress, 2);
            
            $projectImagePath = str_replace(url('storage/projects/') . '/', '', $project->image);
            $project->image = url('storage/projects/' . $projectImagePath);

            $project->members->each(function ($member) {
                $userImagePath = str_replace(url('storage/users/') . '/', '', $member->user->image);
                $member->user->image = url('storage/users/'. $userImagePath);
            });
        });
 
        return response()->json([
            'status' => 200,
            'projects' => $projects
        ], 200);
    }

    public function getTeam(){
        // Get all members
        $members = Member::with('designation', 'user')->get();

        // Group by designation and count the number of users in each designation
        $designationCounts = Member::select('designation_id', DB::raw('count(*) as total'))
            ->groupBy('designation_id')
            ->with('designation')
            ->get()
            ->pluck('total', 'designation.name');

        return response()->json([
            'status' => 200,
            'members' => $members,
            'designation_counts' => $designationCounts
        ], 200);
    }

}
