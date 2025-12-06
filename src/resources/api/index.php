
 * This is a RESTful API that handles all CRUD operations for course resources 
 * and their associated comments/discussions.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: resources
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(255))
 *   - description (TEXT)
 *   - link (VARCHAR(500))
 *   - created_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - resource_id (INT, FOREIGN KEY references resources.id)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve resource(s) or comment(s)
 *   - POST: Create a new resource or comment
 *   - PUT: Update an existing resource
 *   - DELETE: Delete a resource or comment
 * 
 * Response Format: JSON
 * 
 * API Endpoints:
 *   Resources:
 *     GET    /api/resources.php                    - Get all resources
 *     GET    /api/resources.php?id={id}           - Get single resource by ID
 *     POST   /api/resources.php                    - Create new resource
 *     PUT    /api/resources.php                    - Update resource
 *     DELETE /api/resources.php?id={id}           - Delete resource
 * 
 *   Comments:
 *     GET    /api/resources.php?resource_id={id}&action=comments  - Get comments for resource
 *     POST   /api/resources.php?action=comment                    - Create new comment
 *     DELETE /api/resources.php?comment_id={id}&action=delete_comment - Delete comment
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// TODO: Handle preflight OPTIONS request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
require_once "../config/Database.php";

// TODO: Get the PDO database connection
$database = new Database();
$db = $database->getConnection();

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$rawInput = file_get_contents("php://input");
$inputData = json_decode($rawInput, true);

// TODO: Parse query parameters
$action = $_GET["action"] ?? null;
$id = $_GET["id"] ?? null;
$resource_id = $_GET["resource_id"] ?? null;
$comment_id = $_GET["comment_id"] ?? null;


// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

function getAllResources($db) {
    $search = $_GET["search"] ?? null;
    $sort = $_GET["sort"] ?? "created_at";
    $order = $_GET["order"] ?? "desc";

    // Validate sort fields
    $allowedSort = ["title", "created_at"];
    if (!in_array($sort, $allowedSort)) $sort = "created_at";

    // Validate order
    $order = strtolower($order);
    if (!in_array($order, ["asc", "desc"])) $order = "desc";

    // Base query
    $sql = "SELECT id, title, description, link, created_at FROM resources";

    // Search filter
    if ($search) {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
    }

    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);

    if ($search) {
        $searchTerm = "%$search%";
        $stmt->bindParam(":search", $searchTerm);
    }

    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(["success" => true, "data" => $resources]);
}


function getResourceById($db, $resourceId) {
    if (!is_numeric($resourceId)) {
        sendResponse(["success" => false, "message" => "Invalid resource ID"], 400);
    }

    $stmt = $db->prepare(
        "SELECT id, title, description, link, created_at 
         FROM resources WHERE id = ?"
    );

    $stmt->bindParam(1, $resourceId);
    $stmt->execute();

    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resource) {
        sendResponse(["success" => true, "data" => $resource]);
    } else {
        sendResponse(["success" => false, "message" => "Resource not found"], 404);
    }
}


function createResource($db, $data) {
    // Required fields
    $required = ["title", "link"];
    $check = validateRequiredFields($data, $required);

    if (!$check["valid"]) {
        sendResponse(["success" => false, "message" => "Missing fields", "missing" => $check["missing"]], 400);
    }

    $title = sanitizeInput($data["title"]);
    $description = sanitizeInput($data["description"] ?? "");
    $link = sanitizeInput($data["link"]);

    if (!validateUrl($link)) {
        sendResponse(["success" => false, "message" => "Invalid URL"], 400);
    }

    $stmt = $db->prepare(
        "INSERT INTO resources (title, description, link) VALUES (?, ?, ?)"
    );

    $stmt->bindParam(1, $title);
    $stmt->bindParam(2, $description);
    $stmt->bindParam(3, $link);

    if ($stmt->execute()) {
        sendResponse([
            "success" => true,
            "message" => "Resource created",
            "id" => $db->lastInsertId()
        ], 201);
    } else {
        sendResponse(["success" => false, "message" => "Failed to create"], 500);
    }
}


function updateResource($db, $data) {
    if (empty($data["id"])) {
        sendResponse(["success" => false, "message" => "Resource ID is required"], 400);
    }

    $id = $data["id"];

    // Check if resource exists
    $stmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $stmt->bindParam(1, $id);
    $stmt->execute();

    if (!$stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Resource not found"], 404);
    }

    // Dynamic updates
    $fields = [];
    $values = [];

    if (!empty($data["title"])) {
        $fields[] = "title = ?";
        $values[] = sanitizeInput($data["title"]);
    }
    if (!empty($data["description"])) {
        $fields[] = "description = ?";
        $values[] = sanitizeInput($data["description"]);
    }
    if (!empty($data["link"])) {
        if (!validateUrl($data["link"])) {
            sendResponse(["success" => false, "message" => "Invalid URL"], 400);
        }
        $fields[] = "link = ?";
        $values[] = sanitizeInput($data["link"]);
    }

    if (empty($fields)) {
        sendResponse(["success" => false, "message" => "No fields to update"], 400);
    }

    $sql = "UPDATE resources SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);

    foreach ($values as $i => $value) {
        $stmt->bindValue($i + 1, $value);
    }
    $stmt->bindValue(count($values) + 1, $id);

    if ($stmt->execute()) {
        sendResponse(["success" => true, "message" => "Resource updated"]);
    } else {
        sendResponse(["success" => false, "message" => "Update failed"], 500);
    }
}


