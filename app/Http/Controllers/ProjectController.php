<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status');
        // $userId = auth()->id();
    
        $projects = Project::query();
    
        if ($keyword) {
            $projects->where('name', 'like', "%$keyword%");
        }
    
        if ($status) {
            $projects->where('status', $status);
        }

        // $projects->whereHas('users', function ($query) use ($userId) {
        //     $query->where('user_id', $userId);
        // });
    
        $projects->with(['client', 'admin.user', 'members.user']);
        $projects = $projects->get();
    
        if ($projects->count() > 0) {
            $projectData = $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'image' => url('storage/projects/' . $project->image),
                    'status' => $project->status,
                    'priority' => $project->priority,
                    'description' => $project->description,
                    'start_date' => $project->start_date,
                    'end_date' => $project->end_date,
                    'client' => $project->client ? $project->client->company_name : null,
                    'admin' => $project->admin ? [
                        'name' => $project->admin->user->name,
                        'image_url' => $project->admin->user->image ? url('storage/users/' . $project->admin->user->image) : null
                    ] : null,
                    'members' => $project->members->map(function ($member) {
                        return $member->user ? [
                            'name' => $member->user->name,
                            'image_url' => $member->user->image ? url('storage/users/' . $member->user->image) : null
                        ] : null;
                    })->filter()->values()->toArray(), // Filter out null values and reset array keys
                ];
            });

            return response()->json([
                'status' => 200,
                'projects' => $projectData
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No Project Found!'
            ], 404);
        }
    }

    public function store(Request $request) {
        // Check if the authenticated user is an admin
        $user = Auth::user();
        $isAdmin = Admin::where('user_id', $user->id)->exists();

        if (!$isAdmin) {
            return response()->json([
                'status' => 403,
                'message' => 'You do not have permission to create projects.'
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'image'         => 'required|file|image|max:2048',
            'status'        => 'required|in:pending,completed,planning',
            'priority'      => 'required|in:high,medium,low',
            'description'   => 'required|string',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date',
            'admin_id'      => 'required|integer|exists:admins,id',
            'client_id'     => 'required|integer|exists:clients,id',
            'member_id'     => 'required|array',
            'member_id.*'   => 'exists:members,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }
    
        $project = new Project;
        $project->name = $request->input('name');
        $project->status = $request->input('status');
        $project->priority = $request->input('priority');
        $project->description = $request->input('description');
        $project->start_date = $request->input('start_date');
        $project->end_date = $request->input('end_date');
        $project->admin_id = $request->input('admin_id');
        $project->client_id = $request->input('client_id');
        $project->created_by = auth()->id(); // Set created_by
        $project->updated_by = auth()->id(); // Set updated_by
    
        if ($request->hasFile('image')) {
            $completeFileName = $request->file('image')->getClientOriginalName();
            $fileNameOnly = pathinfo($completeFileName, PATHINFO_FILENAME);
            $extension = $request->file('image')->getClientOriginalExtension();
            $compPic = str_replace(' ', '_', $fileNameOnly) . '-' . rand() . '_' . time() . '.' . $extension;
            $path = $request->file('image')->storeAs('public/projects', $compPic);
            $project->image = $compPic;
        }
    
        if ($project->save()) {
            // Attach members to the project after the project is saved
            $project->members()->attach($request->input('member_id'));
            
            $projectImageUrl = url('storage/projects/'.$project->image); // Construct the URL
            
            return response()->json([
                'status' => 200,
                'message' => 'Project created successfully!',
                'imageUrl' => $projectImageUrl
            ], 200);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!'
            ], 500);
        }
    }

    // public function show($id)
    // {
    //     $project = Project::with(['client', 'admin.user', 'members.user'])->find($id);

    //     if (is_null($project)) {
    //         return response()->json([
    //             'status' => 404,
    //             'message' => 'Project not found.'
    //         ], 404);
    //     }

    //     $project->imageUrl = url('storage/projects/' . $project->image);

    //     return response()->json([
    //         'status' => 200,
    //         'message' => 'Project retrieved successfully.',
    //         'project' => [
    //             'id' => $project->id,
    //             'name' => $project->name,
    //             'image' => $project->imageUrl,
    //             'status' => $project->status,
    //             'priority' => $project->priority,
    //             'description' => $project->description,
    //             'start_date' => $project->start_date,
    //             'end_date' => $project->end_date,
    //             'client' => $project->client->company_name,
    //             'admin' => $project->admin->user->name,
    //             'members' => $project->members->map(function ($member) {
    //                 return $member->user->name;
    //             })
    //         ]
    //     ], 200);
    // }

    public function show($id)
    {
        $project = Project::with(['client', 'admin.user', 'members.user'])->find($id);

        if (is_null($project)) {
            return response()->json([
                'status' => 404,
                'message' => 'Project not found.'
            ], 404);
        }

        // Add a property to hold the image URL
        $project->imageUrl = url('storage/projects/' . $project->image);

        // Calculate the total number of days from start date to end date
        $startDate = Carbon::parse($project->start_date);
        $endDate = Carbon::parse($project->end_date);
        $totalDays = $endDate->diffInDays($startDate);

        $totalFeatures = $project->features->count();
        $completedFeatures = $project->features->where('status', 'completed')->count();
        $progress = $totalFeatures > 0 ? round(($completedFeatures / $totalFeatures) * 100, 0) : 0;

        return response()->json([
            'status' => 200,
            'message' => 'Project retrieved successfully.',
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'image' => $project->imageUrl,
                'status' => $project->status,
                'priority' => $project->priority,
                'description' => $project->description,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
                'total_days' => $totalDays,
                'progress' => $progress,
                'client' => $project->client->company_name,
                'admin' => $project->admin->user->name, // Assuming 'admin' has a relationship with 'user'
                'members' => $project->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->user->name, // Assuming 'member' has a relationship with 'user'
                        'email' => $member->user->email, // Assuming 'member' has a relationship with 'user'
                        'profile_image' => $member->user->image ? url('storage/users/' . $member->user->image) : null, // Assuming 'user' has a 'profile_image' field
                        'role' => $member->user->roles // Assuming 'member' has a 'role' field
                    ];
                })
            ]
        ], 200);
    }



    public function edit($id)
    {
        $project = Project::with(['client', 'admin.user', 'members.user'])->find($id);

        if (is_null($project)) {
            return response()->json([
                'status' => 404,
                'message' => 'Project not found.'
            ], 404);
        }

        // Add a property to hold the image URL
        $project->imageUrl = url('storage/projects/' . $project->image);

        // Iterate through each member and add imageUrl for their user image
        foreach ($project->members as $member) {
            $member->user->imageUrl = url('storage/users/' . $member->user->image);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Project retrieved successfully.',
            'project' => $project
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'image'         => 'nullable|file|image|max:2048', // Image is now nullable to allow for updates without changing the image.
            'status'        => 'required|in:pending,completed,planning',
            'priority'      => 'required|in:high,medium,low',
            'description'   => 'required|string',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date',
            'admin_id'      => 'required|integer|exists:admins,id',
            'client_id'     => 'required|integer|exists:clients,id',
            'member_id'     => 'required|array',
            'member_id.*'   => 'exists:members,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        $project = Project::find($id);

        if (is_null($project)) {
            return response()->json([
                'status' => 404,
                'message' => 'Project not found.'
            ], 404);
        }

        $project->name = $request->input('name');
        $project->status = $request->input('status');
        $project->priority = $request->input('priority');
        $project->description = $request->input('description');
        $project->start_date = $request->input('start_date');
        $project->end_date = $request->input('end_date');
        $project->admin_id = $request->input('admin_id');
        $project->client_id = $request->input('client_id');
        $project->updated_by = auth()->id(); // Set updated_by

        if ($request->hasFile('image')) {
            // Delete old image
            if ($project->image) {
                Storage::delete('public/projects/' . $project->image);
            }

            // Upload new image
            $completeFileName = $request->file('image')->getClientOriginalName();
            $fileNameOnly = pathinfo($completeFileName, PATHINFO_FILENAME);
            $extension = $request->file('image')->getClientOriginalExtension();
            $compPic = str_replace(' ', '_', $fileNameOnly) . '-' . rand() . '_' . time() . '.' . $extension;
            $path = $request->file('image')->storeAs('public/projects', $compPic);
            $project->image = $compPic; // Update the image field with new image name
        }

        if ($project->save()) {
            // Sync members to the project after the project is saved
            $project->members()->sync($request->input('member_id'));

            $projectImageUrl = url('storage/projects/'.$project->image); // Construct the URL for new/updated image

            return response()->json([
                'status' => 200,
                'message' => 'Project updated successfully!',
                'imageUrl' => $projectImageUrl // Return the URL in your response
            ], 200);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!'
            ], 500);
        }
    }

    // public function destroy($id)
    // {
    //     $project = Project::find($id);

    //     if (is_null($project)) {
    //         return response()->json([
    //             'status' => 404,
    //             'message' => 'Project not found.'
    //         ], 404);
    //     }

    //     if ($project->created_by != auth()->id()) {
    //         return response()->json([
    //             'status' => 403,
    //             'message' => 'You do not have permission to delete this project.'
    //         ], 403);
    //     }

    //     $imageDeleted = Storage::delete('public/projects/' . $project->image);

    //     if ($imageDeleted || !Storage::exists('public/projects/' . $project->image)) {
    //         $projectDeleted = $project->delete();

    //         if ($projectDeleted) {
    //             return response()->json([
    //                 'status' => 200,
    //                 'message' => 'Project and image deleted successfully!'
    //             ], 200);
    //         } else {
    //             return response()->json([
    //                 'status' => 500,
    //                 'message' => 'Project could not be deleted.'
    //             ], 500);
    //         }
    //     } else {
    //         return response()->json([
    //             'status' => 500,
    //             'message' => 'Image could not be deleted.'
    //         ], 500);
    //     }
    // }

    public function destroy(Request $request, $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json([
                'status' => 404,
                'message' => "No Such Project Found!"
            ], 404);
        }

        if ($project->created_by !== auth()->id()) {
            return response()->json([
                'status' => 403,
                'message' => "Unauthorized! Only the creator can delete this project."
            ], 403);
        }

        // Validate project name
        if ($request->input('project_name') !== $project->name) {
            return response()->json([
                'status' => 400,
                'message' => "Project name does not match."
            ], 400);
        }

        if ($project->delete()) {
            return response()->json([
                'status' => 200,
                'message' => "Project Deleted Successfully!"
            ], 200);
        } else {
            return response()->json([
                'status' => 500,
                'message' => "Something went wrong! Unable to delete project."
            ], 500);
        }
    }

}
