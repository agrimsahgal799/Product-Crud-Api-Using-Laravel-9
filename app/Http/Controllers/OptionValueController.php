<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\OptionValues;
use App\Models\Options;

class OptionValueController extends Controller
{
     /**
     * get the option values list [GET]
     * @return json_response
    */
    public function index(){
        $option_values = OptionValues::orderBy('id','DESC')->get();
        return response()->json(['error'=>'false','data'=>$option_values]);
    }

    /**
     * insert/update the option values.
     * Params -
     * id (for update the option value) : optional
     * option_id : required
     * option_value : required
     * color_code : required (if the related option type is a swatch)
     * @return json response
    */
    public function save(Request $request){
        
        $id = null;
        if($request->has('id') && $request->filled('id')){
            $id = $request->id;
            if(!is_numeric($id)){
                return apiResponse('Invalid option value id.',true);
            }
            $option_value_data = OptionValues::where(['id'=>$id])->first();
            if(!$option_value_data){
                return apiResponse('Invalid option value id.',true);
            }
        }

        $validator = Validator::make($request->all(), [
            'option_id' => 'required',
            'option_value' => 'required',
        ]);
        
        if ($validator->fails()) {
            $errors = $validator->errors();
            return apiResponse($errors->all(),true);
        }
    
        $option = Options::where('option_id',$request->option_id)->first();
        if($option){
            if($option->option_type == 'swatch' && is_null($request->color_code)){
                return apiResponse('Color code is required.',true);
            }
        }
        else{
            return apiResponse('Invalid option id.',true);
        }
    
        $optionValues = new OptionValues();
        $optionValues->updateOrInsert(
            ['id' => $id],
            [
            'option_id' => $request->option_id,
            'option_value' => $request->option_value,
            'color_code' => $option->option_type == 'swatch' ? $request->color_code : null,
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => !is_null($id) ? date("Y-m-d H:i:s") : null
            ]
        );

        if(is_null($id)){
            return apiResponse('Option value created successfully.');
        }
        else{
            return apiResponse('Option value updated successfully.');
        }
    }

    /**
     * delete the product option value [POST]
     * Params -
     * id : required
     * @return json response
    */
    public function delete(Request $request){
        $id = $request->id;
        if(!is_null($id)){
            $option_value_data = OptionValues::where(['id'=>$id])->first();
            if($option_value_data){
                OptionValues::where('id',$id)->delete();
                return apiResponse('Option value deleted successfully.');
            }
            else{
                return apiResponse('Invalid option value id.',true);    
            }
        }
        else{
            return apiResponse('Option value id is required.',true);
        }
    }
}
