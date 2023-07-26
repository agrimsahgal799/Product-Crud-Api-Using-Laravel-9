<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\OptionSet;
use App\Models\Options;

class OptionSetController extends Controller
{
    public function index()
    {
        $option_set = OptionSet::orderBy('option_set_id','DESC')->get();
        return response()->json(['error'=>'false','data'=>$option_set]);
    }

    public function save(Request $request)
    {
        // echo '<pre>';
        // print_r($request->all());

        $id = null;
        if($request->has('option_set_id') && request->filled('option_set_id')){
            $id = $request->option_set_id;
            if(!is_numeric($id)){
                return apiResponse('Invalid option set id.',true);
            }
            $option_set_data = OptionSet::where(['option_set_id'=>$id])->first();
            if(!$option_set_data){
                return apiResponse('Invalid option set id.',true);
            }
        }

        $valid_options = false;
        $option_id_str = '';
        if($request->has('option_id') && is_array($request->option_id) && count($request->option_id)>0){
            $options = $request->option_id;
            $all_options = Options::whereIn('option_id', $options)->get();
            if(count($all_options)>0){
                $opn_ids = [];
                foreach($all_options as $option_ids){
                    $opn_ids[] = $option_ids->option_id;
                }
                $option_id_str = implode(",",$opn_ids);
                $valid_options = true;
            }
        }

        if(!$valid_options){
            return apiResponse('Invalid option ids.',true);
        }

        $validator = Validator::make($request->all(),[
            'option_set_name' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return apiResponse($errors->all(),true);
        }

        $optionSet = new OptionSet();
        $optionSet->updateOrInsert(
            ['option_set_id' => $id],
            [
            'option_id' => $option_id_str,
            'option_set_name' => $request->option_set_name,
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => !is_null($id) ? date("Y-m-d H:i:s") : null
            ]
        );

        if(is_null($id)){
            return apiResponse('Option set added successfully.');
        }
        else{
            return apiResponse('Option set updated successfully.');
        }
    }

    public function delete(Request $request)
    {
        $id = $request->option_set_id;
        if(!is_null($id)){
            $option_set_data = OptionSet::where(['option_set_id'=>$id])->first();
            if($option_set_data){
                OptionSet::where('option_set_id',$id)->delete();
                return apiResponse('Option set deleted successfully.');
            }
            else{
                return apiResponse('Invalid option set id.',true);    
            }
        }
        else{
            return apiResponse('Option set id is required.',true);
        }
    }
}
