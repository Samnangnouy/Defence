<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware'=>'api'], function($routes){
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/validateToken', [AuthController::class, 'validateToken']);  // Updated route
});
Route::middleware(['auth:api'])->group(function () {
    Route::post('roles', [RoleController::class, 'store']);
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('permissions', [RoleController::class, 'create']);
    Route::get('roles/{role}', [RoleController::class, 'show']);
    Route::get('roles/{role}/edit', [RoleController::class, 'edit']);
    Route::put('roles/{role}/update', [RoleController::class, 'update']);
    Route::delete('roles/{role}/delete', [RoleController::class, 'destroy']);

    Route::post('users', [UserController::class, 'store']);
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::get('users/{user}/edit', [UserController::class, 'edit']);
    Route::post('users/{user}/update', [UserController::class, 'update']);
    Route::delete('users/{user}/delete', [UserController::class, 'destroy']);
    Route::post('/update-profile-picture', [UserController::class, 'updateProfilePicture'])->name('update-profile-picture');

    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::get('categories/{id}/edit', [CategoryController::class, 'edit']);
    Route::put('categories/{id}/update', [CategoryController::class, 'update']);
    Route::delete('categories/{id}/delete', [CategoryController::class, 'destroy']);

    Route::post('designations', [DesignationController::class, 'store']);
    Route::get('designations', [DesignationController::class, 'index']);
    Route::get('designations/{id}', [DesignationController::class, 'show']);
    Route::get('designations/{id}/edit', [DesignationController::class, 'edit']);
    Route::put('designations/{id}/update', [DesignationController::class, 'update']);
    Route::delete('designations/{id}/delete', [DesignationController::class, 'destroy']);
    Route::get('designation', [DesignationController::class, 'getDesignationsByCategory']);

    Route::post('members', [MemberController::class, 'store']);
    Route::get('members', [MemberController::class, 'index']);
    Route::get('members/{id}', [MemberController::class, 'show']);
    Route::get('members/{id}/edit', [MemberController::class, 'edit']);
    Route::put('members/{id}/update', [MemberController::class, 'update']);
    Route::delete('members/{id}/delete', [MemberController::class, 'destroy']);
    Route::get('members/feature/{featureId}', [MemberController::class, 'getEmployeesByFeature']);

    Route::post('clients', [ClientController::class, 'store']);
    Route::get('clients', [ClientController::class, 'index']);
    Route::get('clients/{id}', [ClientController::class, 'show']);
    Route::get('clients/{id}/edit', [ClientController::class, 'edit']);
    Route::post('clients/{id}/update', [ClientController::class, 'update']);
    Route::delete('clients/{id}/delete', [ClientController::class, 'destroy']);

    Route::post('admins', [AdminController::class, 'store']);
    Route::get('admins', [AdminController::class, 'index']);
    Route::get('admins/{id}', [AdminController::class, 'show']);
    Route::get('admins/{id}/edit', [AdminController::class, 'edit']);
    Route::put('admins/{id}/update', [AdminController::class, 'update']);
    Route::delete('admins/{id}/delete', [AdminController::class, 'destroy']);

    Route::post('projects', [ProjectController::class, 'store']);
    Route::get('projects', [ProjectController::class, 'index']);
    Route::get('projects/{id}', [ProjectController::class, 'show']);
    Route::get('projects/{id}/edit', [ProjectController::class, 'edit']);
    Route::post('projects/{id}/update', [ProjectController::class, 'update']);
    Route::delete('projects/{id}/delete', [ProjectController::class, 'destroy']);

    Route::post('features', [FeatureController::class, 'store']);
    Route::get('features', [FeatureController::class, 'index']);
    Route::get('features/{id}', [FeatureController::class, 'show']);
    Route::get('features/{id}/edit', [FeatureController::class, 'edit']);
    Route::put('features/{id}/update', [FeatureController::class, 'update']);
    Route::delete('features/{id}/delete', [FeatureController::class, 'destroy']);
    Route::get('features/project/{projectId}', [FeatureController::class, 'getFeaturesByProject']);

    Route::post('tasks', [TaskController::class, 'store']);
    Route::get('tasks', [TaskController::class, 'index']);
    Route::get('tasks/{id}', [TaskController::class, 'show']);
    Route::get('tasks/{id}/edit', [TaskController::class, 'edit']);
    Route::put('tasks/{id}/update', [TaskController::class, 'update']);
    Route::delete('tasks/{id}/delete', [TaskController::class, 'destroy']);
    Route::get('tasks/feature/{featureId}', [TaskController::class, 'getTasksByFeature']);
    Route::put('tasks/{id}/updateUser', [TaskController::class, 'updateUserIds']);

    Route::get('dashboard', [DashboardController::class, 'detail']);
    Route::get('project', [DashboardController::class, 'getProject']);
    Route::get('member', [DashboardController::class, 'getMember']);
    Route::get('task', [DashboardController::class, 'getTask']);
    Route::get('graph', [DashboardController::class, 'graph']);
    
    Route::get('userProject', [ProfileController::class, 'getProject']);
    Route::get('userDetail', [ProfileController::class, 'userDetail']);
    Route::get('projectUser', [ProfileController::class, 'getUserproject']);
    Route::get('userTeam', [ProfileController::class, 'getTeam']);

    Route::get('Reportproject', [ReportController::class, 'getProject']);
    Route::get('Reportfeature', [ReportController::class, 'getFeature']);
});