function deleteResource($db, $resourceId) {
    if (!is_numeric($resourceId)) {
        sendResponse(["success" => false, "message" => "Invalid ID"], 400);
    }

    // Check exists
    $stmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $stmt->bindParam(1, $resourceId);
    $stmt->execute();

    if (!$stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Resource not found"], 404);
    }

    $db->beginTransaction();

    try {
        // Delete comments
        $delC = $db->prepare("DELETE FROM comments WHERE resource_id = ?");
        $delC->bindParam(1, $resourceId);
        $delC->execute();

        // Delete resource
        $delR = $db->prepare("DELETE FROM resources WHERE id = ?");
        $delR->bindParam(1, $resourceId);
        $delR->execute();

        $db->commit();
        sendResponse(["success" => true, "message" => "Resource deleted"]);
    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(["success" => false, "message" => "Deletion failed"], 500);
    }
}


// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

function getCommentsByResourceId($db, $resourceId) {
    if (!is_numeric($resourceId)) {
        sendResponse(["success" => false, "message" => "Invalid ID"], 400);
    }

    $stmt = $db->prepare(
        "SELECT id, resource_id, author, text, created_at
         FROM comments WHERE resource_id = ?
         ORDER BY created_at ASC"
    );

    $stmt->bindParam(1, $resourceId);
    $stmt->execute();

    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(["success" => true, "data" => $comments]);
}


function createComment($db, $data) {
    $required = ["resource_id", "author", "text"];
    $check = validateRequiredFields($data, $required);

    if (!$check["valid"]) {
        sendResponse(["success" => false, "missing" => $check["missing"]], 400);
    }

    if (!is_numeric($data["resource_id"])) {
        sendResponse(["success" => false, "message" => "Invalid resource ID"], 400);
    }

    // Ensure resource exists
    $stmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $stmt->bindParam(1, $data["resource_id"]);
    $stmt->execute();

    if (!$stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Resource not found"], 404);
    }

    $author = sanitizeInput($data["author"]);
    $text = sanitizeInput($data["text"]);

    $stmt = $db->prepare(
        "INSERT INTO comments (resource_id, author, text) VALUES (?, ?, ?)"
    );

    $stmt->bindParam(1, $data["resource_id"]);
    $stmt->bindParam(2, $author);
    $stmt->bindParam(3, $text);

    if ($stmt->execute()) {
        sendResponse([
            "success" => true,
            "message" => "Comment added",
            "id" => $db->lastInsertId()
        ], 201);
    } else {
        sendResponse(["success" => false, "message" => "Failed to add comment"], 500);
    }
}


function deleteComment($db, $commentId) {
    if (!is_numeric($commentId)) {
        sendResponse(["success" => false, "message" => "Invalid comment ID"], 400);
    }

    // Check exists
    $stmt = $db->prepare("SELECT id FROM comments WHERE id = ?");
    $stmt->bindParam(1, $commentId);
    $stmt->execute();

    if (!$stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Comment not found"], 404);
    }

    $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bindParam(1, $commentId);

    if ($stmt->execute()) {
        sendResponse(["success" => true, "message" => "Comment deleted"]);
    } else {
        sendResponse(["success" => false, "message" => "Failed to delete"], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {

    if ($method === "GET") {

        if ($action === "comments") {
            getCommentsByResourceId($db, $resource_id);
        }

        if ($id) {
            getResourceById($db, $id);
        }

        getAllResources($db);

    } elseif ($method === "POST") {

        if ($action === "comment") {
            createComment($db, $inputData);
        }

        createResource($db, $inputData);

    } elseif ($method === "PUT") {

        updateResource($db, $inputData);

    } elseif ($method === "DELETE") {

        if ($action === "delete_comment") {
            deleteComment($db, $comment_id);
        }

        deleteResource($db, $id);

    } else {
        sendResponse(["success" => false, "message" => "Method not allowed"], 405);
    }

} catch (Exception $e) {
    sendResponse(["success" => false, "message" => "Server error"], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);

    if (!is_array($data)) {
        $data = ["data" => $data];
    }

    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    return htmlspecialchars($data, ENT_QUOTES);
}

function validateRequiredFields($data, $requiredFields) {
    $missing = [];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }

    return [
        "valid" => count($missing) === 0,
        "missing" => $missing
    ];
}
?>
