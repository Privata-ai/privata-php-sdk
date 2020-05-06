<?php
    
    /** 
     * Class with an initialize function and query submition function
     * 
    */
    class PrivataAudit {

        protected $dbKey = '';
        protected $idToken = '';
        protected $refreshToken ='';
        protected $expiresIn = '';
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
                    global $idToken, $refreshToken, $expiresIn;
                    $idToken = $obj['idToken'];
                    $refreshToken = $obj['refreshToken'];
                    $expiresIn = $obj['expiresIn'];
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
        public function submitQuery($queries){
            global $idToken, $dbKey, $refreshToken;


            $this -> refreshToken($refreshToken);            
            $apiUrl = 'https://api-sandbox.privata.ai';
            $headers = array(
                "Content-Type: application/json",
                "Authorization: Bearer ".$idToken
            );


            $url = $apiUrl."/databases"."/".$dbKey;

            
            try{
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);
            } catch( Exception $e){
                return($e->getMessage());
            }

            $objQuery = json_decode($queries, true);
            $personalData = $this -> getPersonalDataParams($response);            
            $filteredQueryArray = $this -> filterQuery($objQuery, $personalData);       // filter the query submited in order to only send relevant data
            $filteredJson = json_encode($filteredQueryArray);

            $url = $apiUrl."/databases"."/".$dbKey."/queries";

            try{
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $filteredJson);
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

        private function getPersonalDataParams($response){
            $personalData=[];
            $index = 0;
            $obj = json_decode($response, true);
            foreach ($obj["tables"] as $table){
                if($table["hasPersonalData"]){
                    array_push($personalData, array(
                        "tableName" => $table["name"],
                        "columns" => [],
                        )
                    );
                    foreach($table["columns"] as $column){
                        if($column["isPersonalData"]){
                            array_push($personalData[$index]["columns"], $column["name"]);
                        }
                    }
                    $index++;
                }
            }
            return $personalData;
        }
        
        private function filterQuery($objQuery, $personalData){
            $filteredQueryArray = [];

            foreach($objQuery as $query){                                            //go query by query
                $filteredQuery=[];
                foreach($query["tables"] as $queryTable){                            //search the array containing the tables' names with personal data, with the tables names found inside the SQL query
                    foreach($personalData as $tableWithPersonalData){
                        if($queryTable["table"] == $tableWithPersonalData["tableName"]){
                            
                            $columnsWithPersonalData = [];
                            foreach($queryTable["columns"] as $queryColumn){
                                foreach($tableWithPersonalData["columns"]  as $columnWithPersonalData)
                                    if(!strcasecmp($queryColumn,$columnWithPersonalData)){
                                        array_push($columnsWithPersonalData, $queryColumn);
                                    }
                            }
                            if(!empty($columnsWithPersonalData)){
                                array_push($filteredQuery, array(
                                    "table" => $queryTable["table"],
                                    "columns" => $columnsWithPersonalData,
                                    )
                                );
                            }
                        }
                    }
                }
                if(!empty($filteredQuery)){
                    array_push($filteredQueryArray, array(
                        "tables" => $filteredQuery,
                        "action" => "Read",
                        "timestamp" => $query["timestamp"],
                        "user" => $query["user"],
                        "group" => $query["group"],
                        "returnedRows" => $query["returnedRows"],
                        )
                    );
                }
            }
            return $filteredQueryArray;
        }

        private function refreshToken($refreshToken){

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
                    global $idToken, $refreshToken, $expiresIn;
                    print_r($obj);
                    $idToken = $obj['id_token'];
                    $refreshToken = $obj['refresh_token'];
                    $expiresIn = $obj['expires_in'];
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

    }
    
?>