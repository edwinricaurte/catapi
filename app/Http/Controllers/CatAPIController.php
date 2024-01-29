<?php

namespace App\Http\Controllers;

use Request;
use File;

class CatAPIController extends Controller
{
    public function home(){

        if(!File::exists(storage_path('app/catapi-cats.json'))){
            $this->createCatAPIVoting();
        }
        $file = file_get_contents(storage_path('app/catapi-cats.json'));
        $cats_list = json_decode($file);

        foreach($cats_list as &$cat){
            if(isset($cat->breeds[0])){
                $cat->breeds = $cat->breeds[0];
            }
        }

        return View('home')->with('cats_list',$cats_list);
    }

    public function catAPIRequest($method, $endpoint, $body=null){
        $curl = curl_init();

        $request = [
            CURLOPT_URL => 'https://api.thecatapi.com/v1/'.$endpoint,
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
    public function createCatAPIVoting() {

        //Getting new cats
        $g_response = $this->catAPIRequest('GET','images/search?has_breeds=true&order=RANDOM&limit=6');

        if(in_array($g_response['code'],[200,201])){
            if(File::exists(storage_path('app/catapi-cats.json'))){
                File::delete(storage_path('app/catapi-cats.json'));
            }

            $file = fopen(storage_path('app/catapi-cats.json'), 'a');
            fwrite($file, $g_response['body']);
            fclose($file);
        } else {
            $file = fopen(storage_path('app/catapi-error-log.txt'), 'a');
            fwrite($file, "Date: ".date('m/d/Y h:i:s a')." \n");
            fwrite($file, "Getting cats: \n");
            fwrite($file, 'Error Code: '.$g_response['code']);
            fclose($file);
        }

        //Getting existent votes to delete
        $response = $this->catAPIRequest('GET','votes?limit=100');

        if(in_array($response['code'],[200,201])){
            $existent_votes = json_decode($response['body']);

            if(is_countable($existent_votes)){
                if(count($existent_votes)>0){
                    foreach($existent_votes as $vote){
                        // Deleting Vote
                        $d_response = $this->catAPIRequest('DELETE','votes/'.$vote->id);
                        if(!in_array($response['code'],[200,201])){
                            $file = fopen(storage_path('app/catapi-error-log.txt'), 'a');
                            fwrite($file, "Date: ".date('m/d/Y h:i:s a')." \n");
                            fwrite($file, "Deleting vote #$vote->id \n");
                            fwrite($file, 'Error Code: '.$g_response['code']);
                            fclose($file);
                        }
                    }
                }
            }
        }
        return ['status' => 1];
    }

    public function vote(){
        $vote = new \stdClass();
        $vote->cat_id = Request::get('cat_id');
        $vote->value = (Request::get('value')=='like')?1:-1;
        $vote->user_id = Request::get('user_id',null);

        if(!is_null($vote->cat_id) and !is_null($vote->value) and !is_null($vote->user_id)){
            $g_response = $this->catAPIRequest('POST','votes', json_encode(['image_id' => $vote->cat_id, 'sub_id' => $vote->user_id, 'value' => $vote->value]));

            if(in_array($g_response['code'],[200,201])){
                return ['status' => 1];
            } else {
                $file = fopen(storage_path('app/catapi-error-log.txt'), 'a');
                fwrite($file, "Date: ".date('m/d/Y h:i:s a')." \n");
                fwrite($file, "Adding a Vote \n");
                fwrite($file, 'Error Code: '.$g_response['code']);
                fwrite($file, 'Error: '.$g_response['body']);
                fclose($file);
                dd($g_response);
                return ['status' => 2];
            }
        }
        return ['status' => 2];
    }

    public function getVotesByUserId(){
        //Getting existent votes
        if(Request::get('user_id')){
            $response = $this->catAPIRequest('GET','votes?limit=1000&sub_id='.Request::get('user_id'));

            if(in_array($response['code'],[200,201])){
                $my_votes = json_decode($response['body']);

                if(is_countable($my_votes)){
                    if(count($my_votes)>0){
                        return ['status' => 1, 'my_votes' => $my_votes];
                    }
                }
            }
        }

        return ['status' => 1, 'my_votes' => false];
    }

    public function getVotesSummary(){
        //Getting existent votes
        $response = $this->catAPIRequest('GET','votes?limit=1000');

        if(in_array($response['code'],[200,201])){
            $votes = json_decode($response['body']);

            if(is_countable($votes)){
                if(count($votes)>0){
                    $response_object = [];
                    foreach($votes as $vote){

                        $key = array_search($vote->image_id, array_column($response_object, 'cat_id'));
                        if(is_bool($key)){
                            if($vote->value == 1){
                                $response_object[] = ['cat_id' => $vote->image_id, 'likes' => 1, 'dislikes' => 0];
                            } else {
                                $response_object[] = ['cat_id' => $vote->image_id, 'likes' => 0, 'dislikes' => 1];
                            }
                        } else {
                            if($vote->value == 1){
                                $response_object[$key]['likes'] ++;
                            } else {
                                $response_object[$key]['dislikes'] ++;
                            }
                        }

                    }
                    return ['status' => 1, 'votes' => $response_object];
                }
            }
        }
    }

}
