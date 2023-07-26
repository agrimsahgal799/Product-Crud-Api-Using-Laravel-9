<?php

if(!function_exists('apiResponse'))
{
    function apiResponse($data,$err = false){
        $error = 'false';
        if($err){
            $error = 'true';
        }
        return response()->json(['error'=>$error,'message'=>$data]);
    }
}

if( !function_exists('lower') ){
    function lower($str){
        $str = str_ireplace("_"," ",$str);
        $str = strtolower($str);
        return $str;
    }
}