<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Repositories\AuthRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    use ApiResponser;

    protected AuthRepository $authRepository;

    public function __construct(AuthRepository $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function register(RegisterUserRequest $request): JsonResponse
    {
        $user = $this->authRepository->createUser($request->validated());
        
        return $this->successResponse(
            new UserResource($user),
            'User registered successfully',
            201
        );
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $user = $this->authRepository->findUserByEmail($request->email);

        if (!$user || !$this->authRepository->checkPassword($user, $request->password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        $token = $this->authRepository->createToken($user);

        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user)
        ], 'Login successful');
    }

    public function logout(): JsonResponse
    {
        $this->authRepository->deleteCurrentToken(auth()->user());
        
        return $this->successResponse(
            null,
            'Logged out successfully'
        );
    }

    public function user(): JsonResponse
    {
        return $this->successResponse(
            new UserResource(auth()->user())
        );
    }
}