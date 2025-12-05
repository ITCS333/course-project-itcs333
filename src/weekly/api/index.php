<?php
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================


header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Allow: GET, POST, PUT, DELETE, OPTIONS');


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';



// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();
require_once '../config/Database.php';


// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$database = new Database();
$db       = $database->getConnection();


// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$rawInput = file_get_contents('php://input');
$body   = json_decode($rawInput,true);
if(!is_array($body)){
    $body = [];
}
// TODO: Parse query parameters
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$resource = isset($_GET['resource']) ? strtolower(trim($_GET['resource'])) : 'weeks' ;


// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // TODO: Initialize variables for search, sort, and order from query parameters
    $search = $isset($_GET['search']) ? trim($_GET['search']) : null ;
    $sort = $isset($_GET['sort']) ? trim ($_GET['sort']) : 'start_data';
    $order = $isset($_GEt['order']) ? trim ($_GET['order']) : 'asc'
    
    // TODO: Start building the SQL query
    // Base query: SELECT week_id, title, start_date, description, links, created_at FROM weeks
    $sql = "SELECT week_id, title, start_date, description , links, created_at ,updated_at FROM weeks" ;

    $params = [];
    
    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE for title and description
    // Example: WHERE title LIKE ? OR description LIKE ?
    if(!empty($search)){
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
    
    // TODO: Check if sort parameter exists
    // Validate sort field to prevent SQL injection (only allow: title, start_date, created_at)
    // If invalid, use default sort field (start_date)
    $allowedFields = ['title ', 'start_date' ,'created_at'] ;
    if(!isValidSortField($sort , $allowedFields)){
        $sort = 'start_data';
    }
    
    // TODO: Check if order parameter exists
    // Validate order to prevent SQL injection (only allow: asc, desc)
    // If invalid, use default order (asc)
    if(!in_array($order ,['asc','desc'],true)){
        $order='asc' ;
    }
    
    // TODO: Add ORDER BY clause to the query
    $sql .= "order by {$sort} {$order}";

    
    // TODO: Prepare the SQL query using PDO
    $stmt=$db ->prepare($sql);
    // TODO: Bind parameters if using search
    // Use wildcards for LIKE: "%{$searchTerm}%"
    foreach ($params as $key => $value){
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $weeks =$stmt->fetchAll(PDO::FETCH_ASSOC)
    
    // TODO: Process each week's links field
    // Decode the JSON string back to an array using json_decode()
    foreach($weeks as &$week){
        if(!empty($week['links'])){
            $decoded =json_decode($week['links'], true);
            $week['links'] = is_array($decoded) ? $decoded : [];
        }else{
            $week['links'] =[];
        }
    }
    
    // TODO: Return JSON response with success status and data
    // Use sendResponse() helper function
    sendResponse([
        'success' => true,
        'data'=> $weeks
    ], 200);
}


/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if(empty($weekId)){
        sendError("Validate that week_id is provided",400);
    }
    
    // TODO: Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
    $sql = "week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = :week_id ";
    $stmt =$db->prepare($sql);
    // TODO: Bind the week_id parameter
    $stmt->bindValue(':week_id',$weekId,PDD::PARAM_STR);
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch the result
    $week = $stmt->fetch(PDD:FETCH_ASSOC);
    // TODO: Check if week exists
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
    if($week){
        if(!empty($week['links'])){
            $decoded =json_decode($week['link'], true);
            $week['links']=is_array($decoded) ? $decoded : [] ;
        }else{
            $week['links']=[];
        
        }
        sendResponse([
            'success' => true,
            'data' => $week
        ],200)
    }else{
        sendError('week not found',404)
    }
}


/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    if(
        empty($data['week_id']) ||
        empty($data['title']) ||
        empty($data['start_date']) ||
        empty($data['description'])
    ){
        sendError('week_id, title, start_date, and description are required', 400);
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from title, description, and week_id
    $weekId      = sanitizeInput($data['week_id']);
    $title       = sanitizeInput($data['title']);
    $startDate   = trim($data['start_date']);
    $description = sanitizeInput($data['description']);

    
    // TODO: Validate start_date format
    // Use a regex or DateTime::createFromFormat() to verify YYYY-MM-DD format
    // If invalid, return error response with 400 status    
    if(!validateDate($startDate)) {
        sendError('Invalid start_date format. Expected YYYY-MM-DD', 400);
    }
    // TODO: Check if week_id already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $checksql ="SELECT ID from weeks WHERE week_id = week_id LIMIT 1";
    $checkstmt = $db->prepare($checksql);
    $checkstmt->bindValue(':week_id',$weekId, PDO::PARM_STR);
    $checkstmt->execute();

    if($checkstmt->fetch(PDO::FETCH_ASSOC)){
        sendError('week ID already exists', 409)
    }

    
    // TODO: Handle links array
    // If links is provided and is an array, encode it to JSON using json_encode()
    // If links is not provided, use an empty array []
    if(isset($data['links']) && is_array($data['links'])){
        $linksJson = json_encode([], JSON_UNESCAPED_UNICODE);
    }else{
        $linksJson = json_encode([],JSON_UNESCAPED_UNICODE );
    }
    
    
    // TODO: Prepare INSERT query
    // INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)
    $sql ="INSERT INTO weeks (week_id, title, start_date, description, links)
    VALUES (:week_ID, :title, :start_data, :description, links)";
    $stmt =$db->prepare($sql);
    // TODO: Bind parameters
    $stmt->bindValue(':week_id', $weekId, PDO::PARAM_STR);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
    $stmt->bindValue(':links', $linksJson, PDO::PARAM_STR);
    // TODO: Execute the query
    $result =$stmt->execute();
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created) and the new week data
    // If no, return error response with 500 status
    if($result){
        $responsedata =[
            'week_id'   =>$weekId
            'title'     =>$title
            'start_data' =>$startDate
            'description' =>$description
            'links'     =>json_decode($linksJson,true)
        ];
        sendResponse([
            'success' => true,
            'data'    => $responseData
        ],201)
    }else{
        sendResponse("faild to creat week",500)
    }
}


