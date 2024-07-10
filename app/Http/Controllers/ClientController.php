<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $perPage = $request->input('per_page', 5);
        $clients = Client::query();
        
        if ($keyword) {
            $clients->where('company_name', 'like', "%$keyword%");
        }
        
        // Retrieve the roles
        $clients = $clients->paginate($perPage);
    
        if ($clients->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No Client found.'
            ], 404);
        }


        $clients->getCollection()->transform(function ($client) {
            $client->imageUrl = url('storage/clients/' . $client->image);
            return $client;
        });
    
        return response()->json([
            'status' => 200,
            'message' => 'Clients retrieved successfully.',
            'clients' => $clients
        ], 200);
    }

    public function list(Request $request)
    {
        $keyword = $request->input('keyword');
        $clients = Client::query();
        
        if ($keyword) {
            $clients->where('company_name', 'like', "%$keyword%");
        }
        
        // Retrieve the roles
        $clients = $clients->get();
    
        if ($clients->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No Client found.'
            ], 404);
        }
    
        $clientsArray = $clients->map(function ($client) {
            $client->imageUrl = url('storage/clients/' . $client->image);
            return $client;
        });
    
        return response()->json([
            'status' => 200,
            'message' => 'Clients retrieved successfully.',
            'clients' => $clientsArray
        ], 200);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'required|string',
            'description' => 'required|string',
            'image' => 'required|file|image|max:2048', // The image is required and must be an image file no larger than 2MB
        ]);
    
        if($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        } 
    
        $client = new Client;
        $client->company_name = $request->input('company_name');
        $client->email = $request->input('email');
        $client->phone = $request->input('phone');
        $client->description = $request->input('description');
    
        if ($request->hasFile('image')) {
            $completeFileName = $request->file('image')->getClientOriginalName();
            $fileNameOnly = pathinfo($completeFileName, PATHINFO_FILENAME);
            $extension = $request->file('image')->getClientOriginalExtension();
            $compPic = str_replace(' ', '_', $fileNameOnly) . '-' . rand() . '_' . time() . '.' . $extension;
            $path = $request->file('image')->storeAs('public/clients', $compPic);
            $client->image = $compPic;
        }
    
        if ($client->save()) {
            $clientImageUrl = url('storage/clients/'.$compPic); // Construct the URL
    
            return response()->json([
                'status' => 200,
                'message' => 'Client created successfully!',
                'imageUrl' => $clientImageUrl // Return the URL in your response
            ], 200);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!'
            ], 500);
        }
    }

    public function show($id)
    {
        $client = Client::find($id);

        if (is_null($client)) {
            return response()->json([
                'status' => 404,
                'message' => 'Client not found.'
            ], 404);
        }

        // Add a property to hold the image URL
        $client->imageUrl = url('storage/clients/' . $client->image);

        return response()->json([
            'status' => 200,
            'message' => 'Client retrieved successfully.',
            'client' => $client
        ], 200);
    }

    public function edit($id)
    {
        $client = Client::find($id);

        if (is_null($client)) {
            return response()->json([
                'status' => 404,
                'message' => 'Client not found.'
            ], 404);
        }

        // Add a property to hold the image URL
        $client->imageUrl = url('storage/clients/' . $client->image);

        return response()->json([
            'status' => 200,
            'message' => 'Client retrieved successfully.',
            'client' => $client
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email,' . $id,
            'phone' => 'required|string',
            'description' => 'required|string',
            'image' => 'nullable|file|image|max:2048', // Image is now nullable to allow for updates without changing the image.
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        $client = Client::find($id);

        if (is_null($client)) {
            return response()->json([
                'status' => 404,
                'message' => 'Client not found.'
            ], 404);
        }

        $client->company_name = $request->input('company_name');
        $client->email = $request->input('email');
        $client->phone = $request->input('phone');
        $client->description = $request->input('description');

        if ($request->hasFile('image')) {
            // Delete old image
            Storage::delete('public/clients/' . $client->image);

            // Upload new image
            $completeFileName = $request->file('image')->getClientOriginalName();
            $fileNameOnly = pathinfo($completeFileName, PATHINFO_FILENAME);
            $extension = $request->file('image')->getClientOriginalExtension();
            $compPic = str_replace(' ', '_', $fileNameOnly) . '-' . rand() . '_' . time() . '.' . $extension;
            $path = $request->file('image')->storeAs('public/clients', $compPic);
            $client->image = $compPic; // Update the image field with new image name
        }

        if ($client->save()) {
            $clientImageUrl = url('storage/clients/'.$client->image); // Construct the URL for new/updated image

            return response()->json([
                'status' => 200,
                'message' => 'Product updated successfully!',
                'imageUrl' => $clientImageUrl // Return the URL in your response
            ], 200);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!'
            ], 500);
        }
    }

    public function destroy($id)
    {
        $client = Client::find($id);

        if (is_null($client)) {
            return response()->json([
                'status' => 404,
                'message' => 'Client not found.'
            ], 404);
        }

        // Delete the image file from storage
        $imageDeleted = Storage::delete('public/clients/' . $client->image);

        // If the image was successfully deleted or the image does not exist, delete the product
        if ($imageDeleted || !Storage::exists('public/clients/' . $client->image)) {
            $clientDeleted = $client->delete();

            if ($clientDeleted) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Client and image deleted successfully!'
                ], 200);
            } else {
                // If there was an issue deleting the product, respond with an error
                return response()->json([
                    'status' => 500,
                    'message' => 'Client could not be deleted.'
                ], 500);
            }
        } else {
            // If there was an issue deleting the image, respond with an error
            return response()->json([
                'status' => 500,
                'message' => 'Image could not be deleted.'
            ], 500);
        }
    }
}
