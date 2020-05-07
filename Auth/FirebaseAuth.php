<?php

    /** 
     * Class responsible for session management
     * 
    */
    class FirebaseAuth  {
        private $dbKey = '';
        private $idToken = '';
        private $refreshToken ='';
        private $expiryTime = '';
        private $errorMessage = '';

        
        /**
         * Authentication
         * 
         * Signs in the user using his dbKey and dbSecret
         * 
         * Fails with an error if the dbKey or dbSecret do not match
         */
        public function auth($dbKeyUser, $dbSecret){
            global $dbKey;

            define("api_key", 'AIzaSyDO-JXjcrO9x5sSX30mLQdSpu3_r7yI9gY', true);
            $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key='.api_key.'';

            $dbKey = $dbKeyUser;
            $localdbKey =  $dbKeyUser.'@blockbird.ventures';
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
                    global $idToken, $refreshToken, $expiryTime;
                    $idToken = $obj['idToken'];
                    $refreshToken = $obj['refreshToken'];
                    $expiryTime = time() + intval($obj['expiresIn']);
                    return 200;                                     //query has been submited successfully
                }
                else if (isset($obj["error"]["message"])) {         //Checks in order to detect an unsuccessful submit request
                    global $errorMessage;
                    $errorMessage = "Credentials are invalid";
                    return $errorMessage;
                }
            } else {
                return(http_response_code());
            }
        }

        /**
         * Refresh session token
         * 
         * Refreshes the idToken which is used to perform requests to firebase
         * 
         * Fails with an error message if something went wrong
         */
        private function refreshToken(){
            global $refreshToken;
            
            $api_key = 'AIzaSyDO-JXjcrO9x5sSX30mLQdSpu3_r7yI9gY';
            $url = 'https://securetoken.googleapis.com/v1/token?key='.$api_key.'';
            $dataPayload = [
                'grant_type'=> "refresh_token",
                'refresh_token' => $refreshToken,
                'returnSecureToken' => true
            ];

            try{
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($dataPayload));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);
            } catch( Exception $e){
                return($e->getMessage());
            }


            if(http_response_code() == 200){
                $obj = json_decode($response, true);                //convert json to array
                if (isset ($obj['id_token'])){                       
                    global $idToken, $refreshToken, $expiryTime;
                    $idToken = $obj['id_token'];
                    $refreshToken = $obj['refresh_token'];
                    $expiryTime = time() + intval($obj['expires_in']);
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
         * Verify the IdToken
         * 
         * Verifies if the idToken is still valid and calls the refresh function if it is not.
         * 
         * Fails with the error message sent from the refresh function
         */
        public function verifyIdToken(){
            global $idToken, $expiryTime;
            $curtime = time();
            
            if($curtime < $expiryTime){
                $response = $this -> refreshToken();
                if ($response !== 200){
                    $response = [
                        'code'=> 400,
                        'idToken' => "",
                        'error' => $response
                    ];

                    return $response;
                }
            }

            $response = [
                'code'=> 200,
                'idToken' => $idToken,
                'error' => ""
            ];

            return $response;
        }
        
    }
    
?>