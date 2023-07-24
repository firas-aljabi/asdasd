<?php

namespace App\Http\Controllers;

use App\Exports\OrdersExport;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;
use App\Models\Ingredient;
use App\Models\OrderIngredient;
use App\Models\OrderProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;


class OrderController extends Controller
{
    use ApiResponseTrait;


    public function index()
    {
        $orders = OrderResource::collection(Order::get());
        return $this->apiResponse($orders,'success',200);
    }

    public function show($id){

        $order = Order::find($id);

        if($order){
            return $this->apiResponse(new OrderResource($order),'ok',200);
        }else{
            return $this->apiResponse(null,'The order Not Found',404);
        }
        

    }

    

public function store(Request $request)
{


    $v = $request->validate([
        'time' => 'date_format:H:i:s',
        'time_end' => 'date_format:H:i:s',
        'table_num' => 'required',
        'ingg' => 'nullable',
        'branch_id'=> 'exists:branches,id'
        
    ]);

    $order = new Order();
    $order->table_num = $v['table_num'];
    $order->branch_id = $v['branch_id'];
    $order->tax = 5;
    $order->time = Carbon::now()->format('H:i:s');
    $order->save();
    // Calculate the total price
    $totalPrice = 0;
    // Store the order's products
    if($request->products){
        foreach ($request->products as $productData) {

            $productId = $productData['product_id'];
            $quantity = $productData['quantity'];
              // Create a new order product instance
            $orderProduct = new OrderProduct();
            
            // Set the order product details
            $orderProduct->order_id = $order->id;
            $orderProduct->product_id = $productId;
            $orderProduct->quantity = $quantity;
            
            // Save the order product
            $orderProduct->save();
            // Retrieve the product price from the database
            $product = Product::find($productId);
            if($product){
                $productPrice = $product->price;
            }else{
                return $this->apiResponse(null, 'The product Not found', 400);
            }
            
            
            // Calculate the product subtotal
            $productSubtotal = $productPrice * $quantity;
            
            // Add the product subtotal to the total price
            $totalPrice += $productSubtotal;
            
        }
    }
    if($request->ingredients){
          // Store the order's ingredients
      foreach ($request->ingredients as $ingredientData) {
        $ingredientId = $ingredientData['ingredient_id'];
        $quantity = $ingredientData['quantity'];
        
        // Create a new order ingredient instance
        $orderIngredient = new OrderIngredient();
        
        // // Set the order ingredient details
        $orderIngredient->order_id = $order->id;
        $orderIngredient->ingredient_id = $ingredientId;
        $orderIngredient->quantity = $quantity;
        
        // // Save the order ingredient
        $orderIngredient->save();
        // Retrieve the ingredient price from the database
        $ingredient = Ingredient::find($ingredientId);
        if($ingredient){
            $ingredientPrice = $ingredient->price_by_piece;
        }else{
              return $this->apiResponse(null, 'The ingredient Not found', 400);
        }
        // Calculate the ingredient subtotal
        $ingredientSubtotal = $ingredientPrice * $quantity;
        
        // Add the ingredient subtotal to the total price
        $totalPrice += $ingredientSubtotal;
        
    }
    }
    
        // Add the tax to the total price
        $totalPrice += ($order->tax / 100);
        
        // Set the total price of the order
        $order->total_price = $totalPrice;
        
        // Save the order
        $order->save();
    if ($order) {
        return $this->apiResponse(new OrderResource($order->load(['products'])), 'The order Save', 201);
    }else{
        return $this->apiResponse(null, 'The order Not Save', 400);
    }
    
}



    public function update(Request $request ,$id){
        $validated = Validator::make($request->all(), [
            'status' => 'in:Preparing,Done',
            'table_num' => 'numeric|min:0',
            'tax' => 'nullable|numeric',
            'is_paid' => 'in:0,1',
            'total_price'=>'numeric',
        ]);

        if ($validated->fails()) {
            return $this->apiResponse(null,$validated->errors(),400);
        }


        $order=Order::find($id);

        if($order){
            
            $order->update($request->all());
            $order->save();
            $totalPrice = 0;
            
            if(isset($order->products)){
                OrderProduct::where('order_id',$id)->delete();
                $order->products()->sync($request->products);
                foreach ($order->products as $product) {
                    $totalPrice += ($product->price * $product->pivot->quantity);
                    
                }

            }
            if(isset($order->ingredients)){
                OrderIngredient::where('order_id',$id)->delete();
                $order->ingredients()->sync($request->ingredients);
                foreach ($order->ingredients as $ingredient) {
                    $totalPrice += ($ingredient->price_by_piece * $ingredient->pivot->quantity);
                }
            }
            
            
            
             // Add the tax to the total price
             $totalPrice += ($order->tax / 100);
            
            // Set the total price of the order
            $order->total_price = $totalPrice;
            
            // Save the order
            $order->save();
            
            return $this->apiResponse(new OrderResource($order->load(['products'])), 'The order saved', 201);
        }else{
            return $this->apiResponse(null,'The order not found',404);
        } 
   
    }

