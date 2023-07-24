<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Resturant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResturantController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resturant_name' => 'required|string|between:2,100',
            'phone' => 'nullable|numeric|min:6',
            'email' => 'required|string|email|max:100|unique:resturants',
            'password' => 'required|string|min:6',
            'address' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $resturant = new Resturant();
        $resturant->resturant_name = $request->resturant_name;
        $resturant->phone = $request->phone;
        $resturant->email = $request->email;
        $resturant->password = bcrypt($request->password);
        $resturant->address = $request->address;
        $resturant->save();

        if($resturant){
            $user = new User();
            $user->name = $request->resturant_name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->type = 'admin';
            $user->branch_id = 1;
            $user->save();

            $branch = new Branch();
            $branch->name = $request->resturant_name;
            $branch->address = $request->address;
            $branch->save();

            return response()->json([
                'message' => 'Data successfully saved',
                'resturant' => $resturant
            ], 201);

        }
    }

    public function update(Request $request , $id)
    {
        $validator = Validator::make($request->all(), [
            'resturant_name' => 'required|string|between:2,100',
            'phone' => 'nullable|numeric|min:6',
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|min:6',
            'address' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $resturant = Resturant::find($id);

        if($resturant){

            $resturant->resturant_name = $request->resturant_name;
            $resturant->phone = $request->phone;
            $resturant->email = $request->email;
            $resturant->password = bcrypt($request->password);
            $resturant->address = $request->address;
            $resturant->save();

            

            $branch = Branch::find($id);
            $branch->name = $request->resturant_name;
            $branch->address = $request->address;
            $branch->save();

            $user = User::find($id);
            $user->name = $request->resturant_name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->type = 'admin';
            $user->branch_id = $branch->id;
            $user->save();
            
            return response()->json([
                'message' => 'Data successfully saved',
                'resturant' => $resturant
            ], 201);

        }
    }

    public function delete($id){
        $resturant = Resturant::find($id);
        $user = User::find($id);
        $branch = Branch::find($id);

        if($resturant){

            $resturant->delete($id);
            $user->delete($id);
            $branch->delete($id);

            return response()->json([
                'message' => 'The Data deleted',
                'resturant' => $resturant
            ], 200);
        }else{
            return response()->json([
                'message' => 'The resturant Not Found',
            ], 404);
            return $this->apiResponse(null, 'The resturant Not Found', 404);
        }
    }
}
