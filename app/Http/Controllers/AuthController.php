<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(Request $request)
    {
        try{
            $this->validate($request, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $this->userService->createUser($request->only(['name', 'email', 'password']));

            return response()->json(['message' => 'User registered successfully'], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => 'False',
                'message' => 'Validation error. Please check the input fields.',
                'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json([
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

            return response()->json([
                'guard' => $guard,
                'driver' => $driver,
                'user' => app('auth')->user(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
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
            $credentials = $request->only(['email', 'password']);

            if (! $token = Auth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid email or password'], 401);
            }

            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            return response()->json([
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
            Auth::logout();
            return response()->json(['message' => 'User logged out successfully'], 200);
        }
        catch (\Exception $e) {
            return response()->json([
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
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        return response()->json(app('auth')->user());
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
            return response()->json([
                'success' => 'False',
                'message' => 'Error.',
                'error' => 'Could not refresh token'], 500);
        }
    }

    public function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => Auth::user()
        ]);
    }
}
