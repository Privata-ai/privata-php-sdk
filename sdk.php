<?php
    require 'Auth/FirebaseAuth.php';
    require 'Util/Util.php';
    /** 
     * Class with an initialize function and query submition function
     * 
    */
    class PrivataAudit {

        private $firebaseAuth;
        private $util;

        function __construct(){
            global $firebaseAuth, $util;

            $firebaseAuth = new FirebaseAuth;
            $util = new Util;
        }


        /**
         * Initialize
         * 
         * Signs in the user using his dbKey and dbSecret
         * 
         * Fails with an error if the dbKey or dbSecret do not match
         */
        public function initialize($dbKeyUser ,$dbSecret){
            global $firebaseAuth;
           
            $response = $firebaseAuth -> auth($dbKeyUser, $dbSecret);
            return $response;
        }


        /**
         * Submit Queries
         * 
         * Connects to the database and submits user queries in json format
         * 
         * Fails with an error message should a request fail.
         */
        public function submitQuery($queries){
            global $dbKey, $firebaseAuth, $util;

            $response = $firebaseAuth -> verifyIdToken();
            if ($response['code']== 200 ){
                $idToken = $response['idToken'];
            } else {
                return $response['error'];
            }
            
            define ("apiUrl", 'https://api-sandbox.privata.ai',true);
            $headers = array(
                "Content-Type: application/json",
                "Authorization: Bearer ".$idToken
            );


            $url = apiUrl."/databases"."/".$dbKey;

            
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

            try{
                $personalData = $util -> getPersonalDataParams($response);
                $filteredQueryArray = $util -> filterQuery($objQuery, $personalData);       // filter the query submited in order to only send relevant data

            } catch (Exception $e){
                return($e->getMessage());
            }

            if (empty($filteredQueryArray))
                return 200;
            $filteredJson = json_encode($filteredQueryArray);
            $url = apiUrl."/databases"."/".$dbKey."/queries";

            try{
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $filteredJson);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);
            } catch (Exception $e){
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