    public function destroy($id){

        $order=Order::find($id);

        $order->delete($id);

        if($order){
            return $this->apiResponse(null,'The order deleted',200);
        }else{
            return $this->apiResponse(null,'The order Not Found',404);
        }

    }

    public function peakTimes()
   {
   
    $peakHours = Order::select('time')->groupBy('time')->orderByRaw('COUNT(time) DESC')->first();
    if ($peakHours) {
        return $this->apiResponse($peakHours,'This time is peak time',200);
    } else {
        return $this->apiResponse(null,'No product has been requested yet',404);
    }
    
   }

    public function exportOrderReport(Request $request)
    {
        // $start_at = date($request->start_at);
        // $end_at = date($request->end_at);
        $start_at = $request->input('start_at');
        $end_at = $request->input('end_at');
        $orders = Order::whereBetween('created_at', [$start_at,$end_at])->get();

        return Excel::download(new OrdersExport($orders), 'orders.xlsx');

        if ($orders) {
            
            return $this->apiResponse($orders,'success',200);
        } else {
            return $this->apiResponse(null,'Not Found',404);
        }
    }
    
    public function readyOrder($id){

        $order = Order::where('id', $id)->first();
        $start_at = Carbon::parse($order->time);
        $end_at = Carbon::parse($order->time_end);
        $preparationTime = $end_at->diff($start_at)->format('%H:%i:%s');

        if($preparationTime){
            return $this->apiResponse($preparationTime,'Order preparation time',200);
        } else {
            return $this->apiResponse(null,'Not Found',404);
        }

    }

    public function getStatus($id){

        $order=Order::find($id);

        if($order){
          
            return $this->apiResponse($order->status, 'This order '.$order->status, 201);
        }else{
             return $this->apiResponse(null, 'Not Found', 400);
            
            }
    } 

    public function changeStatus($id){

        $order=Order::find($id);

        if($order){
            
            $order->update([
                'status' => 'Done',
                'time_end' => Carbon::now()->format('H:i:s'),
            ]);
            $order->save();
            return $this->apiResponse($order, 'Changes saved successfully', 201);
        }else{
             return $this->apiResponse(null, 'Changes are not saved', 400);
            
            }
    } 
    public function CheckPaid($id)
    {
        $order = Order::find($id);
        if($order){
            if($order->is_paid == 0){
                return $this->apiResponse($order->is_paid, 'This order Not Paid Yet', 201);
            }else{
                return $this->apiResponse($order->is_paid, 'This order is Paid ', 201);
            }
        }else{
            return $this->apiResponse(null, 'Not Found', 404);
        }
    }
    public function ChangePaid($id){

        $order = Order::find($id);

        if(!$order){
            return $this->apiResponse(null, 'Not Found', 404);
        }
        
        if($order->is_paid == '0'){
            $order->update([
                'is_paid' => '1',
            ]);
           
            $order->save();
            return $this->apiResponse($order->is_paid, ' Payment status changed successfully', 201);
            
        }else{
            return $this->apiResponse(null, 'Changes are not saved', 400);
        }
        
    }

    public function mostRequestedProduct(){

        $mostRequestedProduct =DB::table('products')
            ->leftJoin('orders_products', 'products.id', '=', 'orders_products.product_id')->select('products.name')
            ->groupBy('products.name')
            ->orderByRaw('COUNT(product_id) DESC')
            ->limit(5)
            ->get();
             
        if ($mostRequestedProduct) {
           
            return $this->apiResponse($mostRequestedProduct,'success',200);
           
                
        } else {
             return $this->apiResponse(null,'No product has been requested yet',404);
        }
    }

    public function leastRequestedProduct(){

        $leastRequestedProduct = DB::table('products')
        ->leftJoin('orders_products', 'products.id', '=', 'orders_products.product_id')->select('products.name')
        ->groupBy('products.name')
        ->orderByRaw('COUNT(product_id)')
        ->limit(5)
        ->get();

        if ($leastRequestedProduct) {
            return $this->apiResponse($leastRequestedProduct,'success',200);
        } else {
            return $this->apiResponse(null,'No product has been requested yet',404);
        }
    }
}

