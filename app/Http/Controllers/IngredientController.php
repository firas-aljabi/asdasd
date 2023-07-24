<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\IngredientResource;
use Illuminate\Support\Facades\File;

class IngredientController extends Controller
{
    use ApiResponseTrait;
    
   
    
    public function index()
    {
        $ingredients = IngredientResource::collection(Ingredient::get());
        return $this->apiResponse($ingredients,'success',200);
    }

    public function show($id){

        $ingredient = Ingredient::find($id);
        

        if($ingredient){
            return $this->apiResponse(new IngredientResource($ingredient),'ok',200);
        }
        return $this->apiResponse(null,'The ingredient Not Found',404);

    }

    
    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'price_by_piece' => 'required|numeric|min:0',
            'image' => 'nullable|file||image|mimes:jpeg,jpg,png',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(null,$validator->errors(),400);
        }

        $ingredient = new Ingredient();
        $ingredient->name = $request->name;
        $ingredient->price_by_piece = $request->price_by_piece;
        $ingredient->branch_id = $request->branch_id;
        if($request->hasFile('image')){
            $image = $request->file('image');
            $filename = $image->getClientOriginalName();
            $request->image->move(public_path('/images/ingredient'),$filename);
            $ingredient->image = $filename;
        }
        $ingredient->save();
        $ingredient->products()->save($ingredient);

        if($ingredient){
            return $this->apiResponse(new IngredientResource($ingredient),'The ingredient Save',201);
        }else{
            return $this->apiResponse(null,'The ingredient Not Save',400);
        }

        
    }

    

    
    public function update(Request $request ,$id){

        $validator = Validator::make($request->all(), [
            'name' => 'max:255',
            'price_by_piece' => 'numeric|min:0',
            'image' => 'nullable|file||image|mimes:jpeg,jpg,png',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(null,$validator->errors(),400);
        }


        $ingredient =Ingredient::find($id);
        
        if($ingredient){
            
            $ingredient->name = $request->name;
            $ingredient->price_by_piece = $request->price_by_piece;
            $ingredient->branch_id = $request->branch_id;

            if($request->hasFile('image')){
                File::delete(public_path('/images/ingredient/'.$ingredient->image));
                $image = $request->file('image');
                $filename = $image->getClientOriginalName();
                $request->image->move(public_path('/images/ingredient'),$filename);
                $ingredient->image = $filename;
            }
            $ingredient->save();
            $ingredient->products()->sync($ingredient);

            return $this->apiResponse(new IngredientResource($ingredient),'The ingredient update',201);
        }else{
            return $this->apiResponse(null,'The ingredient Not Found',404);
        }

    }

    
    public function destroy($id){

        $ingredient=Ingredient::find($id);

        if($ingredient){

            $ingredient->delete($id);
            File::delete(public_path('/images/ingredient/'.$ingredient->image));

            return $this->apiResponse(null,'The ingredient deleted',200);
        }else{
            return $this->apiResponse(null,'The ingredient Not Found',404);
        }

    }
}
