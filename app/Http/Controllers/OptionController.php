<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Options;

class OptionController extends Controller
{
    public function index()
    {
        $options = Options::orderBy('option_id','DESC')->get();
        return response()->json(['error'=>'false','data'=>$options]);
    }

    public function save(Request $request)
    {    
        $id = null;
        if($request->has('option_id') && $request->filled('option_id')){
            $id = $request->option_id;
            if(!is_numeric($id)){
                return apiResponse('Invalid option id.',true);
            }
            $option_data = Options::where(['option_id'=>$id])->first();
            if(!$option_data){
                return apiResponse('Invalid option id.',true);
            }
        }

        /* option types : swatch, list */

        $validator = Validator::make($request->all(), [
            'option_name' => 'required',
            'option_type' => [
                'required',
                function($attribute, $value, $fail){
                    if( !in_array($value, ['swatch','list']) ){
                        $fail('The '.lower($attribute).' value is invalid (must be swatch or list).');
                    }
                }
            ]
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return apiResponse($errors->all(),true);
        }

        $options = new Options();
        $options->updateOrInsert(
            ['option_id' => $id],
            [
            'option_name' => $request->option_name,
            'option_type' => $request->option_type,
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => !is_null($id) ? date("Y-m-d H:i:s") : null
            ]
        );

        if(is_null($id)){
            return apiResponse('Option added successfully.');
        }
        else{
            return apiResponse('Option updated successfully.');
        }
    }

    public function delete(Request $request)
    {
        $id = $request->option_id;
        if(!is_null($id)){
            $option_data = Options::where(['option_id'=>$id])->first();
            if($option_data){
                Options::where('option_id',$id)->delete();
                return apiResponse('Option deleted successfully.');
            }
            else{
                return apiResponse('Invalid option id.',true);    
            }
        }
        else{
            return apiResponse('Option id is required.',true);
        }
    }
}
