<?php

namespace App\Http\Controllers;

use Request;
use File;

class FrontEndController extends Controller
{
    public function vote(){
        $vote = new \stdClass();
        $vote->cat_id = Request::get('cat_id');
        $vote->value = (Request::get('value')=='like')?1:-1;
        $vote->user_id = Request::get('user_id',null);
        $vote->auth_token = env('ISG_API_TOKEN');

        $vote->image_id = Request::get('cat_id');
        $vote->sub_id = Request::get('user_id',null);

        if(!is_null($vote->cat_id) and !is_null($vote->value) and !is_null($vote->user_id)){

            if(File::exists(storage_path('app/catapi-cats.json'))){
                $file = file_get_contents(storage_path('app/catapi-cats.json'));
                $cats_list = json_decode($file);
                $cat_ids = [];
                foreach($cats_list as &$cat){
                    if(isset($cat->id)){
                        $cat_ids[] = $cat->id;
                    }
                }
                if(in_array($vote->cat_id,$cat_ids)){
                    $g_response = $this->APIRequest('POST','vote', json_encode($vote));
                    if(in_array($g_response['code'],[200,201])){
                        return ['status' => 1];
                    } else {
                        $file = fopen(storage_path('app/isg-api-error-log.txt'), 'a');
                        fwrite($file, "Date: ".date('m/d/Y h:i:s a')." \n");
                        fwrite($file, "Sending Vote to CatAPI \n");
                        fwrite($file, 'Error Code: '.$g_response['code']);
                        fwrite($file, 'Error: '.$g_response['body']);
                        fclose($file);
                        return ['status' => 2];
                    }
                }
            }
        }
        return ['status' => 2];
    }

    public function getVotesByUserId(){
        if(Request::get('user_id')){

            $isg_request = new \stdClass();
            $isg_request->limit = Request::get('limit',1000);
            $isg_request->user_id = Request::get('user_id',null);
            $isg_request->auth_token = env('ISG_API_TOKEN');

            $response = $this->APIRequest('POST','my-votes',json_encode($isg_request));

            if(in_array($response['code'],[200,201])){
                $response_object = json_decode($response['body']);

                if(isset($response_object->my_votes)){
                    if(is_countable($response_object->my_votes)){
                        if(count($response_object->my_votes)>0){
                            return ['status' => 1, 'my_votes' => $response_object->my_votes];
                        }
                    }
                }
            }
        }

        return ['status' => 1, 'my_votes' => false];
    }

    public function getVotesSummary(){
        //Getting existent votes
        $isg_request = new \stdClass();
        $isg_request->limit = Request::get('limit',1000);
        $isg_request->auth_token = env('ISG_API_TOKEN');

        $response = $this->APIRequest('POST','votes-summary', json_encode($isg_request));

        if(in_array($response['code'],[200,201])){
            $isg_response = json_decode($response['body']);

            if(isset($isg_response->votes)){
                return ['status' => 1, 'votes' => $isg_response->votes];
            }
        }
        return ['status' => 1, 'votes' => []];
    }

    public function resetVotes(){
        $isg_request = new \stdClass();
        $isg_request->auth_token = env('ISG_API_TOKEN');

        $response = $this->APIRequest('POST','reset-votes', json_encode($isg_request));

        if(in_array($response['code'],[200,201])){
            $isg_response = json_decode($response['body']);

            if(isset($isg_response->status)){
                return ['status' => $isg_response->status];
            }
        }
        return ['status' => 2];
    }

    public function APIRequest($method, $endpoint, $body=null){
        $curl = curl_init();

        $request = [
            CURLOPT_URL => env('ISG_API_URL').$endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: '.env('CATAPI_TOKEN')
            ]];

        curl_setopt_array($curl, $request);
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return [
            'code' => $httpcode,
            'body' => $response
        ];
    }
}