/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if(empty($data['week_id'])){
        sendError('week_id is required',400);
    }
    $weekId =sanitizeInput($data['week_id']);
    // TODO: Check if week exists
    // Prepare and execute a SELECT query to find the week
    // If not found, return error response with 404 status
    $checkSql  = "SELECT week_id FROM weeks WHERE week_id = :week_id LIMIT 1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':week_id', $weekId, PDO::PARAM_STR);
    $checkStmt->execute();

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendError('Week not found', 404);
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize an array to hold SET clauses
    // Initialize an array to hold values for binding
    $fields = [];
    $values = [];
    // TODO: Check which fields are provided and add to SET clauses
    // If title is provided, add "title = ?"
    if (isset($data['title'])) {
        $title    = sanitizeInput($data['title']);
        $fields[] = "title = ?";
        $values[] = $title;
    }
    // If start_date is provided, validate format and add "start_date = ?"
    // If description is provided, add "description = ?"
    // If links is provided, encode to JSON and add "links = ?"
    
    // TODO: If no fields to update, return error response with 400 status
    
    // TODO: Add updated_at timestamp to SET clauses
    // Add "updated_at = CURRENT_TIMESTAMP"
    
    // TODO: Build the complete UPDATE query
    // UPDATE weeks SET [clauses] WHERE week_id = ?
    
    // TODO: Prepare the query
    
    // TODO: Bind parameters dynamically
    // Bind values array and then bind week_id at the end
    
    // TODO: Execute the query
    
    // TODO: Check if update was successful
    // If yes, return success response with updated week data
    // If no, return error response with 500 status
}


/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    
    // TODO: Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    
    // TODO: Delete associated comments first (to maintain referential integrity)
    // Prepare DELETE query for comments table
    // DELETE FROM comments WHERE week_id = ?
    
    // TODO: Execute comment deletion query
    
    // TODO: Prepare DELETE query for week
    // DELETE FROM weeks WHERE week_id = ?
    
    // TODO: Bind the week_id parameter
    
    // TODO: Execute the query
    
    // TODO: Check if delete was successful
    // If yes, return success response with message indicating week and comments deleted
    // If no, return error response with 500 status
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    
    // TODO: Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
    
    // TODO: Bind the week_id parameter
    
    // TODO: Execute the query
    
    // TODO: Fetch all results as an associative array
    
    // TODO: Return JSON response with success status and data
    // Even if no comments exist, return an empty array
}


/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    
    // TODO: Validate that text is not empty after trimming
    // If empty, return error response with 400 status
    
    // TODO: Check if the week exists
    // Prepare and execute a SELECT query on weeks table
    // If week not found, return error response with 404 status
    
    // TODO: Prepare INSERT query
    // INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)
    
    // TODO: Bind parameters
    
    // TODO: Execute the query
    
    // TODO: Check if insert was successful
    // If yes, get the last insert ID and return success response with 201 status
    // Include the new comment data in the response
    // If no, return error response with 500 status
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Resource: comments
 * 
 * Query Parameters or JSON Body:
 *   - id: The comment ID to delete
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided
    // If not, return error response with 400 status
    
    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    
    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
    
    // TODO: Bind the id parameter
    
    // TODO: Execute the query
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Determine the resource type from query parameters
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
    
    
    // Route based on resource type and HTTP method
    
    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            // TODO: Check if week_id is provided in query parameters
            // If yes, call getWeekById()
            // If no, call getAllWeeks() to get all weeks (with optional search/sort)
            
        } elseif ($method === 'POST') {
            // TODO: Call createWeek() with the decoded request body
            
        } elseif ($method === 'PUT') {
            // TODO: Call updateWeek() with the decoded request body
            
        } elseif ($method === 'DELETE') {
            // TODO: Get week_id from query parameter or request body
            // Call deleteWeek()
            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        
        if ($method === 'GET') {
            // TODO: Get week_id from query parameters
            // Call getCommentsByWeek()
            
        } elseif ($method === 'POST') {
            // TODO: Call createComment() with the decoded request body
            
        } elseif ($method === 'DELETE') {
            // TODO: Get comment id from query parameter or request body
            // Call deleteComment()
            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
        }
    }
    
    // ========== INVALID RESOURCE ==========
    else {
        // TODO: Return error for invalid resource
        // Set HTTP status to 400 (Bad Request)
        // Return JSON error message: "Invalid resource. Use 'weeks' or 'comments'"
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
    
    // TODO: Return generic error response with 500 status
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // Use http_response_code($statusCode)
    
    // TODO: Echo JSON encoded data
    // Use json_encode($data)
    
    // TODO: Exit to prevent further execution
}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    // TODO: Create error response array
    // Structure: ['success' => false, 'error' => $message]
    
    // TODO: Call sendResponse() with the error array and status code
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat() to validate
    // Format: 'Y-m-d'
    // Check that the created date matches the input string
    // Return true if valid, false otherwise
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    
    // TODO: Strip HTML tags using strip_tags()
    
    // TODO: Convert special characters using htmlspecialchars()
    
    // TODO: Return sanitized data
}


/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */
function isValidSortField($field, $allowedFields) {
    // TODO: Check if $field exists in $allowedFields array
    // Use in_array()
    // Return true if valid, false otherwise
}

?>
