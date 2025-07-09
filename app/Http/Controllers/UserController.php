<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Services\UserService;
use App\Services\UserActivityService;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use UserActivityController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    protected $userService;
    protected $userActivityService;

    public function __construct(UserService $userService, UserActivityService $userActivityService)
    {
        $this->userService = $userService;
        $this->userActivityService = $userActivityService;
    }

    public function updateName(Request $request, $userId = null)
    {
        try {
            $this->validate($request, [
                'name' => 'required|string|max:255',
            ]);
            $authuser = app('auth')->user();
            if(!$userId) {
                $userId = $authuser->id;
            }
            else {
                if (!Gate::allows('is-Admin', $authuser)) {
                    return response(['error' => 'No permission'], 403);
                }
            }
            $result = [];
            $result = $this->userService->updateUserName($userId, $request->input('name'));
            if($result['status'] == 'success')
                return response($result['message'], 200);
        } catch (ValidationException $e) {
            return response([
                'success' => 'False',
                'message' => 'Validation error. Please check the input fields.',
                'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response([
                'success' => 'False',
                'message' => 'Server error.',
                'error' => $e->getMessage()], 500);
        }
    }

    public function softDeleteUser($id)
    {
        $user = app('auth')->user();
        $result = $this->userService->performSoftDeleteUser($user, $id);
        if ($result['status'] === 'success') {
            return response(['message' => $result['message']], 200);
        } else {
            return response(['error' => $result['message']], 400);
        }
    }

    public function bulksoftDeleteUsers(Request $request)
    {
        try{
            $this->validate($request, [
                'user_ids' => 'required|array',
                'user_ids.*' => 'integer|exists:users,id',
            ]);
            $user = app('auth')->user();
            $userIds = $request->input('user_ids');
            $result = $this->userService->performBulkDeleteUsers($userIds, $user);
            if(!$result['error']) return response($result, 200);
            return response($result, 400);
        } catch (ValidationException $e) {
            return response([
                'success' => 'False',
                'message' => 'Validation error. Please check the input fields.',
                'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                'success' => 'False',
                'message' => 'Server error.',
                'error' => $e->getMessage()], 500);
        }
    }

    public function listUser(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'string|max:255|nullable',
                'email' => 'string|email|max:255|nullable',
                'role' => 'string|max:255|nullable',
                'pagenumber' => 'integer|min:1|nullable',
                'perpage' => 'integer|min:1|nullable',
            ]);

            $filters = [];
            $filters['name'] = $request->input('name');
            $filters['email'] = $request->input('email');
            $filters['role'] = $request->input('role');
            $pageNumber = $request->input('pagenumber', 1);
            $perPage = $request->input('perpage', 10);

            $users = $this->userService->listUsers($filters, $pageNumber, $perPage);
            return response($users);

        } catch (\Exception $e) {
            return response([
                'success' => 'False',
                'message' => 'Server error.',
                'error' => $e->getMessage()], 500);
        }
    }

    public function listUserActivity(Request $request)
    {
        try{
            $this->validate($request, [
                'name' => 'string|max:255|nullable',
                'from' => 'date|nullable',
                'to' => 'date|nullable',
                'pagenumber' => 'integer|min:1|nullable',
                'perpage' => 'integer|min:1|nullable',
            ]);

            $filters = [];
            $filters['name'] = $request->input('name');
            $filters['from'] = $request->input('from');
            $filters['to'] = $request->input('to');
            $pageNumber = $request->input('pagenumber', 1);
            $perPage = $request->input('perpage', 2);

            $users = $this->userActivityService->listUserActivities($filters, $pageNumber, $perPage);
            return response($users);
        } catch (ValidationException $e) {
            return response([
                'success' => 'False',
                'message' => 'Validation error. Please check the input fields.',
                'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response([
                'success' => 'False',
                'message' => 'Server error.',
                'error' => $e->getMessage()], 500);
        }
    }

    public function getRecentActivities(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|integer|exists:users,id',
        ]);
        $userId = $request->input('user_id');
        $user = User::findOrFail($userId);
        if (!$user) {
            return response(['error' => 'Unauthorized'], 401);
        }
        if (!Gate::allows('is-Admin', Auth::user())) {
            return response(['error' => 'No permission'], 403);
        }
        $activities = $this->userActivityService->getRecentActivities($userId);

        return response($activities);
    }
    
}