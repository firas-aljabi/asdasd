<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Support\Facades\File;

class BranchController extends Controller
{
    use ApiResponseTrait;
    
    public function index()
    {
        $branches = BranchResource::collection(Branch::get());
        return $this->apiResponse($branches,'success',200);
    }

    public function show($id)
    {
        $branch = Branch::find($id);

        if($branch){
            return $this->apiResponse(new BranchResource($branch),'ok',200);
        }else{
            return $this->apiResponse(null,'The branch Not Found',404);
        }
        
    }

    
    public function store(Request $request){
       

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'about' => 'nullable|string|min:3|max:2500',
            'image' => 'nullable|file|image|mimes:jpeg,jpg,png',
            'address' => 'nullable|string|min:3|max:2500',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(null,$validator->errors(),400);
        }

        $branch = new Branch();
        $branch->name = $request->input('name');
        $branch->about = $request->input('about');
        $branch->address = $request->input('address');
        if($request->hasFile('image')){
            $image = $request->file('image');
            $filename = $image->getClientOriginalName();
            $image->move(public_path('/images/branch'),$filename);
            $branch->image = $filename;
        }
        $branch->save();

        if($branch){
            return $this->apiResponse(new BranchResource($branch),'The branch Save',201);
        }else{
            return $this->apiResponse(null,'The branch Not Save',400);
        }
    }

    

    
    public function update(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'max:255',
            'about' => 'nullable|string|min:3|max:2500',
            'image' => 'nullable|file|image|mimes:jpeg,jpg,png',
            'address' => 'nullable|string|min:3|max:2500',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(null,$validator->errors(),400);
        }
        $branch=Branch::find($id);
        
        if($branch){
            $branch->name = $request->input('name');
            $branch->about = $request->input('about');
            $branch->address = $request->input('address');
            
            if($request->hasFile('image')){
                File::delete(public_path('/images/branch/'.$branch->image));
                $image = $request->file('image');
                $filename = $image->getClientOriginalName();
                $request->image->move(public_path('/images/branch'),$filename);
                $branch->image = $filename;
            }
            $branch->save();
            return $this->apiResponse(new BranchResource($branch),'The branch update',201);
        }else{
            return $this->apiResponse(null,'The branch Not Found',404);
        }

    }

    
    public function destroy($id){

        $branch=Branch::find($id);

       

        if($branch){

            $branch->delete($id);
            File::delete(public_path('/images/branch/'.$branch->image));
            
            return $this->apiResponse(null,'The branch deleted',200);
        }else{
            return $this->apiResponse(null,'The branch Not Found',404);
        }

    }
}
