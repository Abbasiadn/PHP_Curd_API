<?php
// Include necessary headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle pre-flight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Include your database connection class and routing logic
require_once 'route.php';
require_once 'api.php';
require_once 'index.php';
require_once 'db.php';

// Improved getAllUsers function with error handling
function getAllUsers($params, $queryParams) {
    $db = new Db();
    $conn = $db->getConnection();
    //    var_dump(count( $queryParams)); 
    try {
        // Check if there are duplicate parameters (multiple 'id' in query)
        if (isset($queryParams) && count($queryParams) > 1) {
            foreach ($queryParams as $key => $value) {
                // if (is_array($value) && count($value) > 1) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Too many "' . $key . '" parameters found. Only one "' . $key . '" parameter is allowed.'
                    ]);
                    return;
                }
           
        }

        // If 'id' parameter exists, get data for that specific ID
        if (isset($queryParams['id'])) {
            $id = $queryParams['id'];
            $stmt = $conn->prepare("CALL GetUserWithId(:id)");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => "Fetching user with ID: $id",
                    'data' => ['user' => $user]
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => "User with ID $id not found."
                ]);
            }
        } else {
            // If no 'id' parameter, fetch all users
            $stmt = $conn->prepare("CALL getAllUser()");
            $stmt->execute();

            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Fetching all users...',
                'data' => $users
            ]);
        }
    } catch (PDOException $e) {
        // Handle database errors
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        // Handle general errors
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'An unexpected error occurred: ' . $e->getMessage()
        ]);
    } finally {
        // Close DB connection
        $db->close();
    }
}


// function getAllUsers($params, $queryParams) {
//     $db = new Db();
//     $conn = $db->getConnection();

//     try {
//         if (isset($queryParams['id'])) {
//             $id = $queryParams['id'];
//             $stmt = $conn->prepare("CALL 	GetUserWithId(:id)");
//             $stmt->bindParam(':id', $id, PDO::PARAM_INT);
//             $stmt->execute();
//             $user = $stmt->fetch(PDO::FETCH_ASSOC);

//             if ($user) {
//                 echo json_encode([
//                     'status' => 'success',
//                     'message' => "Fetching user with ID: $id",
//                     'data' => ['user' => $user]
//                 ]);
//             } else {
//                 echo json_encode([
//                     'status' => 'error',
//                     'message' => "User with ID $id not found."
//                 ]);
//             }
//         } else {
//             $stmt = $conn->prepare("CALL getAllUser()");
//             $stmt->execute();
//             $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

//             echo json_encode([
//                 'status' => 'success',
//                 'message' => 'Fetching all users...',
//                 'data' => $users
//             ]);
//         }
//     } catch (PDOException $e) {
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'Database error: ' . $e->getMessage()
//         ]);
//     }

//     $db->close();
// }

// Create User function with validation and error handling
function createUser($params, $queryParams) {
    $db = new Db();
    $conn = $db->getConnection();

    // Get raw POST body data
    $inputData = json_decode(file_get_contents('php://input'), true);  // Decode the JSON body

    // Check if the body is empty or missing required parameters
    if (empty($inputData)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Request body is empty. Please provide the required data.'
        ]);
        return;
    }

    // Check if name and email are set in the POST data
    if (isset($inputData['name']) && isset($inputData['email'])) {
        $name = $inputData['name'];
        $email = $inputData['email'];

        // Validate name (should only contain letters and spaces)
        if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid name. Only letters and spaces are allowed.'
            ]);
            return;
        }

        // Validate email (should be a valid email address format)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email format.'
            ]);
            return;
        }

        // Sanitize inputs (to avoid special characters or HTML tags)
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

        // Prepare a parameterized SQL query to prevent SQL injection
        try {
            // Call the stored procedure to create the user
            $stmt = $conn->prepare("CALL createUser(:name, :email)");
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            // Fetch the inserted ID from the stored procedure's result
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $id = $result['id'];  // Extract the inserted ID

            // Now, retrieve the user data using the newly created ID
            $stmt = $conn->prepare("CALL GetUserWithId(:id)");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if user data was found
            if ($user) {
                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User created and fetched successfully!',
                    'data' => ['user' => $user]
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found after creation.'
                ]);
            }
        } catch (PDOException $e) {
            // Error handling
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    } else {
        // Missing required parameters
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'User name and email are required.'
        ]);
    }

    $db->close();
}


// function createUser($params, $queryParams) {
//     $db = new Db();
//     $conn = $db->getConnection();

//     try {
//         if (isset($_POST['name']) && isset($_POST['email'])) {
//             $name = $_POST['name'];
//             $email = $_POST['email'];

//             // Simple validation (you can add more)
//             if (empty($name) || empty($email)) {
//                 echo json_encode([
//                     'status' => 'error',
//                     'message' => 'User name and email are required.'
//                 ]);
//                 return;
//             }

//             $stmt = $conn->prepare("CALL CreateUser(:name, :email)");
//             $stmt->bindParam(':name', $name, PDO::PARAM_STR);
//             $stmt->bindParam(':email', $email, PDO::PARAM_STR);
//             $stmt->execute();

//             $id = $stmt->fetchColumn();  // Get the last inserted ID

//             echo json_encode([
//                 'status' => 'success',
//                 'message' => 'User created successfully!',
//                 'data' => ['id' => $id, 'name' => $name, 'email' => $email]
//             ]);
//         } else {
//             echo json_encode([
//                 'status' => 'error',
//                 'message' => 'User name and email are required.'
//             ]);
//         }
//     } catch (PDOException $e) {
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'Database error: ' . $e->getMessage()
//         ]);
//     }

//     $db->close();
// }

