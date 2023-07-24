<?php

namespace App\Http\Controllers;

use App\Http\Resources\RatingResource;
use App\Models\Rating;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class RatingController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ratings = RatingResource::collection(Rating::get());
        return $this->apiResponse($ratings,'success',200);
    }


    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(null,$validator->errors(),400);
        }

        $rating = Rating::create($request->all());

        if($rating){
            return $this->apiResponse(new RatingResource($rating),'The rating Save',201);
        }

        return $this->apiResponse(null,'The product Not Save',400);
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Rating  $rating
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $rating = Rating::find($id);
        

        if($rating){
            return $this->apiResponse(new RatingResource($rating),'ok',200);
        }
        return $this->apiResponse(null,'The rating Not Found',404);
    }
    public function destroy($id){

        $rating=rating::find($id);

        if(!$rating){
            return $this->apiResponse(null,'The rating Not Found',404);
        }

        $rating->delete($id);

        if($rating){
            return $this->apiResponse(null,'The rating deleted',200);
        }

    }

    
    public function avgRating($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->apiResponse(null,'No product has been requested yet',404);
        }

        $average_rating = $product->ratings->avg('value');

        return response()->json(['data'=> round($average_rating),'message'=>'this rating from all user for this product'],200);
    }

    public function mostRatedProduct(){
        $mostRatedProduct = DB::table('products')
        ->join('ratings', 'products.id', '=', 'ratings.product_id')
        ->select('products.name', DB::raw('AVG(ratings.value) as average_rating'))
        ->groupBy('products.name')
        ->orderByDesc('average_rating')
        ->first();

        if($mostRatedProduct){
            return response()->json(['data'=> $mostRatedProduct,'message'=>'The most rated product '],200);
        }else{
            return $this->apiResponse(null,'No product has been Rated yet',404);
        }
    }

    public function leastRatedProduct(){
        $mostRatedProduct = DB::table('products')
        ->join('ratings', 'products.id', '=', 'ratings.product_id')
        ->select('products.name', DB::raw('AVG(ratings.value) as average_rating'))
        ->groupBy('products.name')
        ->orderBy('average_rating','ASC')
        ->first();

        if($mostRatedProduct){
            return response()->json(['data'=> $mostRatedProduct,'message'=>'The most rated product '],200);
        }else{
            return $this->apiResponse(null,'No product has been Rated yet',404);
        }
    }

}
