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
            $objQuery = json_decode($queries, true);

            if (isset($objQuery[0]["sql"])){                                                //case where a SQL json query was submited
                $objQuery = $this -> parseSQLQuery($objQuery);
            }
            
            if(isset($objQuery[0]["tables"])){
                $filteredQueryArray = $this -> filterJson($objQuery, $personalData);       // case where a table json was submited
                echo "<br>";
                print_r($filteredQueryArray);
                echo "<br>";
            }
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

        
        private function filterJson($objQuery, $personalData){
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

        private function parseSQLQuery($objQuery){
            $parsedQueryArray = [];
            foreach ($objQuery as $query){                                                          //go query by query
                preg_match("/\bFROM\b/i", $query["sql"], $offsetTable, PREG_OFFSET_CAPTURE);        //get table names from SQL query
                $offsetTableStart = $offsetTable[0][1]+5;

                if(preg_match("/\bWhere\b/i", $query["sql"], $offsetWhereClause, PREG_OFFSET_CAPTURE)){     //if the query comes with Where clause, we have to ignore it
                    $offsetTableEnd = $offsetWhereClause[0][1] - 1 - $offsetTableStart;                     // -1 refers to the blank space between the last table name and the WHERE clause
                    $result = substr($query["sql"], $offsetTableStart, $offsetTableEnd);
                }
                else{
                    $result = substr($query["sql"], $offsetTableStart);
                }
                $tableNames = preg_split("/[\s,]+/", $result);
                $parsedQuery=[];
                $index = 0;

                foreach($tableNames as $queryTable){                            //search the array containing the tables' names with personal data, with the tables names found inside the SQL query                         
                            array_push($parsedQuery, array(
                                "table" => $queryTable,
                                "columns" => [],
                                )
                            );
                            preg_match("/\bSELECT\b/i", $query["sql"], $offsetColumn, PREG_OFFSET_CAPTURE);
                            $offsetColumnStart = $offsetColumn[0][1] + 7;
                            $offsetColumnEnd = $offsetTable[0][1] - 1  - $offsetColumnStart;         // -1 refers to the blank space between the last column name and the FROM clause
                            $result = substr($query["sql"],$offsetColumnStart, $offsetColumnEnd);
                            $columnsNames = preg_split("/[\s,]+/", $result);

                            foreach($columnsNames as $queryColumn){                                 
                                        array_push($parsedQuery[$index]["columns"], $queryColumn);
                            }
                            $index ++;
                }

                    array_push($parsedQueryArray, array(                               //create a json array with a "tables" format
                        "tables" => $parsedQuery,
                        "action" => "Read",
                        "timestamp" => $query["timestamp"],
                        "user" => $query["user"],
                        "group" => $query["group"],
                        "returnedRows" => $query["returnedRows"],
                        )
                    );
                
            }
            return $parsedQueryArray;
        }

    }
    
?>