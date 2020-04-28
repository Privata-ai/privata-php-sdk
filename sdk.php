<html>
    <head>
        <h1>SDK File</h1>
    </head>

    <body>
    
    <?php

        $dbKey = '11dfce72-7889-40e5-b1c8-19865c619eda';
        $dbSecret = '05f3aed260dc756af246e166d1eef9c8f43cd61b';
        $idToken = '';
        $errorMessage = '';
                
        function initialize($dbKey, $dbSecret){
            $api_key = 'AIzaSyDO-JXjcrO9x5sSX30mLQdSpu3_r7yI9gY'; 
            $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key='.$api_key.'';
            $dbKey = $dbKey.'@blockbird.ventures';
            $data = [
                'email'=> $dbKey,
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
                //echo $response;
               
            } catch( Exception $e){
                echo 'Caught an exception while attempting to connect to Firebase: ', $e->getMessage(), "\n";
            }

            if(http_response_code() == 200){
                $obj = json_decode($response, true);                //convert json to array
                if (isset ($obj['idToken'])){
                    global $idToken;
                    $idToken = $obj['idToken'];
                }
                else if (isset($obj["error"]["message"])) {
                    global $errorMessage;
                    $errorMessage = $obj["error"]["message"];
                }
                
                
            }else {
                echo "There has been an error while trying to connect to Firebase \n";
            }
        }

        initialize($dbKey, $dbSecret);

        echo $idToken;
        echo $errorMessage;

        

        
      
    ?>
    
    </body>



</html>