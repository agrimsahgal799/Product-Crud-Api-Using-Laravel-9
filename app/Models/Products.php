<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

    public function getProduct($id)
    {
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
        $option_data = $this->options->join('tbl_option_values', 'tbl_options.option_id', '=', 'tbl_option_values.option_id')
                    ->whereIn('tbl_options.option_id', $options)
                    ->get()->toArray();
        //print_r($option_data);

        if(!is_null($option_data))
        {
            $opn_array = [];  
            foreach($option_data as $opn_data){
                $opn_array[$opn_data['option_id']][] = $opn_data['option_value'];
            }

            $opn_array = $this->get_combinations($opn_array);
            // print_r($opn_array);
            if(count($opn_array)>0){
                $variants_names = [];
                foreach($opn_array as $opn_arr){
                    $variant_name = $product_name." ".implode(" ",$opn_arr);
                    $variant_slug = $this->generateSlug($variant_name);
                    $variants_names[$variant_slug] = $variant_name;
                }
                $variant_products = $this->validateVariantSlugs($variants_names);
            }   
        }
        return $variant_products;
    }

    public function get_combinations($arrays) {
        $result = array(array());
        foreach ($arrays as $property => $property_values) {
            $tmp = array();
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, array($property => $property_value));
                }
            }
            $result = $tmp;
        }
        return $result;
    }

    public function validateVariantSlugs($slug_array)
    {
        $response = [];
        foreach($slug_array as $slug => $name){
            $valid_slug = $this->validateSlug($slug);
            $response[$valid_slug] = $name;
        }
        return $response;
    }
}