// Update User function with validation and error handling
function updateUser($params, $queryParams) {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);  // Method Not Allowed
        echo json_encode([
            'status' => 'error',
            'message' => 'Method Not Allowed. Please use PUT method for updating user.'
        ]);
        return;
    }

    if (isset($queryParams['id'])) {
        $id = $queryParams['id'];
        $db = new Db();
        $conn = $db->getConnection();

        if (isset($queryParams) && count($queryParams) > 1) {
            foreach ($queryParams as $key => $value) {
                // if (is_array($value) && count($value) > 1) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Too many "' . $key . '" parameters found. Only one "' . $key . '" parameter is allowed.'
                    ]);
                    return;
                }
           
        }

        try {
            // Get raw PUT data
            $inputData = json_decode(file_get_contents("php://input"), true);

            if ($inputData === null) {
                http_response_code(400);  // Bad Request
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid JSON in request body.'
                ]);
                return;
            }

            // Validate and sanitize inputs
            if (isset($inputData['name']) && isset($inputData['email'])) {
                $name = $inputData['name'];
                $email = $inputData['email'];

                // Check if 'name' is not empty and is a valid string (only letters and spaces)
                if (empty($name)) {
                    http_response_code(400);  // Bad Request
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Name cannot be empty.'
                    ]);
                    return;
                }
                if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
                    http_response_code(400);  // Bad Request
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Name should only contain letters and spaces.'
                    ]);
                    return;
                }

                // Check if 'email' is not empty and is a valid email format
                if (empty($email)) {
                    http_response_code(400);  // Bad Request
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Email cannot be empty.'
                    ]);
                    return;
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);  // Bad Request
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid email format.'
                    ]);
                    return;
                }

                // Sanitize inputs to avoid special characters or HTML tags
                $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

                // Prepare the SQL query to prevent SQL injection and call the stored procedure to update user
                $stmt = $conn->prepare("CALL UpdateUser(:id, :name, :email)");
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();

                // Fetch the updated user data using the updated stored procedure
                $stmt = $conn->prepare("CALL UpdateUser(:id, :name, :email)");
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();

                // Get the updated ID (returned from the stored procedure)
                $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check if the user exists
                if ($updatedUser) {
                    http_response_code(200);  // OK
                    echo json_encode([
                        'status' => 'success',
                        'message' => "User with ID $id updated successfully!",
                        'data' => $updatedUser
                    ]);
                } else {
                    http_response_code(404);  // Not Found
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'User not found after update.'
                    ]);
                }
            } else {
                http_response_code(400);  // Bad Request
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User name and email are required.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);  // Internal Server Error
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }

        $db->close();
    } else {
        http_response_code(400);  // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID is required for update.'
        ]);
    }
}





// Delete User function with error handling
function deleteUser($params, $queryParams) {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(405);  // Method Not Allowed
        echo json_encode([
            'status' => 'error',
            'message' => 'Method Not Allowed. Please use DELETE method for deleting a user.'
        ]);
        return;
    }

    if (isset($queryParams['id'])) {
        $id = $queryParams['id'];

        // Validate the ID
        if (!is_numeric($id) || $id <= 0) {
            http_response_code(400);  // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid user ID. ID must be a positive integer.'
            ]);
            return;
        }

        if (isset($queryParams) && count($queryParams) > 1) {
            foreach ($queryParams as $key => $value) {
                // if (is_array($value) && count($value) > 1) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Too many "' . $key . '" parameters found. Only one "' . $key . '" parameter is allowed.'
                    ]);
                    return;
                }
           
        }

        $db = new Db();
        $conn = $db->getConnection();

        try {
            // Sanitize the ID (cast to integer to ensure safety)
            $id = (int)$id;

            // Call the stored procedure to delete the user
            $stmt = $conn->prepare("CALL DeleteUser(:id)");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Check if the user was deleted successfully
            if ($stmt->rowCount() > 0) {
                http_response_code(200);  // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => "User with ID $id deleted successfully!"
                ]);
            } else {
                http_response_code(404);  // Not Found
                echo json_encode([
                    'status' => 'error',
                    'message' => "User with ID $id not found."
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);  // Internal Server Error
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }

        $db->close();
    } else {
        http_response_code(400);  // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID is required for delete.'
        ]);
    }
}
// Assuming you have an API router or entry point
function handleStoreBearerTokenRequest() {
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Method Not Allowed
        echo json_encode([
            'status' => 'error',
            'message' => 'Method Not Allowed. Please use POST method.'
        ]);
        return;
    }

    // Get the raw POST data
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Validate the input (user_id is required)
    if (isset($inputData['user_id'])) {
        $user_id = $inputData['user_id'];

        // Call the function to store the Bearer token
        $response = storeBearerToken($user_id);

        // Return the response (Token generated successfully)
        echo json_encode($response);
    } else {
        // Invalid request if user_id is missing
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID is required.'
        ]);
    }
}

// Function to store the Bearer token in the database
function storeBearerToken($user_id) {
    // Generate a Bearer token
    $token = bin2hex(random_bytes(32)); // 64-character token

    // Set the expiration time for the token (e.g., 1 hour)
    date_default_timezone_set('Asia/Karachi');

    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store the token in the database
    try {
        $db = new Db();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("INSERT INTO tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->bindParam(':expires_at', $expiresAt, PDO::PARAM_STR);

        $stmt->execute();

        // Return the token response
        return [
            'status' => 'success',
            'message' => 'Token generated successfully!',
            'token' => $token, // Returning the generated token
            'expires_at' => $expiresAt
        ];
    } catch (PDOException $e) {
        // Handle the database error
        return [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Call the API handler to process the request


?>
