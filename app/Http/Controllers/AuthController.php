<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Services\UserService;
use App\Services\UserActivityService;
use App\Services\MailService;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use UserActivityController;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    protected $userService;
    protected $userActivityService;
    protected $mailService;

    public function __construct(UserService $userService,
                                UserActivityService $userActivityService,
                                MailService $mailService)
    {
        $this->userService = $userService;
        $this->userActivityService = $userActivityService;
        $this->mailService = $mailService;
    }

    public function register(Request $request)
    {
        try{
            $this->validate($request, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $user = $this->userService->createUser($request->only(['name', 'email', 'password']));

            $confirmationUrl = url("/api/confirm/{$user->confirmation_token}");
            $data = [
                'name' => $user->name,
                'confirmationUrl' => $confirmationUrl,
            ];

            $this->mailService->sendMail('emails.confirmation', $data, $user->email, $user->name,
                                        'Confirm your email', 'no-reply@example.com', 'App Name');

            return response(['message' => 'Registration successful. Check your email to confirm.'], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
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

    public function confirmEmail($token)
    {
        $user = User::where('confirmation_token', $token)->firstOrFail();
        $user->email_verified_at = Carbon::now();
        $user->confirmation_token = null;
        $user->created_by = app('auth')->user()->id ?? null;
        $user->save();
        return response(['message' => 'Email confirmed! You can now login.']);
    }

    public function forgotPassword(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|string|email|max:255',
            ]);

            $user = User::where('email', $request->email)->first();
            if(!$user) {
                return response(['error' => 'User not found'], 404);

            }
            $resetToken = Str::random(32);
            $user->confirmation_token = $resetToken;
            $user->save();
            $resetUrl = url("/api/resetpwd/{$resetToken}");
            $data = [
                'name' => $user->name,
                'resetUrl' => $resetUrl,
            ];
            $this->mailService->sendMail('emails.ResetPassword', $data, $user->email, $user->name,
                                        'Reset your password', 'no-reply@example.com', 'App Name');

            return response(['message' => 'Password reset link sent to your email.'], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
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

    public function resetPassword(Request $request, $token)
    {
        try {
            $this->validate($request, [
                'password' => 'required|string|min:6|confirmed',
            ]);

            $user = User::where('confirmation_token', $token)->firstOrFail();
            $user->password = Hash::make($request->password);
            $user->confirmation_token = null;
            $user->save();

            return response(['message' => 'Password has been reset successfully.'], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
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

    public function test(Request $request)
    {
        try{
            $guard = app('auth')->getDefaultDriver();
            $driver = config("auth.guards.{$guard}.driver");

            return response([
                'guard' => $guard,
                'driver' => $driver,
                'user' => app('auth')->user(),
            ]);
        } catch (\Exception $e) {
            return response([
                'success' => 'False',
                'message' => 'Server error.',
                'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle user login.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try{
            $this->validate($request, [
                'email' => 'required|string|email|max:255',
                'password' => 'required|string|min:6',
            ]);
            $credentials = $request->only(['email', 'password']);

            $token = $this->userService->verifyUser($credentials);

            $user = app('auth')->user();
            $loginTime = Carbon::now()->toDateTimeString();
            $this->userActivityService->loginActivity($user->id, $loginTime);
            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            return response([
                'success' => 'False',
                'message' => 'Server error.',
                'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Handle user logout.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try{
            $user = app('auth')->user();
            Auth::logout();
            $logoutTime = Carbon::now()->toDateTimeString();
            $this->userActivityService->logoutActivity($user->id, $logoutTime);
            return response(['message' => 'User logged out successfully'], 200);
        }
        catch (\Exception $e) {
            return response([
                'success' => 'False',
                'message' => 'Server error.',
                'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        if (!app('auth')->check()) {
            return response(['error' => 'Unauthenticated'], 401);
        }
        return response(app('auth')->user());
    }
    /**
     * Refresh the JWT token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    
    public function refresh()
    {
        try{
            $new_token = app('auth')->refresh();
            return $this->respondWithToken(app('auth')->refresh());
        }
        catch(\Exception $e) {
            return response([
                'success' => 'False',
                'message' => 'Error.',
                'error' => 'Could not refresh token'], 500);
        }
    }

    public function respondWithToken($token)
    {
        return response([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => Auth::user()
        ]);
    }

    public function softDeleteUser($id)
    {
        try {
            $user = app('auth')->user();
            $userToDelete = User::findOrFail($id);
            if(!($user->roles->contains('role', 'admin')))
            {
                return response(['error' => 'You do not have permission to delete this user'], 403);
            }
            if ($user->id == $id) {
                return response()->json(['error' => 'You cannot delete yourself'], 400);
            }
            $userToDelete->deleted_by = $user->id;
            $userToDelete->save();
            $userToDelete->delete();
            return response(['message' => 'User deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response(['error' => 'User not found'], 404);
        } catch (\Exception $e) {
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
            $perPage = $request->input('perpage', 2);

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
        } catch (\Illuminate\Validation\ValidationException $e) {
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

}
