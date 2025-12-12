<?php
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
header('Content-Type: application/json; charset=utf-8');

// TODO: Set CORS headers to allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// TODO: Include the database connection class
require_once "database.php";

// TODO: Create database connection
$db = (new Database())->getConnection();

// TODO: Set PDO to throw exceptions on errors
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method

$method = $_SERVER['REQUEST_METHOD'];
// TODO: Get the request body for POST and PUT requests
$input = file_get_contents("php://input");

// TODO: Parse query parameters
$data = json_decode($input, true);


// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    // TODO: Start building the SQL query
    $sql = "SELECT * FROM assignments WHERE 1=1";
    // TODO: Check if 'search' query parameter exists in $_GET
     if (!empty($_GET['search'])) {
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
    }
    // TODO: Check if 'sort' and 'order' query parameters exist
     $sort = $_GET['sort'] ?? 'created_at';
    $order = strtolower($_GET['order'] ?? 'asc');
    if (!in_array($order, ['asc', 'desc'])) {
        $order = 'asc';
    }
    $allowedSort = ['title', 'due_date', 'created_at'];
    if (!in_array($sort, $allowedSort)) {
        $sort = 'created_at';
    }

    // TODO: Prepare the SQL statement using $db->prepare()
       $stmt = $db->prepare($sql . " ORDER BY $sort $order");
    // TODO: Bind parameters if search is used
     if (!empty($_GET['search'])) {
        $stmt->bindValue(":search", "%" . $_GET['search'] . "%");
    }
    // TODO: Execute the prepared statement
     $stmt->execute();
    // TODO: Fetch all results as associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // TODO: For each assignment, decode the 'files' field from JSON to array
     foreach ($results as &$row) {
        $row['files'] = json_decode($row['files'], true);
    }
    // TODO: Return JSON response
     sendResponse($results);
}
/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) sendResponse(["error" => "ID required"], 400);
    // TODO: Prepare SQL query to select assignment by id
       $stmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    // TODO: Bind the :id parameter
    $stmt->bindParam(":id", $assignmentId);
    // TODO: Execute the statement
     $stmt->execute();
    // TODO: Fetch the result as associative array
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    // TODO: Check if assignment was found
      if (!$result) sendResponse(["error" => "Not found"], 404);
    // TODO: Decode the 'files' field from JSON to array
        $result['files'] = json_decode($result['files'], true);
    // TODO: Return success response with assignment data
      sendResponse($result);
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        sendResponse(["error" => "Missing fields"], 400);
    }
    // TODO: Sanitize input data
        $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    // TODO: Validate due_date format
     if (!validateDate($data['due_date'])) {
        sendResponse(["error" => "Invalid date"], 400);
    }
    // TODO: Generate a unique assignment ID
      $id = uniqid();
    // TODO: Handle the 'files' field
        $files = isset($data['files']) ? json_encode($data['files']) : json_encode([]);
    // TODO: Prepare INSERT query
        $stmt = $db->prepare("INSERT INTO assignments (id,title,description,due_date,files) VALUES (:id,:t,:d,:dd,:f)");
    // TODO: Bind all parameters
    $stmt->bindParam(":id", $id);
    $stmt->bindParam(":t", $title);
    $stmt->bindParam(":d", $description);
    $stmt->bindParam(":dd", $data['due_date']);
    $stmt->bindParam(":f", $files);
    
    // TODO: Execute the statement
        $success = $stmt->execute();
    
    // TODO: Check if insert was successful
    if (!$success) {
          // TODO: If insert failed, return 500 error
    sendResponse(["error" => "Insert failed"], 500);
}
sendResponse(["success" => true, "id" => $id], 201);
}
/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
        if (empty($data['id'])) sendResponse(["error" => "ID required"], 400);
    // TODO: Store assignment ID in variable
        $id = $data['id'];
    // TODO: Check if assignment exists
$check = $db->prepare("SELECT id FROM assignments WHERE id = :id");
$check->execute([":id" => $assignmentId]);
if ($check->rowCount() === 0) {
    sendResponse(["error" => "Assignment not found"], 404);
}
    // TODO: Build UPDATE query dynamically based on provided fields
      $fields = [];
    $params = [":id" => $id];
    if (!empty($data['title'])) {
        $fields[] = "title = :title";
        $params[":title"] = sanitizeInput($data['title']);
    }

    if (!empty($data['description'])) {
        $fields[] = "description = :description";
        $params[":description"] = sanitizeInput($data['description']);
    }

    if (!empty($data['due_date'])) {
        $fields[] = "due_date = :due_date";
        $params[":due_date"] = $data['due_date'];
    }

    if (!empty($data['files'])) {
        $fields[] = "files = :files";
        $params[":files"] = json_encode($data['files']);
    }
    
    // TODO: Check which fields are provided and add to SET clause
    $setClauses = [];
