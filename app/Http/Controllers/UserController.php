<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserFormRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    public function paginate(Request $request)
    {
        return $this->userService->paginate($request);
    }

    public function all()
    {
        return response()->json(User::all(), 200);
    }

    public function store(UserFormRequest $request)
    {
        return $this->userService->store($request->all());
    }

    public function update(UserFormRequest $request, User $user)
    {
        return $this->userService->update($user, $request->all());
    }

    public function delete(User $user)
    {
        return $this->userService->delete($user);
    }

    public function get(User $user)
    {
        return response()->json($user, 200);
    }

    public function verifyPassword(Request $request, User $user)
    {
        return $this->userService->verifyPassword($request, $user);
    }

    public function updatePassword(Request $request, User $user)
    {
        return $this->userService->updatePassword($request->all(), $user);
    }
}
