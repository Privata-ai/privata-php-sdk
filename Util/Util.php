<?php

    /** 
     * Class responsible for util functions
     * 
    */
    class Util {

        /**
         * Filter queries before sending to our API
         * 
         * Takes the full query submited by the user and applies some filters in order to send only relevant 
         * data to our API. (Send only information relevant to personal data accesses)
         */
        public function filterQuery($objQuery, $personalData){
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
                            if(!empty($columnsWithPersonalData)){                   // if there were no relevant columns on the initial query, the table is removed from the final filtered query
                                array_push($filteredQuery, array(
                                    "table" => $queryTable["table"],
                                    "columns" => $columnsWithPersonalData,
                                    )
                                );
                            }
                        }
                    }
                }
                if(!empty($filteredQuery)){                                         // if the initial query didn't access any personal information the query is removed from the final query array
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

        /**
         * Get relevant fields
         * 
         * Takes the database response containing all table and columns names and outputs an array with
         * only the tables and columns that have personal data.
         *  
         */
        public function getPersonalDataParams($response){
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