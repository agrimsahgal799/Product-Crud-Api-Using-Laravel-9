<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Products;
use App\Models\ProductImages;
use App\Models\OptionSet;
use App\Models\Options;
use App\Models\ProductOptions;

class ProductController extends Controller
{
    public $product_obj;
    public function __construct(){
        $this->product_obj = new Products();
    }

    /**
     * get the product list [GET]
     * @return json_response
    */
    public function index()
    {
        $products = Products::select('*')->whereNotIn('id', ProductOptions::select('option_product_id'))
        ->where(['hide_from_shop'=>'no','status'=>'enable'])
        ->orderBy('id', 'DESC')
        ->get()->toArray();
        
        if(count($products)>0){
            $pids = [];
            foreach($products as $product){
                $pids[] = $product['id'];
            }
            $variants = Products::select('tbl_product.*','tbl_product_options.product_id','tbl_product_options.option_value_id')
            ->join('tbl_product_options','tbl_product.id', '=', 'tbl_product_options.option_product_id')
            ->whereNotIn('tbl_product.id', $pids)
            ->get()->toArray();
            if(count($variants)>0){
                $variant_products = [];
                foreach($variants as $v_product){
                    $variant_products[$v_product['product_id']][] = $v_product;
                }
            }   
            foreach($products as &$p){
                if(isset($variant_products[$p['id']])){
                    $p['variants'] = $variant_products[$p['id']];
                }
            } 
        }
        return response()->json(['error'=>'false','data'=>$products]);
    }

    /**
     * insert the product [POST]
     * Params -
     * name : required
     * slug : @if empty, then it will be created by name
     * description
     * price : required
     * inventory : @default 0
     * inventory_status (yes/no) : @default 'no'
     * option_set (option set id) : optional @If it doesn't pass, the product will be treated as a normal product without variation.
     * status (enable/disable) : @default 'enable'
     * Images : @array
     * There will be two ways to create images.
     * 1. By the selection using file input (should be array)
     * 2. By passing the base64-encoded URL (should be array)
     * @return json_response
    */
    public function save(Request $request)
    {
        /* set global variables and arrays */
        $product_payload = [];
        $variant_products = [];
        $product_images = [];
        $created_at = date("Y-m-d H:i:s");
        $updated_at = null;
        $description = null;
        $option_set = 0;
        $inventory = 0;
        $inventory_status = 'no';
        $status = 'enable';
        $product_name = $request->name;
        $product_slug = '';
        $price = $request->price;

        /* validate required fields */
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'price' => 'required|numeric|gte:1'    
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return apiResponse($errors->all(),true);
        }

        if($request->has('description')){
            $description = $request->filled('description') ? $request->description : null;
        }else{
            $description = null;
        }

        if($request->has('status')){
            if(!in_array($request->status, ['enable','disable'])){
                return apiResponse('Please enter valid product status (enable or disable).',true);
            }
            $status = $request->status;
        }
        else{
            $status = 'enable';
        }

        if($request->has('inventory')){
            $inventory = $request->inventory;
            if($request->filled('inventory')){
                if(!is_numeric($inventory)){
                    return apiResponse('Please enter valid inventory value in numbers.',true);
                }
            }
            else{
                $inventory = 0;
            }
            if($request->has('inventory_status')){
                if(!in_array($request->inventory_status, ['yes','no'])){
                    return apiResponse('Please enter inventory status (yes or no).',true);
                }
                $inventory_status = $request->inventory_status;
            }
            else{
                $inventory_status = 'no';
            }
        }
        else{
            $inventory = 0;
            $inventory_status = 'no';
        }
        
        /* create product slug */
        $slug = $product_name;
        if($request->has('slug')){
            if($request->filled('slug')){
                $slug = $request->slug;
            }
        }
        $product_slug = $this->product_obj->generateSlug($slug);
        /* validate slug from exist slugs in database */
        $product_slug = $this->product_obj->validateSlug($product_slug);
        
        /* process Gererate product variant products (attribute products) by option_set id */
        if($request->has('option_set'))
        {
            if($request->filled('option_set'))
            {
                $option_set = $request->option_set;
                /* validate option set */
                $valid_option_set = OptionSet::where('option_set_id', $option_set)->get()->first();
                if(!is_null($valid_option_set))
                {
                    /* Gererate product variant products (attribute products) */
                    $options = $valid_option_set->option_id;
                    $variants = $this->product_obj->generateVariants($options,$product_name);
                    if(count($variants)>0){
                        foreach($variants as $v_data){
                            $variant_products[] = [
                                'name' => $v_data['name'],
                                'slug' => $v_data['slug'],
                                'value_id' => $v_data['value_id'],
                                'description' => $description,
                                'price' => $price,
                                'inventory' => $inventory,
                                'inventory_status' => $inventory_status,
                                'option_set' => 0,
                                'hide_from_shop' => 'yes',
                                'status' => $status
                            ];
                        }
                    }
                }
                else{
                    return apiResponse('Please enter valid option set id.',true);
                }
            }
            else{
                $option_set = 0;
            }
        }
        else{
            $option_set = 0;
        }

        $product_payload = [
            'name' => $product_name,
            'slug' => $product_slug,
            'description' => $description,
            'price' => $price,
            'inventory' => $inventory,
            'inventory_status' => $inventory_status,
            'option_set' => $option_set,
            'hide_from_shop' => 'no',
            'status' => $status,
            'created_at' => $created_at,
            'updated_at' => $updated_at
        ];
        
        if($request->file('images')){
            $product_images = $this->product_obj->uploadImages($request->images,'file_input');
        }
        elseif($request->has('images') && is_array($request->images) && count($request->images)>0){    
            $product_images = $this->product_obj->uploadImages($request->images);
        }
        if(isset($product_images['error'])){
            return apiResponse($product_images['message'],true);
        }

        $products = new Products();
        $main_product_id = $products->insertGetId($product_payload);
        
        if(count($variant_products)>0){
            foreach($variant_products as $v_product){

                $variant_product_id = $products->insertGetId([
                    'name' => $v_product['name'],
                    'slug' => $v_product['slug'],
                    'description' => $v_product['description'],
                    'price' => $v_product['price'],
                    'inventory' => $v_product['inventory'],
                    'inventory_status' => $v_product['inventory_status'],
                    'option_set' => $v_product['option_set'],
                    'hide_from_shop' => $v_product['hide_from_shop'],
                    'status' => $v_product['status'],
                    'created_at' => $created_at,
                    'updated_at' => $updated_at
                ]);
                $product_options = new ProductOptions();
                $product_options->insert([
                    'product_id' => $main_product_id,
                    'option_product_id' => $variant_product_id,
                    'option_value_id' => $v_product['value_id'],
                    'created_at' => $created_at,
                    'updated_at' => $updated_at
                ]);
            }
        }

        if(count($product_images)>0){
            foreach($product_images as &$p_imgs){
                $p_imgs['product_id'] = $main_product_id;
            }
            $images = new ProductImages();
            $images->insert($product_images);
        }

        return apiResponse('Product created successfully.');
    }
}
