<?php
// require_once "includes/dbh.inc.php"; 
require_once "includes/functions.lib.php";

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial scale=1.0">
    <title>Import Excel to DB</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="./css/main.css">
</head>

<body>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <form class="" , action="" , enctype="multipart/form-data" method="post">
        <h3>Import Excel Data</h3>
        <input type="file" name="excel" value="" required>
        <button type="submit" name="import">Import</button>
    </form>

    <?php

    $error = "";
    $message = "";
    $table = "";

    // Read and Insert Data
    if (isset($_POST["import"])) {

        // Get the extension
        $fileName = $_FILES["excel"]["name"];
        $fileExtension = explode('.', $fileName);
        $fileExtension = strtolower(end($fileExtension));
        // 2025_02_04-18_34.___
        $newFilename = date("Y_m_d") . "_" . date("h_i") . "." . $fileExtension;

        $targetDir = "uploads/" . $newFilename;
        /* Move the uploaded file from the temp 
        folder to the uploads for preparation */
        move_uploaded_file($_FILES["excel"]["tmp_name"], $targetDir);

        error_reporting(0);
        ini_set('display_error', 0);

        // Spreadsheet Reader
        require "excelReader/excel_reader2.php";
        require "excelReader/SpreadsheetReader.php";

        $reader = new SpreadsheetReader($targetDir);
        // Get the list of sheets
        $sheets = $reader->Sheets();
        // Retrieve the worksheet called 'sheet1'
        // $spreadsheet->getSheetByName('sheet1');
        
        // DB Connect
        $pdo = connectDB();
        if ($pdo && 1 == 1) {
            try {
                $query = "INSERT INTO bank_users (name, country, age, email, phone, account, account_date, balance, active)
                VALUES (:name, :country, :age, :email, :phone, :account, :account_date, :balance, :active);";
                $stmt = $pdo->prepare($query);

                $rowCount = 0;
                $first = true; // Flag to track the first iteration
                $coorectHeader = "namecountryageemailphoneaccountaccount_datebalanceactive";

                // Insert 1.st sheet
                // Access the first sheet
                $reader->ChangeSheet(0); // 0 is the index for the first sheet
                foreach ($reader as $row) {
                    
                    // Skip the first iteration if the Header is correct
                    if ($first) {
                        // Header Check
                        $header = strtolower(implode($row));
                        if ($header == $coorectHeader) {
                            echo "Header OK";
                            $first = false;
                            continue;
                        } else {
                            $error .= "Incorrect Header!";
                        }
                    }
                    // Convert the date to a DateTime object
                    $row[6] = formatDate($row[6], 'd-m-y');

                    $stmt->bindParam(":name", $row[0]);
                    $stmt->bindParam(":country", $row[1]);
                    $stmt->bindParam(":age", $row[2]);
                    $stmt->bindParam(":email", $row[3]);
                    $stmt->bindParam(":phone", $row[4]);
                    $stmt->bindParam(":account", $row[5]);
                    $stmt->bindParam(":account_date", $row[6]);
                    $stmt->bindParam(":balance", $row[7]);
                    $stmt->bindParam(":active", $row[8]);
                    $stmt->execute();
                    $rowCount++;
                }
                $message = $rowCount . ' rows inserted successfully! (Bank Users) ';


                // Insert 2.nd sheet
                $query_bic = "INSERT INTO country_bic (country, bic_swift)
                VALUES (:country, :bic_swift);";
                $stmt = $pdo->prepare($query_bic);
                
                $rowCount = 0;
                $first = true; // Flag to track the first iteration
                $coorectHeader = "countrybic/swift";

                // Access the second sheet
                if (isset($sheets[1])) { // Check if the second sheet exists

                    $reader->ChangeSheet(1); // 1 is the index for the second sheet
                    foreach ($reader as $row) {
                        // Skip the first iteration if the Header is correct
                        if ($first) {
                            // Header Check
                            $header = strtolower(implode($row));
                            if ($header == $coorectHeader) {
                                echo "Header 2 OK";
                                $first = false;
                                continue;
                            } else {
                                $error .= "Incorrect Header 2!";
                            }
                        }

                        $stmt->bindParam(":country", $row[0]);
                        $stmt->bindParam(":bic_swift", $row[1]);
                        $stmt->execute();
                        $rowCount++;
                    }

                } else {
                    $error .= "Second sheet does not exist.\n";
                }

                $message .= $rowCount . ' rows inserted successfully! (BIC/SWIFT) ';

            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }

            $pdo = null; // Close the connection
            $stmt = null;
        } // PDO Connection Error handling is in connectDB function

        // Cleanup file from uploads
        if (unlink($targetDir)) {
            $message .= 'The Uploaded file ' . $fileName . ' is deleted from uploads.';
        };
    }


    $select_q = "SELECT bank_users.*, country_bic.bic_swift
    FROM bank_users
    LEFT JOIN country_bic
    ON bank_users.country = country_bic.country
    ORDER BY bank_users.id";

    // Show data fronm DB
    $rows = tbData2Array("bank_users", $select_q);

    $i = 1;
    $td = "";
    // Show Details from DB
    foreach ($rows as $row) {
        $td .= '<tr>';
        $td .= '<td scope="row">' . $row["id"] . '</td>';
        $td .= '<td >' . $row["name"] . '</td>';
        $td .= '<td >' . $row["age"] . '</td>';
        $td .= '<td >' . $row["country"] . '</td>';
        $td .= '<td >' . $row["email"] . '</td>';
        $td .= '<td >' . $row["account"] . '</td>';
        $td .= '<td >' . $row["bic_swift"] . '</td>';
        $td .= '<td >' . $row["active"] . '</td>';
        $td .= '</tr>';
    }

    $table = '<table class="table table-dark table-striped table-hover table-sm">
    <caption>List of users</caption>
    <tr>
        <td scope="col">#</td>
        <td scope="col">Name</td>
        <td scope="col">Country</td>
        <td scope="col">Age</td>
        <td scope="col">Email</td>
        <td scope="col">Account</td>
        <td scope="col">BIC/SWIFT</td>
        <td scope="col">Active</td>
    </tr>
    <div>'
        . $td .
        '</div>
    </table>';

    if($error != ""){
        echo '<div class="alert alert-danger" role="alert"">' . $error . '</div>';
    };
    if($message != ""){
        echo '<div class="alert alert-primary" role="alert"">' . $message . '</div>';
    };
    echo $table;

    ?>
    <script>
        $(document).ready(function() {
            // Use event delegation to handle dynamically added elements
            $(document).on('click', '.alert', function() {
                $(this).remove();
            });
        });
    </script>

</body>

</html>