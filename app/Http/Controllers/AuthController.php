<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Services\UserService;
use App\Services\UserActivityService;
use App\Services\MailService;
use App\Services\RoleService;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use UserActivityController;
use Carbon\Carbon;
use App\Events\UserRegistered; 
use App\Models\Role;

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
            event(new UserRegistered($user));

            $user->created_by = app('auth')->user()->id ?? null;
            $user->save();

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
        $roleService = app()->make(RoleService::class);
        $user->email_verified_at = Carbon::now();
        $user->confirmation_token = null;
        // $user->created_by = app('auth')->user()->id ?? null;
        $user->save();
        $roleService->assignUserRoles($user->id, ['1']);
        return response(['message' => 'Email confirmed! You can now login.'], 200);
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
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $resetUrl = "{$frontendUrl}/resetpwd/{$resetToken}";
            // $resetUrl = url("/api/resetpwd/{$resetToken}");
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

            $verification = $this->userService->verifyUser($credentials);

            if(!$verification['token']) return response($verification['error'], 401);
            $token = $verification['token'];

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
        return response(Auth::user()->load('roles'));
    }
    /**
     * Refresh the JWT token.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function isAdmin()
    {
        $roleId = 2;
        $role = Role::findOrFail($roleId);
        $user = app('auth')->user();
        if($user->roles->contains($roleId))
        {
            return response([true], 200);
        }
        return response([false], 500);
    }
    
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

    public function isValidUser()
    {
        if (app('auth')->check()) {
            return response(['valid' => true, 'user' => app('auth')->user()]);
        } else {
            return response(['valid' => false], 401);
        }
    }

    public function respondWithToken($token)
    {
        return response([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => Auth::user()->load('roles'),

        ]);
    }
}