$params = [];
$types = "";
if (!empty($data['title'])) {
    $setClauses[] = "title = ?";
    $params[] = $data['title'];
    $types .= "s";
}

if (!empty($data['description'])) {
    $setClauses[] = "description = ?";
    $params[] = $data['description'];
    $types .= "s";
}

if (!empty($data['deadline'])) {
    $setClauses[] = "deadline = ?";
    $params[] = $data['deadline'];
    $types .= "s";
}
if (empty($setClauses)) {
    http_response_code(400);
    echo json_encode(["error" => "No fields provided to update"]);
    exit;
}
$sql = "UPDATE assignments SET " . implode(", ", $setClauses) . " WHERE id = ?";
$params[] = $id;
$types .= "i";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
    
    // TODO: If no fields to update (besides updated_at), return 400 error
        if (empty($fields)) sendResponse(["error" => "Nothing to update"], 400);

    
    // TODO: Complete the UPDATE query
        $sql = "UPDATE assignments SET " . implode(", ", $fields) . " WHERE id = :id";

    
    // TODO: Prepare the statement
    
        $stmt = $db->prepare($sql);

    // TODO: Bind all parameters dynamically
    
     foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    // TODO: Execute the statement
    
        $stmt->execute();

    // TODO: Check if update was successful
     if ($stmt->rowCount() === 0) {
    
    // TODO: If no rows affected, return appropriate message
     sendResponse(["message" => "No changes made"], 200);
    }

    sendResponse(["success" => true]);
}
/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    
        if (empty($assignmentId)) sendResponse(["error" => "ID required"], 400);

    // TODO: Check if assignment exists
      $check = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $check->execute([":id" => $assignmentId]);
    if ($check->rowCount() === 0) {
        sendResponse(["error" => "Assignment not found"], 404);
    }
    
    // TODO: Delete associated comments first (due to foreign key constraint)
        $db->prepare("DELETE FROM comments WHERE assignment_id = :id")
            ->execute([":id" => $assignmentId]);
    
    // TODO: Prepare DELETE query for assignment
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = :id");

    
    // TODO: Bind the :id parameter
        $stmt->bindParam(":id", $assignmentId);

    
    // TODO: Execute the statement
        $stmt->execute();

    
    // TODO: Check if delete was successful
   if (!$success) {
            // TODO: If delete failed, return 500 error
        sendResponse(["error" => "Delete failed"], 500);
    }

    sendResponse(["success" => true]);
}


// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
     if (empty($assignmentId)) sendResponse(["error" => "assignment_id required"], 400);
    
    // TODO: Prepare SQL query to select all comments for the assignment
     $stmt = $db->prepare("SELECT * FROM comments WHERE assignment_id = :id");
    
    // TODO: Bind the :assignment_id parameter
     $stmt->bindParam(":id", $assignmentId);
    
    // TODO: Execute the statement
      $stmt->execute();
    
    // TODO: Fetch all results as associative array
      $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return success response with comments data
    sendResponse($comments);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
     if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(["error" => "Missing fields"], 400);
    }
    
    // TODO: Sanitize input data
     $assignmentId= sanitizeInput($data['assignment_id']);
    $author= sanitizeInput($data['author']);
    $text= sanitizeInput($data['text']);
    
    // TODO: Validate that text is not empty after trimming
     if (trim($text) === "") {
        sendResponse(["error" => "Comment text cannot be empty"], 400);
    }
    
    // TODO: Verify that the assignment exists
    
    $check = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $check->execute([":id" => $assignmentId]);
    if ($check->rowCount() === 0) {
        sendResponse(["error" => "Assignment not found"], 404);
    }
    // TODO: Prepare INSERT query for comment
      $stmt = $db->prepare("
        INSERT INTO comments (assignment_id, author, text)
        VALUES (:aid, :author, :text)
    ");
    
    // TODO: Bind all parameters
      $stmt->bindParam(":aid", $assignmentId);
    $stmt->bindParam(":author", $author);
    $stmt->bindParam(":text", $text);
    
    // TODO: Execute the statement
    $stmt->execute();
    
    // TODO: Get the ID of the inserted comment
     $newId = $db->lastInsertId();

    
    // TODO: Return success response with created comment data
  sendResponse([
        "success" => true,
        "id" => $newId,
        "assignment_id" => $assignmentId,
        "author" => $author,
        "text" => $text
    ], 201);
}

