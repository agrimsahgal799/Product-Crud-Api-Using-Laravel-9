<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use Illuminate\Support\Str;
use App\Models\Options;

class Products extends Model
{
    use HasFactory;
    protected $table = 'tbl_product';
    protected $fillable = ['name','slug','description','inventory','inventory_status','option_set','hide_from_shop','status','created_at','updated_at'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $db;
    protected $options;
    public function __construct(){
        $this->db = DB::table('tbl_product');
        $this->options = new Options;
    }

    public function getProduct($id){
        return $this->db->where(['id'=>$id])->first();
    }

    public function generateSlug($string)
    {
        $options = [
            'delimiter' => '-',
            'lowercase' => true
        ];
        
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $string = mb_convert_encoding((string)$string, 'UTF-8', mb_list_encodings());

        // Replace non-alphanumeric characters with our delimiter
        $string = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $string);

        // Remove duplicate delimiters
        $string = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $string);

        // Remove delimiter from ends
        $string = trim($string, $options['delimiter']);

        return $options['lowercase'] ? mb_strtolower($string, 'UTF-8') : $string;
    }

    public function validateSlug($slug)
    {
        $result = $this->db->where('slug', 'like', "$slug%")
                ->orWhere('slug', $slug)
                ->get();
        if(count($result)>0)
        {
            $match_slug = [];
            foreach($result as $value){
                $match_slug[$value->slug] = $value->slug;
            }
            
            $i = false;
            $j = 0;
            while ($i == false)
            {    
                if($j == 0){
                    $check_slug = $slug;
                }else{
                    $check_slug = $slug.'-'.$j;
                }   
                if(!isset($match_slug[$check_slug])){
                    $new_slug = $check_slug;
                    $i = true;
                }
                $j++;
            }
            $response_slug = $new_slug;
        }
        else{
            $response_slug = $slug;
        }
        return $response_slug;
    }

    public function generateVariants($options, $product_name)
    {   
        $variant_products = [];
        $options = explode(",", $options);
        $option_data = $this->options->select('tbl_option_values.id','tbl_options.option_id','tbl_options.option_name','tbl_options.option_type','tbl_option_values.option_value','tbl_option_values.color_code')
                    ->join('tbl_option_values', 'tbl_options.option_id', '=', 'tbl_option_values.option_id')
                    ->whereIn('tbl_options.option_id', $options)
                    ->get()->toArray();
        if(!is_null($option_data))
        {
            $opn_array = [];  
            foreach($option_data as $opn_data){
                $opn_array[$opn_data['option_id']][$opn_data['id']] = $opn_data['option_value'];
            }
            
            $opn_array = $this->get_combinations($opn_array);            
            if(count($opn_array)>0)
            {
                $variants_names = [];
                foreach($opn_array as $opn_arr)
                {
                    $ids = [];
                    $values = [];
                    foreach($opn_arr as $op){
                        $ids[] = $op['id'];
                        $values[] = $op['value'];
                    }
                    $value_id = implode("," ,$ids);
                    $variant_name = $product_name." ".implode(" ",$values);
                    $variant_slug = $this->generateSlug($variant_name);
                    $variant_slug = $this->validateSlug($variant_slug);
                    $variants_names[] = [
                        'value_id' => $value_id,
                        'name' => $variant_name,
                        'slug' => $variant_slug
                    ];
                }
                $variant_products = $variants_names;
            }   
        }
        return $variant_products;
    }

    public function get_combinations($arrays) {
        $result = array(array());
        foreach ($arrays as $property => $property_values) {
            $tmp = array();
            foreach ($result as $result_item) {
                foreach ($property_values as $property_key => $property_value) {
                    $tmp[] = array_merge($result_item, array($property => array('id'=>$property_key,'value'=>$property_value)));   
                }
            }
            $result = $tmp;
        }
        return $result;
    }

    public function isImageFile($ext){
        $extensions = ['gif','jpg','jpeg','png','psd','bmp','tiff','svg'];
        if(in_array($ext, $extensions)){
            return true;
        }
        return false;
    }

    public function uploadImages($images, $type = false)
    {
        $product_images = [];
        if($type == 'file_input')
        {
            $valid_img = [];
            $upload = [];
            foreach($images as $img){
                if(!is_null($img)){
                    $original_name = $img->getClientOriginalName();
                    $name = $img->hashName();
                    $path = $img->path();
                    $extension = $img->extension();
                    $is_img = $this->isImageFile($extension);
                    if($is_img){
                        $valid_img[] = 'true';
                        $upload[] = [
                            'name' => $name,
                            'path' => $path
                        ];
                    }
                    else{
                        $valid_img[] = 'false';
                    }
                }
            }

            if(in_array('false', $valid_img)){
                return $product_images[] = ['error'=>true,'message'=>'Please select all valid images.'];
            }
            if(count($upload)>0){
                foreach($upload as $upload_file){
                    $path = Storage::putFileAs('public/product_images', $upload_file['path'], $upload_file['name']);
                    if($path){
                        $product_images[] = [
                            'name' => $upload_file['name'],
                            'path' => $path
                        ];
                    }
                }
            }
        }
        else
        {
            $valid_img = [];
            $upload = [];
            foreach($images as $img){
                if(!is_null($img))
                {
                    if( stripos($img, "base64,") !== false ){
                        $base64Image = explode(";base64,", $img);
                        $image_base64 = base64_decode($base64Image[1]);
                        $valid_img[] = 'true';
                    }
                    elseif(base64_encode(base64_decode($img, true)) === $img){   
                        $image_base64 = base64_decode($img, true);
                        $valid_img[] = 'true';
                    }
                    else{
                        $valid_img[] = 'false';
                    }
                    if($valid_img){
                        $upload[] = [
                            'image' => $image_base64
                        ];
                    }
                }
            }
            if(in_array('false', $valid_img)){
                return $product_images[] = ['error'=>true,'message'=>'Please enter valid base64 image path.'];
            }
            if(count($upload)>0){
                foreach($upload as $upload_file){

                    $randomString = Str::random(30);
                    $folder_path = storage_path('app/public/product_images/');
                    $file = $randomString.'.jpg';
                    $file_path = $folder_path.$file.'.jpg';
                    $image = $upload_file['image'];
                    file_put_contents($file_path, $image);
                    $product_images[] = [
                        'name' => $file,
                        'path' => 'public/product_images/'.$file
                    ];
                }
            }
        }

        return $product_images;
    }
}
