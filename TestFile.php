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
        $queryTest1 = '[
            {
              "sql": "SELECT first_name, last_name, bankAccountBranch , bankAccountCode, region  FROM suppliers, countries WHERE adfasd aweasd ae ",
              "timestamp": 1567493198,
              "user": "4353479",
              "group": "Medics",
              "returnedRows": 3
            },
            {
              "sql": "SELECT * FROM buyingGroups",
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

          $queryTest2 = '[
            {
              "tables": [
                {
                  "table": "suppliers",
                  "columns": [
                    "bankAccountBranch",
                    "bankAccountCode",
                    "last_name"
                  ]
                },
                {
                  "table": "countries",
                  "columns": [
                  ]
                }
              ],
              "action": "Read",
              "timestamp": 1567493198,
              "user": "4353479",
              "group": "Medics",
              "returnedRows": 3
            },
            {
              "tables": [
                {
                  "table": "buyingGroups",
                  "columns": [
                    "blood_type",
                    "notes"
                  ]
                }
              ],
              "action": "Read",
              "timestamp": 1567493198,
              "user": "4353479",
              "group": "Medics",
              "returnedRows": 3
            },
            {
              "tables": [
                {
                  "table": "Patient_Receipts",
                  "columns": [
                    "address",
                    "vat_number",
                    "social_security_number",
                    "email"
                  ]
                }
              ],
              "action": "Read",
              "timestamp": 1293234,
              "user": "4353479",
              "group": "Administrative",
              "returnedRows": 1
            }
          ]';

        //Tests in order to simulate an API call using the PHP-SDK file
        $testObject = new PrivataAudit;
        $resultAuthentication = $testObject -> initialize($dbKeyuser, $dbSecret);
        $resultQuery1 = $testObject -> submitQuery($queryTest1);
        
        echo "<br>";
        echo "<br>";
        echo "<br>";
        print_r ("Authentication result: ".$resultAuthentication);          //should print 200 in a successful request
        echo "<br>";
        echo "<br>";
        print_r ("Query result: ".$resultQuery1);                            //should print 200 in a successful request
        echo "<br>";
        echo "<br>";
        echo "<br>";
        echo "<br>";
        print_r ("Query 2 test:") ;
        echo "<br>";
        echo "<br>";
        $resultQuery2 = $testObject -> submitQuery($queryTest2);
        echo "<br>";
        echo "<br>";
        print_r ("Query result: ".$resultQuery2); 
    ?>


    </body>



</html>