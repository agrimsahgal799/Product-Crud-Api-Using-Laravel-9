<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use App\Models\Products;
use App\Models\OptionSet;

class ProductController extends Controller
{
    public $product_obj;
    public function __construct()
    {
        $this->product_obj = new Products();
    }

    public function index()
    {
        $products = Products::orderBy('id','DESC')->get();
        return response()->json(['error'=>'false','data'=>$products]);
    }

    public function save(Request $request)
    {
        echo '<pre>';
        print_r($request->all());

        $product_payload = [];
        $variant_products = [];
        $option_set = 0;
        $inventory_status = 'no';
        $inventory = 0;
        $status = 'enable';
        $product_name = $request->name;
        $price = $request->price;
        $description = null;

        $upload = [];
        if($request->file('images')){
            $images = $request->file('images');
            $valid_img = false;
            foreach($images as $img){
                $original_name = $img->getClientOriginalName();
                $name = $img->hashName();
                $path = $img->path();
                $extension = $img->extension();
                $is_img = $this->product_obj->isImageFile($extension);
                if($is_img){
                    $valid_img = true;
                }
                $upload[] = [
                    'name' => $name,
                    'path' => $path
                ];
            }
            if(!$valid_img){
                return apiResponse('Please select all valid images.',true);
            }
        }
        elseif($request->has('images') && is_array($request->images) && count($request->images)>0){
            
            $images = $request->images;
            $valid_img = false;
            foreach($images as $img){
                
                $decodedImg = base64_decode(trim($img));
                if(base64_encode(base64_decode($img, true)) === $img){   
                    $decodedImg = base64_decode($img, true);
                    $valid_img = true;
                    $upload[] = [
                        'name' => '',
                        'path' => $decodedImg
                    ];
                }
            }
        }

        if(count($upload)>0){

            foreach($upload as $upload_file){
                //Storage::putFile('product_images', new File('/path/to/photo'), 'public');

                $path = Storage::putFileAs('public/product_images', new File($upload_file['path']), $upload_file['name']);
                echo $path;
                echo '<br>';
            }

            
        }

        echo '<pre>';print_r($upload);

        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'price' => 'required|numeric|gte:1'    
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return apiResponse($errors->all(),true);
        }

        $product_data = [];
        $id = null;
        if($request->has('id') && $request->filled('id')){
            $id = $request->id;
            if(!is_numeric($id)){
                return apiResponse('Invalid product id.',true);
            }
            $product_data = $this->product_obj->getProduct($id);
            if(!$product_data){
                return apiResponse('Invalid option id.',true);
            }
        }

        if($request->has('description')){
            $description = $request->filled('description') ? $request->description : null;
        }else{
            $description = isset($product_data->description) ? $product_data->description : null;
        }

        if($request->has('status')){
            if(!in_array($request->status, ['enable','disable'])){
                return apiResponse('Please enter valid product status (enable or disable).',true);
            }
            $status = $request->status;
        }
        else{
            $status = isset($product_data->status) ? $product_data->status : 'enable';
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
            $inventory = isset($product_data->inventory) ? $product_data->inventory : 0;
            $inventory_status = isset($product_data->inventory_status) ? $product_data->inventory_status : 'no';
        }
        
        /* let's create product slug */
        $slug = $product_name;
        if($request->has('slug')){
            if($request->filled('slug')){
                $slug = $request->slug;
            }
        }

        $product_slug = $this->product_obj->generateSlug($slug);

        /* validate slug from exist slugs in database */
        $product_slug = $this->product_obj->validateSlug($product_slug);

        if($request->has('option_set'))
        {
            if($request->filled('option_set'))
            {
                $option_set = $request->option_set;
                /* validate option set */
                $valid_option_set = OptionSet::where('option_set_id', $option_set)->get()->first();
                if(!is_null($valid_option_set))
                {
                    $options = $valid_option_set->option_id;

                    /* Gererate product variant products (attribute products) */
                    $variants = $this->product_obj->generateVariants($options,$product_name);
                    if(count($variants)>0){
                        foreach($variants as $v_slug => $v_name){
                            $variant_products[] = [
                                'name' => $v_name,
                                'slug' => $v_slug,
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
            $option_set = $product_data->option_set;
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
            'status' => $status
        ];
        $product_payload = array_merge(array($product_payload), $variant_products);
        print_r($product_payload);
        die();

        $products = new Products();
        if(!is_null($id)){
            
        }
        else{
            $products->insert($product_payload);
            return apiResponse('Products added successfully.');
        }
    }
}
