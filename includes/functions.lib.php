<?php

// Daniel's Functions
function connectDB(){
    $dsn = "mysql:host=localhost;dbname=mydata";
    $dbusername = "root";
    $password = ""; // on mac you may need to use root on xamp

    // You can use this line alone or with try catch
    // $pdo = new PDO($dsn, $dbusername, $password);

    try {
        // pdo = PHP Data Objects
        $pdo = new PDO( $dsn,  $dbusername, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
}

function tbData2Array(string $tbname) {
    try {
        $pdo = connectDB();

        // Prepare the SQL query to fetch all data from the specified table
        $query = "SELECT * FROM " . $tbname;
        $stmt = $pdo->prepare($query);

        // Execute the query
        $stmt->execute();

        // Fetch all rows as an associative array
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Close the database connection
        $pdo = null;
        $stmt = null;

        // Return the fetched data or an empty array if no data is found
        return $rows ?: [];
        die();
    } catch (PDOException $e) {
        // Handle any database errors
        die("Query failed: " . $e->getMessage());
    }
}

function arrayIntoDB(string $tbname, array $data) {
    try {
        $pdo = connectDB();

        // Check if the array is empty
        if (empty($data)) {
            throw new Exception("Data array is empty.");
        }

        // Get column names from the first element of the array
        $columns = array_keys($data[0]);
        $columnNames = implode(", ", $columns);

        // Create placeholders for bind parameters
        $placeholders = ":" . implode(", :", $columns);

        // Prepare the SQL query
        $query = "INSERT INTO $tbname ($columnNames) VALUES ($placeholders)";
        $stmt = $pdo->prepare($query);

        // Iterate through the data array and bind values
        foreach ($data as $row) {
            foreach ($row as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute(); // Execute the query for each row
        }

        // Close the database connection
        $pdo = null;
        $stmt = null;

        return true; // Return true if insertion is successful
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }

    // Insert rows Example
    // $data = [
    //     ["name" => "John Doe", "email" => "john@example.com", "age" => 30],
    //     ["name" => "Jane Smith", "email" => "jane@example.com", "age" => 25],
    // ];
    
    // $tableName = "users";
    // if (arrayIntoDB($tableName, $data)) {
    //     echo "Data inserted successfully!";
    // } else {
    //     echo "Failed to insert data.";
    // }
}

function show($array, $level = 0) {
    // Check if the input is an array
    if (!is_array($array)) {
        echo str_repeat("&nbsp;", $level * 4) . htmlspecialchars($array) . "<br>";
        return;
    }

    // Reindex the array starting from zero
    $array = array_values($array);

    // Iterate through the array
    foreach ($array as $key => $value) {
        // Indent based on the level of nesting
        echo str_repeat("&nbsp;", $level * 4) . "<strong>" . htmlspecialchars($key) . "</strong>: ";

        // If the value is an array, recursively call the function
        if (is_array($value)) {
            echo "<br>";
            show($value, $level + 1);
        } else {
            // Otherwise, print the value
            echo htmlspecialchars($value) . "<br>";
        }
    }
}

function mergeArraysBySubArray($array1, $array2) {
    // Iterate through the second array and merge its elements into the first array
    foreach ($array2 as $key => $subArray2) {
        if (isset($array1[$key])) {
            // Append the second array's sub-array to the corresponding sub-array in the first array
            $array1[$key] = array_merge($array1[$key], $subArray2);
        } else {
            // If the key doesn't exist in the first array, add the sub-array from the second array
            $array1[$key] = $subArray2;
        }
    }
    return $array1;
}


function formatDate($dateString, $inputFormat)
{
    // Create a DateTime object from the input date string and format
    $dateObject = DateTime::createFromFormat($inputFormat, $dateString);

    if ($dateObject) {
        // Format the date to 'YYYY-MM-DD' for MySQL
        $formattedDate = $dateObject->format('Y-m-d');
        return $formattedDate;
    } else {
        return "Invalid date format!";
    }
}

?>