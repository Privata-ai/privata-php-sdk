<html>
    <head>
        <h1>SDK File</h1>
    </head>

    <body>

    <?php
    
    require 'sdk.php';

        //Test variables

        $dbKeyuser = '11dfce72-7889-40e5-b1c8-19865c619eda';
        $dbSecret = '05f3aed260dc756af246e166d1eef9c8f43cd61b';
        $queryTest = '[
            {
              "sql": "SELECT first_name, last_name, phone_number, email FROM Patient, countries",
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

        //Tests in order to simulate an API call using the PHP-SDK file
        $testObject = new PrivataAudit;
        $resultAuthentication = $testObject -> initialize($dbKeyuser, $dbSecret);
        $resultQuery = $testObject -> submitQuery($queryTest);
        
        echo "<br>";
        print_r ("Authentication result: ".$resultAuthentication);          //should print 200 in a successful request
        echo "<br>";
        echo "<br>";
        print_r ("Query result: ".$resultQuery);                            //should print 200 in a successful request

    ?>


    </body>



</html>