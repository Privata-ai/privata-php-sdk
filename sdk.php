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
        public function submitQuery($queries){
            global $idToken, $dbKey;

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

            $personalData = $this -> getPersonalDataParams($response);
            print_r($personalData);
            echo "<br>";
            echo "<br>";
            
            $objQuery = json_decode($queries, true);
            foreach ($objQuery as $query){
                if(preg_match("/\bFROM\b/i", $query["sql"], $offset, PREG_OFFSET_CAPTURE)){         //get table names from SQL query
                    $result = substr($query["sql"],$offset[0][1]+5);
                    $tableNames = preg_split("/[\s,]+/", $result);
                    print_r($tableNames);
                    echo "<br>";
                    echo "<br>";

                    foreach($tableNames as $queryTable){                            //search the array containing the names of tables with personal data, with the tables names found inside the SQL query
                        foreach($personalData as $table){
                            if($queryTable == $table["tableName"]){
                                echo "Match found";
                                echo "<br>";
                                echo "<br>";
                            }
                        }
                    }


                }
            }
            



            $url = $apiUrl."/databases"."/".$dbKey."/queries";
            try{
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $queries);
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

    }
    
?>