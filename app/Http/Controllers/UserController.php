<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource; 


class UserController extends Controller
{
    //
    use HttpResponses;
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
            'type' => 'in:admin,Casher,Kitchen',
            'branch_id' => 'nullable|integer|exists:branches,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {

        try {
            $rules = [
                "email" => "required",
                "password" => "required"
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $credentials = $request->only(['email', 'password']);

            $token = Auth::guard('api')->attempt($credentials);


            if (!$token)
                return response()->json(['error' => 'Unauthorized'], 401);

            $user = Auth::guard('api')->user();


            return response()->json(['token' => $token, 'user' => $user]);
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], $ex->getCode());
        }
    }



    public function index()
    {

        $users = UserResource::collection(User::get());
        return $this->apiResponse($users, 'success', 200);
    }

    public function show($id)
    {

        $user = User::find($id);


        if ($user) {
            return $this->apiResponse(new UserResource($user), 'ok', 200);
        }
        return $this->apiResponse(null, 'The user Not Found', 404);
    }

    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'max:255',
            'email' => 'required|string|email|max:100',
            'password' => 'min:8',
            'type' => 'in:Kitchen,Casher,admin',
            'branch_id' => 'nullable|integer|exists:branches,id'
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(null, $validator->errors(), 400);
        }


        $user = User::find($id);


        if (!$user) {
            return $this->apiResponse(null, 'The user Not Found', 404);
        }

        $user->update(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        if ($user) {
            return $this->apiResponse(new UserResource($user), 'The user update', 201);
        }
    }


    public function destroy($id)
    {

        $user = User::find($id);

        if (!$user) {
            return $this->apiResponse(null, 'The user Not Found', 404);
        }

        

        if ($user) {

            $user->delete($id);
            return $this->apiResponse(null, 'The user deleted', 200);
        }
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    protected function respondWithToken($token, $user)
    {
        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

   
    
}
