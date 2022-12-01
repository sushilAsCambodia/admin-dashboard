<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new User())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id');

            $query->when($request->name, function ($query) use ($request) {
                $query->where('users.name', 'like', "%$request->name%")
                    ->orWhere('email', 'like', "%$request->name%")
                    ->orWhere('roles.name', 'like', "%$request->name%");
            });
            // $query->when($request->email, function ($query) use ($request) {
            //     $query->where('email', $request->email);
            // });
            $results = $query->select('users.*')->with(['roles', 'merchants'])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function store(array $data): JsonResponse
    {
        try {
            DB::transaction(function () use ($data) {
                $user = User::create($data);
                $role = Role::find($data['role_id']);
                $user->assignRole($role->name);

                if (count($data['merchants'])) {
                    $user->merchants()->sync($data['merchants']);
                }
            });

            return response()->json([
                'messages' => ['User created successfully'],
            ], 201);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function update($user, array $data): JsonResponse
    {
        try {
            DB::transaction(function () use ($user, $data) {
                $user->update($data);
                $role = Role::find($data['role_id']);

                if ($role) {
                    foreach ($user->roles as $value) {
                        $user->removeRole($value->name);
                    }
                    $user->assignRole($role->name);
                }

                if (count($data['merchants'])) {
                    $user->merchants()->sync($data['merchants']);
                }
            });

            return response()->json([
                'messages' => ['User updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($user): JsonResponse
    {
        if ($user->hasRole('super admin')) {
            return response()->json([
                'messages' => ['You are not allowed to delete Super Admin'],
            ], 400);
        }

        try {
            $user->delete();

            return response()->json([
                'messages' => ['User deleted successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function verifyPassword($request, $user): JsonResponse
    {
        try {
            if (Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => true,
                    'messages' => ['Password is correct'],
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'messages' => ['Password is correct'],
                ], 200);
            }
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function updatePassword($data, $user): JsonResponse
    {
        try {
            $user = User::find($data['id']);
            if ($data['current_password']) {
                if (Hash::check($data['current_password'], $user->password)) {
                    $user->password = $data['password'];
                    $user->update();

                    return response()->json([
                        'status' => true,
                        'messages' => ['updated successfully'],
                    ], 200);
                } else {
                    return response()->json([
                        'status' => false,
                        'messages' => ['current password incorrect'],
                    ], 200);
                }
            } else {
                if ($user) {
                    $user->password = $data['password'];
                    $user->update();

                    return response()->json([
                        'data' => $data['password'],
                        'status' => true,
                        'messages' => ['updated successfully'],
                    ], 200);
                } else {
                    return response()->json([
                        'data' => $data['password'],
                        'status' => false,
                        'messages' => ['Password incorrect'],
                    ], 200);
                }
            }
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
