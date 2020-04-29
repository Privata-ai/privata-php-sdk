<html>
    <head>
        <h1>SDK File</h1>
    </head>

    <body>
    
    <?php

        $dbKeyuser = '11dfce72-7889-40e5-b1c8-19865c619eda';
        $dbSecret = '05f3aed260dc756af246e166d1eef9c8f43cd61b';
        $queryTest = '[
            {
              "sql": "SELECT first_name, last_name, phone_number, email FROM Patient",
              "timestamp": 1567493198,
              "user": "4353479",
              "group": "Medics",
              "returnedRows": 3
            },
            {
              "sql": "SELECT blood_type, notes FROM Medical_Record",
              "timestamp": 1567493198,
              "user": "4353479",
              "group": "Medics",
              "returnedRows": 3
            },
            {
              "sql": "SELECT address, vat_number, social_security_number, email FROM Patient_Receipts",
              "timestamp": 1293234,
              "user": "4353479",
              "group": "Administrative",
              "returnedRows": 1
            }
          ]';

    class PrivataAudit {

        protected $dbKey = '';
        protected $idToken = '';
        protected $errorMessage = '';


        public function initialize($dbKeyuser, $dbSecret){
            global  $api_key, $dbKey;
            $api_key = 'AIzaSyDO-JXjcrO9x5sSX30mLQdSpu3_r7yI9gY';
            $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key='.$api_key.'';
            $dbKey = $dbKeyuser;
            $localdbKey =  $dbKeyuser.'@blockbird.ventures';
            $data = [
                'email'=> $localdbKey,
                'password' => $dbSecret,
                'returnSecureToken' => true
            ];
            //echo $api_key;
            try{
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);
                //echo $response;
               
            } catch( Exception $e){
                echo 'Caught an exception while attempting to connect to Firebase: ', $e->getMessage(), "\n";
            }

            if(http_response_code() == 200){
                $obj = json_decode($response, true);                //convert json to array
                if (isset ($obj['idToken'])){
                    global $idToken;
                    $idToken = $obj['idToken'];
                    //echo $obj;
                    return 200;
                }
                else if (isset($obj["error"]["message"])) {
                    global $errorMessage;
                    $errorMessage = $obj["error"]["message"];
                }
            }else {
                echo "There has been an error while trying to connect to Firebase \n". http_response_code();
            }
            echo"Authentication successful \n ";
        }

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
                curl_setopt($curl, CURLOPT_HEADER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);

                curl_close($curl);
                echo $response;
               
            } catch( Exception $e){
                echo 'Caught an exception while attempting to connect to Firebase: ', $e->getMessage(), "\n";
            }


        }

    }
        
    $testObject = new PrivataAudit;
    $testObject -> initialize($dbKeyuser, $dbSecret);
    $testObject -> submitQuery($queryTest);

    ?>
    
    </body>



</html>