/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
    if (empty($commentId)) {
        sendResponse(["error" => "Comment ID is required"], 400);
    }
    
    // TODO: Check if comment exists
    $check = $db->prepare("SELECT id FROM comments WHERE id = :id");
    $check->execute([":id" => $commentId]);
    
    if ($check->rowCount() === 0) {
        sendResponse(["error" => "Comment not found"], 404);
    }
    
    // TODO: Prepare DELETE query
        $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");

    
    // TODO: Bind the :id parameter
    $stmt->bindParam(":id", $commentId, PDO::PARAM_INT);
    
    // TODO: Execute the statement
        $stmt->execute();

    
    // TODO: Check if delete was successful
    if ($stmt->rowCount() === 0) {
    
    // TODO: If delete failed, return 500 error
   sendResponse(["error" => "Failed to delete comment"], 500);
    }
    sendResponse(["success" => true], 200);
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
        $resource = $_GET['resource'] ?? null;

    // TODO: Route based on HTTP method and resource type
    
    if ($method === 'GET') {
        // TODO: Handle GET requests
        
        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
            if (!empty($_GET['id'])) {
                getAssignmentById($db, $_GET['id']);
            } else {
                getAllAssignments($db);
            }

        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
            if (empty($_GET['assignment_id'])) {
                sendResponse(["error" => "assignment_id is required"], 400);
            }
            getCommentsByAssignment($db, $_GET['assignment_id']);

            // TODO: Invalid resource, return 400 error
             sendResponse(["error" => "Invalid resource for GET"], 400);
        }
    }
    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)
               
        if ($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
             createAssignment($db, $data);
        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
             createComment($db, $data);
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(["error" => "Invalid resource for POST"], 400);
        }
        }else if ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            updateAssignment($db, $data);
        } else {
            // TODO: PUT not supported for other resources
            sendResponse(["error" => "PUT not supported for this resource"], 405);
        }

        } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests
        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
              if (empty($_GET['id'])) {
                sendResponse(["error" => "id is required"], 400);
            }
            deleteAssignment($db, $_GET['id']);

        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
            if (empty($_GET['id'])) {
                sendResponse(["error" => "id is required"], 400);
            }
            deleteComment($db, $_GET['id']);

        } else {
            // TODO: Invalid resource, return 400 error
             sendResponse(["error" => "Invalid resource for DELETE"], 400);
        } 

}else {
        // TODO: Method not supported
      sendResponse(["error" => "Method not supported"], 405);
    } catch (PDOException $e) {
    // TODO: Handle database errors
    
sendResponse(["error" => "Database error: " . $e->getMessage()], 500);

} catch (Exception $e) {
    // TODO: Handle general errors
     sendResponse(["error" => $e->getMessage()], 500);
}
    


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
      http_response_code($statusCode);
    // TODO: Ensure data is an array
     if (!is_array($data)) {
        $data = ["data" => $data];
    }
    
    // TODO: Echo JSON encoded data
    header("Content-Type: application/json; charset=UTF-8");
    
    // TODO: Exit to prevent further execution
    echo json_encode($data, JSON_PRETTY_PRINT);
      exit();
}
/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
    $data = trim($data);
    // TODO: Remove HTML and PHP tags
     $data = strip_tags($data);
    // TODO: Convert special characters to HTML entities
     $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');   
    // TODO: Return the sanitized data
      return $data;
}
/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
      if (empty($date) || !is_string($date)) {
        return false;
    }
    // Use DateTime::createFromFormat to validate
    $d = DateTime::createFromFormat("Y-m-d", $date);
    // TODO: Return true if valid, false otherwise
  return $d && $d->format("Y-m-d") === $date;
}
/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
     if (!is_array($allowedValues)) {
        return false;
    }
    // TODO: Return the result
   return in_array($value, $allowedValues, true);
}
?>
