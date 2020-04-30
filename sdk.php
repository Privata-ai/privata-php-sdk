<?php

    /** 
     * Class with an initialize function and query submition function
     * 
    */
    class PrivataAudit {

        protected $dbKey = '';
        protected $idToken = '';
        protected $errorMessage = '';


        /**
         * Initialize
         * 
         * Signs in the user using his dbKey and dbSecret
         * 
         * Fails with an error if the dbKey or dbSecret do not match
         */
        public function initialize($dbKeyuser, $dbSecret){
            global $dbKey;

            $api_key = 'AIzaSyDO-JXjcrO9x5sSX30mLQdSpu3_r7yI9gY';
            $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key='.$api_key.'';

            $dbKey = $dbKeyuser;
            $localdbKey =  $dbKeyuser.'@blockbird.ventures';
            $data = [
                'email'=> $localdbKey,
                'password' => $dbSecret,
                'returnSecureToken' => true
            ];

            try{
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);
            } catch( Exception $e){
                return($e->getMessage());
            }

            if(http_response_code() == 200){
                $obj = json_decode($response, true);                //convert json to array
                if (isset ($obj['idToken'])){                       
                    global $idToken;
                    $idToken = $obj['idToken'];
                    return 200;                                     //query has been submited successfully
                }
                else if (isset($obj["error"]["message"])) {         //Checks in order to detect an unsuccessful submit request
                    global $errorMessage;
                    $errorMessage = $obj["error"]["message"];
                    return $errorMessage;
                }
            } else {
                return(http_response_code());
            }
        }


        /**
         * Submit Queries
         * 
         * Connects to the database and submits user queries in json format
         * 
         * Fails with an error message should a request fail.
         */
        public function submitQuery($query){
            global $idToken, $dbKey;

            $apiUrl = 'https://api-sandbox.privata.ai';
            $headers = array(
                "Content-Type: application/json",
                "Authorization: Bearer ".$idToken
            );
            $url = $apiUrl."/databases"."/".$dbKey."/queries";

            try{
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);
            } catch( Exception $e){
                return($e->getMessage());
            }

            if(http_response_code() == 200){
                $obj = json_decode($response, true);
                if (isset($obj["error"])) {                             //Checks in order to detect an unsuccessful submit request
                    if(isset($obj["message"])){
                        global $errorMessage;
                        $errorMessage = $obj["message"];
                        return $errorMessage;
                    } else {
                        global $errorMessage;
                        $errorMessage = $obj["error"];
                        return $errorMessage;
                    }
                } else{
                    return 200;                                         //query has been submited successfully
                }
            }else {
                return(http_response_code());
            }
        }

    }
    
?>