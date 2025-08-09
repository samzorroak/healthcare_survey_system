<?php
include '../config.php'; // Ensure config.php is properly included
require_once 'EntityManager.php';

header('Content-Type: application/json');

// Get the database connection instance
$conn = Database::getInstance();

$response = ["success" => false, "message" => "Invalid request"];

/**
 * Function to generate a unique invitation link for each participant
 * @param int $survey_id
 * @param int $participant_id
 * @return string
 */
function generateInvitationLink($survey_id, $participant_id)
{
    $base_url = "https://yourdomain.com/survey_invitation.php"; // Update with your actual domain and endpoint
    $token = base64_encode($survey_id . ":" . $participant_id . ":" . uniqid());
    return $base_url . "?token=" . $token;
}



try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $entity = $_POST['entity'] ?? '';
        $id = $_POST['id'] ?? '';

        //USER MANAGEMENT
        if ($entity === "user") {
            if ($action === "read_single" && !empty($id)) {
                $stmt = $conn->prepare("SELECT `id`, `name`, `email`, `mobile`, `permissions`, `status` FROM `users` WHERE `id` = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $user['permissions'] = json_decode($user['permissions'], true); // Decode JSON string to array

                    $response = [
                        "success" => true,
                        "data" => $user,
                        "id" => $user['id'],
                        "name" => $user['name'],
                        "email" => $user['email'],
                        "mobile" => $user['mobile'],
                        "permissions" => $user['permissions'],
                        "status" => $user['status']
                    ];
                } else {
                    $response = ["success" => false, "error" => "User not found"];
                }
            } elseif ($action === "create") { // Add new user
                $name = $_POST['name'] ?? '';
                $email = $_POST['email'] ?? '';
                $mobile = $_POST['mobile'] ?? '';
                $password = $_POST['password'] ?? '';
                $permissions = $_POST['permissions'] ?? '';

                // Convert permissions array to a string (comma-separated) if it's an array
                if (is_array($permissions)) {
                    $permissions = json_encode($permissions); // Convert to JSON string
                    // $permissions = implode(",", $permissions);
                }

                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $response = ["success" => false, "message" => "Invalid email format"];
                    echo json_encode($response);
                    exit;
                }

                if (!empty($name) && !empty($email) && !empty($mobile) && !empty($password) && !empty($permissions)) {
                    // Hash the password before storing
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                    // Check if email already exists
                    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $checkStmt->execute([$email]);

                    if ($checkStmt->rowCount() > 0) {
                        $response = ["success" => false, "message" => "Email already exists"];
                    } else {
                        $stmt = $conn->prepare("INSERT INTO users (`name`, `email`, `mobile`, `password`, `permissions`) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$name, $email, $mobile, $hashedPassword, $permissions]);

                        if ($stmt->rowCount()) {
                            $response = ["success" => true, "message" => "User added successfully"];
                        } else {
                            $response = ["success" => false, "message" => "Failed to add user"];
                        }
                    }
                } else {
                    $response = ["success" => false, "message" => "Missing required fields"];
                }
            } elseif ($action === "update" && !empty($id)) {
                $name = $_POST['name'] ?? '';
                $email = $_POST['email'] ?? '';
                $mobile = $_POST['mobile'] ?? '';
                $status = $_POST['status'] ?? 'Inactive';
                $permissions = $_POST['permissions'] ?? [];  // will be empty if none checked

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(["success" => false, "message" => "Invalid email format"]);
                    exit;
                }

                $permissions = isset($_POST['permissions']) && is_array($_POST['permissions'])
                    ? array_values(array_unique($_POST['permissions']))
                    : [];

                $permissions_json = json_encode($permissions);

                if (!empty($name) && !empty($email) && !empty($mobile)) {
                    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $checkStmt->execute([$email, $id]);

                    if ($checkStmt->rowCount() > 0) {
                        echo json_encode(["success" => false, "message" => "Email already exists"]);
                    } else {
                        // Update all fields including empty permissions
                        $stmt = $conn->prepare("UPDATE users SET `name` = ?, `email` = ?, `mobile` = ?, `permissions` = ?, `status` = ? WHERE `id` = ?");
                        $stmt->execute([$name, $email, $mobile, $permissions_json, $status, $id]);

                        file_put_contents('debug_log.txt', print_r($_POST, true));

                        echo json_encode([
                            "success" => true,
                            "message" => "User updated successfully"
                        ]);
                    }
                } else {
                    echo json_encode(["success" => false, "message" => "Missing required fields"]);
                }

                exit;
            } elseif ($action === "delete" && !empty($id)) {
                session_start(); // ensure session is active
                $UserPermissions = $_SESSION['permissions'] ?? [];
                $perm = 'manage_users';
                if (!(str_contains($UserPermissions, $perm))) {
                    $response = ["success" => false, "message" => "You do not have permission to delete users."];
                    echo json_encode($response);
                    exit;
                }
                
                $stmt = $conn->prepare("DELETE FROM `users` WHERE `id` = ?");
                $stmt->execute([$id]);

                if ($stmt->rowCount()) {
                    $response = ["success" => true, "message" => "User deleted successfully"];
                } else {
                    $response = ["success" => false, "message" => "User not found or already deleted"];
                }

                echo json_encode($response);
                exit;
            } elseif ($action === "search") {
                $query = $_POST['query'] ?? '';

                if (!empty($query)) {
                    $searchTerm = "%$query%";
                    $stmt = $conn->prepare("SELECT id, name, email, permissions FROM users 
                                WHERE id LIKE ? OR name LIKE ? OR permissions LIKE ?");
                    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($users) {
                        $response = ["success" => true, "data" => $users];
                    } else {
                        $response = ["success" => false, "message" => "No users found"];
                    }
                } else {
                    $response = ["success" => false, "message" => "Empty search query"];
                }
            } elseif ($action === "fetch_sorted") {
                $sortColumn = $_POST['sortColumn'] ?? 'id'; // Default sorting by ID
                $sortOrder = $_POST['sortOrder'] ?? 'ASC';  // Default sorting in ascending order

                // Ensure valid column to prevent SQL injection
                $allowedColumns = ['id', 'name'];
                if (!in_array($sortColumn, $allowedColumns)) {
                    $sortColumn = 'id';
                }

                // Ensure valid order
                $sortOrder = ($sortOrder === 'DESC') ? 'DESC' : 'ASC';

                $stmt = $conn->prepare("SELECT id, name, email, permissions FROM users ORDER BY $sortColumn $sortOrder");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($users) {
                    $response = ["success" => true, "data" => $users];
                } else {
                    $response = ["success" => false, "message" => "No users found"];
                }
            }
        }



        //PARTICIPANT MANAGEMENT
        elseif ($entity === "participant") {
            if ($action === "read_single" && !empty($id)) {
                $stmt = $conn->prepare("SELECT * FROM participants WHERE id = ?");
                $stmt->execute([$id]);
                $participant = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($participant) {
                    $response = [
                        "success" => true,
                        "data" => $participant,
                        "id" => $participant['id'],
                        "full_name" => $participant['full_name'],
                        "email" => $participant['email'],
                        "mobile" => $participant['mobile'],
                        "dob" => $participant['dob'],
                        "qualification" => $participant['qualification'],
                        "specialization" => $participant['specialization'],
                        "pan" => $participant['pan'],
                        "registration_id" => $participant['registration_id'],
                        "address" => $participant['address'],
                        "city" => $participant['city'],
                        "state" => $participant['state'],
                        "country" => $participant['country'],
                        "zip" => $participant['zip'],
                        "cancel_cheque" => $participant['cancel_cheque'],
                        "password" => $participant['password'],
                        "status" => $participant['status']
                    ];
                } else {
                    $response = ["success" => false, "message" => "Participant not found"];
                }
            } elseif ($action === "create") {
                $name = $_POST['full_name'] ?? '';
                $email = $_POST['email'] ?? '';
                $mobile = $_POST['mobile'] ?? '';
                $dob = $_POST['dob'] ?? '';
                $qualification = $_POST['qualification'] ?? '';
                $specialization = $_POST['specialization'] ?? '';
                $pan = $_POST['pan'] ?? '';
                $registration_id = $_POST['registration_id'] ?? '';
                $address = $_POST['address'] ?? '';
                $city = $_POST['city'] ?? '';
                $state = $_POST['state'] ?? '';
                $country = $_POST['country'] ?? '';
                $zip = $_POST['zip'] ?? '';
                $status = $_POST['status'] ?? '';

                // Generate password from PAN number and DOB year
                $pan_prefix = substr($pan, 0, 4); // First four letters of PAN
                $birth_year = date('Y', strtotime($dob)); // Extract year from DOB
                $password = $pan_prefix . $birth_year; // Combine
                $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Securely hash password

                $cancel_cheque = null;
                if (isset($_FILES['cancel_cheque']) && $_FILES['cancel_cheque']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['cancel_cheque']['tmp_name'];
                    $fileName = $_FILES['cancel_cheque']['name'];
                    $fileSize = $_FILES['cancel_cheque']['size'];
                    $fileType = $_FILES['cancel_cheque']['type'];

                    // Define the allowed file types and maximum file size
                    $allowedTypes = ['application/pdf', 'image/png', 'image/jpg', 'image/jpeg'];
                    $maxSize = 10 * 1024 * 1024; // 10MB max

                    // Check if the file type and size are valid
                    if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
                        // Generate a unique name for the file
                        $newFileName = uniqid() . '_' . $fileName;
                        $uploadPath = './uploads/cancel_cheques/' . $newFileName;

                        // Move the uploaded file to the server's directory
                        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                            $cancel_cheque = $newFileName;
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Error uploading the file.']);
                            exit;
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Invalid file type or size exceeded.']);
                        exit;
                    }
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(["success" => false, "message" => "Invalid email format"]);
                    exit;
                }

                // Check if email already exists (Fixed variable name)
                $checkStmt = $conn->prepare("SELECT `id` FROM `participants` WHERE `email` = ? AND `id` != ?");
                $checkStmt->execute([$email, $id]);

                if ($checkStmt->rowCount() > 0) {
                    echo json_encode(["success" => false, "message" => "Email already exists"]);
                    exit;
                } elseif (!empty($name) && !empty($email) && !empty($mobile)) {
                    if ($cancel_cheque) {
                        $stmt = $conn->prepare("INSERT INTO participants (full_name, email, mobile, dob, qualification, specialization, pan, registration_id, address, city, state, country, zip, cancel_cheque, status, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$name, $email, $mobile, $dob, $qualification, $specialization, $pan, $registration_id, $address, $city, $state, $country, $zip, $cancel_cheque, $status, $hashed_password]);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO participants (full_name, email, mobile, dob, qualification, specialization, pan, registration_id, address, city, state, country, zip, status, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$name, $email, $mobile, $dob, $qualification, $specialization, $pan, $registration_id, $address, $city, $state, $country, $zip, $status, $hashed_password]);
                    }
                    if ($stmt->rowCount()) {
                        $response = ["success" => true, "message" => "Participant added successfully"];
                    } else {
                        $response = ["success" => false, "message" => "Failed to add participant"];
                    }
                } else {
                    $response = ["success" => false, "message" => "Missing required fields"];
                }
            } elseif ($action === "update" && !empty($id)) {
                $name = $_POST['full_name'] ?? '';
                $email = $_POST['email'] ?? '';
                $mobile = $_POST['mobile'] ?? '';
                $dob = $_POST['dob'] ?? '';
                $qualification = $_POST['qualification'] ?? '';
                $specialization = $_POST['specialization'] ?? '';
                $pan = $_POST['pan'] ?? '';
                $registration_id = $_POST['registration_id'] ?? '';
                $address = $_POST['address'] ?? '';
                $city = $_POST['city'] ?? '';
                $state = $_POST['state'] ?? '';
                $country = $_POST['country'] ?? '';
                $zip = $_POST['zip'] ?? '';
                $status = $_POST['status'] ?? '';

                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(["success" => false, "message" => "Invalid email format"]);
                    exit;
                }

                // Generate a new password only if PAN number or DOB is changed
                $stmt = $conn->prepare("SELECT pan, dob FROM participants WHERE id = ?");
                $stmt->execute([$id]);
                $oldData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($oldData && ($oldData['pan'] !== $pan || $oldData['dob'] !== $dob)) {
                    $pan_prefix = substr($pan, 0, 4);
                    $birth_year = date('Y', strtotime($dob));
                    $password = $pan_prefix . $birth_year;
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                } else {
                    $hashed_password = null;
                }

                // Check if email already exists (Fixed variable name)
                $checkStmt = $conn->prepare("SELECT `id` FROM `participants` WHERE `email` = ? AND `id` != ?");
                $checkStmt->execute([$email, $id]);

                if ($checkStmt->rowCount() > 0) {
                    echo json_encode(["success" => false, "message" => "Email already exists"]);
                    exit;
                } else {
                    // Handling file upload
                    $cancel_cheque = null;
                    if (isset($_FILES['cancel_cheque']) && $_FILES['cancel_cheque']['error'] === UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES['cancel_cheque']['tmp_name'];
                        $fileName = $_FILES['cancel_cheque']['name'];
                        $fileSize = $_FILES['cancel_cheque']['size'];
                        $fileType = $_FILES['cancel_cheque']['type'];

                        // Define the allowed file types and maximum file size
                        $allowedTypes = ['application/pdf', 'image/png', 'image/jpg', 'image/jpeg'];
                        $maxSize = 10 * 1024 * 1024; // 10MB max

                        // Check if the file type and size are valid
                        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
                            // Generate a unique name for the file
                            $newFileName = uniqid() . '_' . $fileName;
                            $uploadPath = './uploads/cancel_cheques/' . $newFileName;

                            // Move the uploaded file to the server's directory
                            if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                                $cancel_cheque = $newFileName;
                            } else {
                                echo json_encode(['success' => false, 'message' => 'Error uploading the file.']);
                                exit;
                            }
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Invalid file type or size exceeded.']);
                            exit;
                        }
                    }
                    try {
                        // Update participant details and optionally the agreement file
                        $updateQuery = "UPDATE participants SET full_name=?, email=?, mobile=?, dob=?, qualification=?, specialization=?, pan=?, registration_id=?, address=?, country=?, state=?, city=?, zip=?, status=?";
                        $params = [$full_name, $email, $mobile, $dob, $qualification, $specialization, $pan, $registration_id, $address, $country, $state, $city, $zip, $status];

                        if ($cancel_cheque) {
                            $updateQuery .= ", cancel_cheque=?";
                            $params[] = $cancel_cheque;
                        }

                        if ($hashed_password) {
                            $updateQuery .= ", password=?";
                            $params[] = $hashed_password;
                        }

                        $updateQuery .= " WHERE id=?";
                        $params[] = $id;

                        $stmt = $conn->prepare($updateQuery);
                        $stmt->execute($params);


                        // Return updated participant data
                        $stmt = $conn->prepare("SELECT * FROM particpants WHERE id = ?");
                        $stmt->execute([$id]);
                        $participant = $stmt->fetch(PDO::FETCH_ASSOC);

                        echo json_encode(['success' => true, 'participant' => $participant]);
                    } catch (Exception $e) {
                        echo json_encode(['success' => false, 'message' => 'Error updating client: ' . $e->getMessage()]);
                    }
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $response = ["success" => false, "message" => "Invalid email format"];
                } elseif (!empty($name) && !empty($email) && !empty($mobile)) {
                    $stmt = $conn->prepare("UPDATE participants SET full_name = ?, email = ?, mobile = ?, dob = ?, qualification = ?, specialization = ?, pan = ?, registration_id = ?, address = ?, city = ?, state = ?, zip = ?, status = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $mobile, $dob, $qualification, $specialization, $pan, $registration_id, $address, $city, $state, $zip, $status, $id]);
                    $response = $stmt->rowCount() ? ["success" => true, "message" => "Participant updated successfully"] : ["success" => false, "message" => "No changes made"];
                } else {
                    $response = ["success" => false, "message" => "Missing required fields"];
                }
            } elseif ($action === "delete" && !empty($id)) {

                $stmt = $conn->prepare("DELETE FROM `participants` WHERE `id` = ?");
                $stmt->execute([$id]);

                if ($stmt->rowCount()) {
                    $response = ["success" => true, "message" => "Participant deleted successfully"];
                } else {
                    $response = ["success" => false, "message" => "Participant not found or already deleted"];
                }
            } elseif ($action === "search") {
                $query = $_POST['query'] ?? '';

                $stmt = $conn->prepare("SELECT * FROM participants WHERE id LIKE ? OR full_name LIKE ? OR qualification LIKE ? OR specialization LIKE ?");
                $searchQuery = "%$query%";
                $stmt->execute([$searchQuery, $searchQuery, $searchQuery, $searchQuery]);
                $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($participants) {
                    echo json_encode(["success" => true, "data" => $participants]);
                } else {
                    echo json_encode(["success" => false, "message" => "No participants found"]);
                }
                exit;
            } elseif ($action === "fetch_sorted") {
                $sortColumn = $_POST['sortColumn'] ?? 'id';
                $sortOrder = $_POST['sortOrder'] ?? 'ASC';

                $validColumns = ["id", "full_name", "specialization"];
                if (!in_array($sortColumn, $validColumns)) {
                    $sortColumn = "id";
                }

                $query = "SELECT * FROM participants ORDER BY $sortColumn $sortOrder";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(["success" => true, "data" => $participants]);
                exit;
            } elseif ($action === "view_participant" && !empty($id)) {
                $stmt = $conn->prepare("SELECT * FROM participants WHERE id = ?");
                $stmt->execute([$id]);
                $participant = $stmt->fetch(PDO::FETCH_ASSOC);


                if ($participant) {
                    $response = [
                        "success" => true,
                        "data" => $participant,
                        "modalContent" => "
                        <strong>Name:</strong> {$participant['full_name']}<br>
                        <strong>Email:</strong> {$participant['email']}<br>
                        <strong>Mobile:</strong> {$participant['mobile']}<br>
                        <strong>Qualification:</strong> {$participant['qualification']}<br>
                        <strong>Specialization:</strong> {$participant['specialization']}<br>
                        <strong>Address:</strong> {$participant['address']}, {$participant['city']}, {$participant['state']}, {$participant['country']}<br>
                        <strong>Status:</strong> {$participant['status']}<br>
                        "
                    ];
                } else {
                    $response = ["success" => false, "message" => "Client not found"];
                }
            }
        }




        //SURVEY MANAGEMENT
        elseif ($entity === "survey") {
            $surveyManager = new EntityManager('surveys');

            if ($action === 'read') {
                $surveys = $surveyManager->fetchAll();
                foreach ($surveys as $survey) {
                    echo "<tr>
                        <td>{$survey['id']}</td>
                        <td>{$survey['title']}</td>
                        <td>{$client['organization_name']}</td>
                        <td>₹{$survey['amount']}</td>
                        <td>{$survey['status']}</td>
                        <td>
                            <button class='btn btn-info view-survey-btn' data-id='{$survey['id']}'>View</button>
                            <button class='btn btn-warning edit-survey-btn' data-id='{$survey['id']}'>Edit</button>
                            <button class='btn btn-danger delete-survey-btn' data-id='{$survey['id']}'>Delete</button>
                        </td>
                    </tr>";
                }
            } elseif ($action === 'read_single') {
                try {
                    $stmt1 = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
                    $stmt1->execute([$id]);
                    $survey = $stmt1->fetch(PDO::FETCH_ASSOC);

                    if (!$survey) {
                        echo json_encode(["success" => false, "message" => "Survey not found"]);
                        exit;
                    }

                    // Fetch Client Information
                    $stmt2 = $conn->prepare("SELECT organization_name FROM clients WHERE id = ?");
                    $stmt2->execute([$survey['client_id']]);
                    $client = $stmt2->fetch(PDO::FETCH_ASSOC);

                    // Fetch Participants
                    $stmt3 = $conn->prepare("SELECT p.id, p.full_name, p.email, i.email_sent, i.invitation_link 
                                             FROM participants p
                                             JOIN survey_participants i ON p.id = i.participant_id
                                             WHERE i.survey_id = ?");
                    $stmt3->execute([$id]);
                    $participants = $stmt3->fetchAll(PDO::FETCH_ASSOC);

                    // Fetch Questions and Options
                    $stmt = $conn->prepare("SELECT `id`, `question_text`, `question_type` FROM `questions` WHERE `survey_id` = ?");
                    $stmt->execute([$id]);
                    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($questions as &$question) {
                        $stmt = $conn->prepare("SELECT `id`, `answer_text` FROM answers WHERE question_id = ?");
                        $stmt->execute([$question['id']]);
                        $question['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    $response = [
                        "success" => true,
                        "id" => $survey['id'],
                        "title" => $survey['title'],
                        "client_id" => $survey['client_id'],
                        "client" => $client['organization_name'] ?? "Unknown",
                        "description" => $survey['description'],
                        "amount" => $survey['amount'],
                        "start_date" => $survey['start_date'],
                        "end_date" => $survey['end_date'],
                        "status" => $survey['status'],
                        "participants" => $participants,
                        "questions" => $questions
                    ];

                    echo json_encode($response);
                    exit;
                } catch (Exception $e) {
                    echo json_encode(["success" => false, "message" => "Error retrieving survey: " . $e->getMessage()]);
                    exit;
                }
            } elseif ($action === 'create') {
                try {
                    // Ensure required fields exist
                    if (!isset($_POST['client_id'], $_POST['title'], $_POST['description'], $_POST['amount'], $_POST['start_date'], $_POST['end_date'], $_POST['status'])) {
                        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                        exit;
                    }

                    // Retrieve form data
                    $client_id = $_POST['client_id'];
                    $title = $_POST['title'];
                    $description = $_POST['description'];
                    $amount = $_POST['amount'];
                    $start_date = $_POST['start_date'];
                    $end_date = $_POST['end_date'];
                    $status = $_POST['status'];
                    $participants = isset($_POST['participants']) ? json_decode($_POST['participants'], true) : [];
                    $participantIds = $_POST['participants'] ?? [];




                    // Check if client ID exists
                    $stmtCheck = $conn->prepare("SELECT id FROM clients WHERE id = ?");
                    $stmtCheck->execute([$client_id]);
                    if ($stmtCheck->rowCount() === 0) {
                        echo json_encode(['success' => false, 'message' => 'Invalid client selected']);
                        exit;
                    }

                    $conn->beginTransaction();

                    // Insert survey details
                    $stmt = $conn->prepare("INSERT INTO surveys (`client_id`, `title`, `description`, `amount`, `start_date`, `end_date`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$client_id, $title, $description, $amount, $start_date, $end_date, $status]);

                    // Get the last inserted survey ID
                    $survey_id = $conn->lastInsertId();

                    // Insert participants into survey_participants table
                    if (!empty($participants)) {
                        //set amount
                        $participantCount = 0;
                        foreach ($participants as $pt) {
                            $participantCount++;
                        }
                        $individualAmount = $participantCount > 0 ? ($amount / $participantCount) : 0;

                        // if (is_array($participantIds) || $participantIds instanceof Countable){
                        //     $participantCount = count($participantIds);
                        // } else {
                        //     $participantCount = 0; // Default to 0 if not countable
                        //     error_log("Warning: count() called on a non-countable type in manage_entity.php");
                        // }
                        // $surveyAmount = floatval($_POST['amount']); // Get survey amount from form
                        // $individualAmount = $participantCount > 0 ? round($surveyAmount / $participantCount, 2) : 0;


                        // Fetch participant details in bulk
                        $inQuery = implode(',', array_fill(0, count($participants), '?'));
                        $stmtFetchParticipants = $conn->prepare("SELECT id, full_name, email, mobile, specialization FROM participants WHERE id IN ($inQuery)");
                        $stmtFetchParticipants->execute($participants);
                        $participantData = $stmtFetchParticipants->fetchAll(PDO::FETCH_ASSOC);

                        $insertStmt = $conn->prepare("INSERT INTO survey_participants (survey_id, participant_id, full_name, email, mobile, specialization, payment_status, payment_amount, email_sent, invitation_link) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, 0, ?)");

                        foreach ($participantData as $participant) {
                            $invitation_link = "https://yourdomain.com/invite.php?survey=" . $survey_id . "&participant=" . $participant['id'];
                            $insertStmt->execute([
                                $survey_id,
                                $participant['id'],
                                $participant['full_name'],
                                $participant['email'],
                                $participant['mobile'],
                                $participant['specialization'],
                                $individualAmount,
                                $invitation_link
                            ]);
                        }
                    }

                    //Inserting Questions
                    $pdo = Database::getInstance();

                    $questions = isset($_POST['questions']) ? json_decode($_POST['questions'], true) : [];

                    if (!is_array($questions)) {
                        $questions = [];
                    }

                    foreach ($questions as $question) {
                        $question_text = $question['question_text'] ?? '';
                        $question_type = $question['question_type'] ?? 'single';
                        $required = $question['required'] ?? 0;
                        $options = $question['options'] ?? [];

                        if ($question_text && is_array($options) && count($options) > 1) {
                            // Insert question
                            $stmt = $pdo->prepare("INSERT INTO questions (`survey_id`, `question_text`, `question_type`, `required`) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$survey_id, $question_text, $question_type, $required]);
                            $question_id = $pdo->lastInsertId();

                            // Insert options
                            $stmt_option = $pdo->prepare("INSERT INTO answers (`question_id`, `answer_text`) VALUES (?, ?)");
                            foreach ($options as $option) {
                                $stmt_option->execute([$question_id, $option]);
                            }
                        }
                    }

                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Survey, invitations, and questionnaire created successfully!']);
                    exit;
                } catch (Exception $e) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Error creating survey: ' . $e->getMessage()]);
                    exit;
                }
            } elseif ($action === 'fetch_survey') {
                $survey_id = $_POST['survey_id'];
                try {
                    // Fetch survey details
                    $stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
                    $stmt->execute([$survey_id]);
                    $survey = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$survey) {
                        echo json_encode(['success' => false, 'message' => 'Survey not found']);
                        exit;
                    }

                    // Fetch selected participant IDs
                    $stmt = $conn->prepare("SELECT participant_id FROM survey_participants WHERE survey_id = ?");
                    $stmt->execute([$survey_id]);
                    $selectedParticipantIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    // Fetch all participants
                    $stmt = $conn->query("SELECT * FROM participants WHERE status = 'active'");
                    $allParticipants = $stmt->fetchAll(PDO::FETCH_ASSOC);



                    // Fetch questions and options for the survey
                    $stmt = $conn->prepare("SELECT * FROM `questions` WHERE `survey_id` = ?");
                    if (!$stmt->execute([$survey_id])) {
                        error_log("Error executing query: " . implode(", ", $stmt->errorInfo()));
                    }
                    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($questions as &$question) {
                        $stmt = $conn->prepare("SELECT `id`, `answer_text` FROM answers WHERE question_id = ?");
                        if (!$stmt->execute([$question['id']])) {
                            error_log("Error fetching options for question ID: " . $question['id']);
                        }
                        $question['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    echo json_encode([
                        'success' => true,
                        'data' => $survey,
                        'selected_participants' => $selectedParticipantIds,
                        'all_participants' => $allParticipants,
                        'questions' => $questions
                    ]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Error fetching survey: ' . $e->getMessage()]);
                }
                exit;
            } elseif ($action === "search") {
                $query = $_POST['query'] ?? '';

                $stmt = $conn->prepare("SELECT s.*, c.organization_name AS client_name
                            FROM surveys s
                            LEFT JOIN clients c ON s.client_id = c.id
                            WHERE s.title LIKE ? OR s.id LIKE ? OR c.organization_name LIKE ?");
                $stmt->execute(["%$query%", "%$query%", "%$query%"]);

                $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $surveys]);
                exit;
            } elseif ($action === "fetch_sorted") {

                $sortColumn = $_POST['sortColumn'] ?? 'id';
                $sortOrder = $_POST['sortOrder'] ?? 'ASC';

                // Whitelist columns
                $allowed = ["id", "title", "client"];
                if (!in_array($sortColumn, $allowed)) {
                    $sortColumn = 'id';
                }

                $query = $conn->prepare("SELECT s.*, c.organization_name AS client_name
                            FROM surveys s
                            LEFT JOIN clients c ON s.client_id = c.id
                            ORDER BY s.$sortColumn $sortOrder");
                $stmt = $conn->prepare($query);
                $stmt->execute();

                $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $surveys]);
                exit;
            } elseif ($action === 'update') {
                try {
                    $survey_id = $_POST['id'];
                    $title = $_POST['title'];
                    $description = $_POST['description'];
                    $amount = $_POST['amount'];
                    $start_date = $_POST['start_date'];
                    $end_date = $_POST['end_date'];
                    $status = $_POST['status'];
                    $participants = isset($_POST['participants']) ? json_decode($_POST['participants'], true) : [];
                    $questions = isset($_POST['questions']) ? json_decode($_POST['questions'], true) : [];

                    $conn->beginTransaction();

                    // Update survey details
                    $stmt = $conn->prepare("UPDATE surveys SET title = ?, description = ?, amount = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
                    $stmt->execute([$title, $description, $amount, $start_date, $end_date, $status, $survey_id]);

                    // Step 1: Fetch existing participant IDs for this survey
                    $stmt = $conn->prepare("SELECT participant_id FROM survey_participants WHERE survey_id = ?");
                    $stmt->execute([$survey_id]);
                    $existingParticipantIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    // Step 2: Determine participant IDs coming from the form
                    $newParticipantIds = $participants; // already parsed from JSON

                    // Step 3: Find participants to remove and add
                    $participantsToRemove = array_diff($existingParticipantIds, $newParticipantIds);
                    $participantsToAdd = array_diff($newParticipantIds, $existingParticipantIds);

                    // Step 4: Remove unselected participants
                    if (!empty($participantsToRemove)) {
                        $in = implode(',', array_fill(0, count($participantsToRemove), '?'));
                        $stmt = $conn->prepare("DELETE FROM survey_participants WHERE survey_id = ? AND participant_id IN ($in)");
                        $stmt->execute(array_merge([$survey_id], $participantsToRemove));
                    }

                    // Step 5: Add new participants (with calculated payout)
                    if (!empty($participantsToAdd)) {
                        // Fetch only new participants’ details
                        $inQuery = implode(',', array_fill(0, count($participantsToAdd), '?'));
                        $stmtFetchParticipants = $conn->prepare("SELECT id, full_name, email, mobile, specialization FROM participants WHERE id IN ($inQuery)");
                        $stmtFetchParticipants->execute($participantsToAdd);
                        $participantData = $stmtFetchParticipants->fetchAll(PDO::FETCH_ASSOC);

                        //set amount for each participant
                        $participantCount = 0;
                        foreach ($participants as $pt) {
                            $participantCount++;
                        }
                        $individualAmount = $participantCount > 0 ? ($amount / $participantCount) : 0;
                        // $newTotal = count($existingParticipantIds) - count($participantsToRemove) + count($participantsToAdd);
                        // $individualAmount = $newTotal > 0 ? ($amount / $newTotal) : 0;

                        $insertStmt = $conn->prepare("INSERT INTO survey_participants (survey_id, participant_id, full_name, email, mobile, specialization, payment_status, payment_amount, email_sent, invitation_link) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, 0, ?)");

                        foreach ($participantData as $participant) {
                            $invitation_link = "https://yourdomain.com/invite.php?survey=" . $survey_id . "&participant=" . $participant['id'];
                            $insertStmt->execute([
                                $survey_id,
                                $participant['id'],
                                $participant['full_name'],
                                $participant['email'],
                                $participant['mobile'],
                                $participant['specialization'],
                                $individualAmount,
                                $invitation_link
                            ]);
                        }
                    }

                    // // Clear old participants
                    // $stmt = $conn->prepare("DELETE FROM survey_participants WHERE survey_id = ?");
                    // $stmt->execute([$survey_id]);


                    // // Insert participants into survey_participants table
                    // if (!empty($participants)) {
                    //     //set amount for each participant
                    //     $participantCount = 0;
                    //     foreach ($participants as $pt) {
                    //         $participantCount++;
                    //     }
                    //     $individualAmount = $participantCount > 0 ? ($amount / $participantCount) : 0;


                    //     // Fetch participant details in bulk
                    //     $inQuery = implode(',', array_fill(0, count($participants), '?'));
                    //     $stmtFetchParticipants = $conn->prepare("SELECT id, full_name, email, mobile, specialization FROM participants WHERE id IN ($inQuery)");
                    //     $stmtFetchParticipants->execute($participants);
                    //     $participantData = $stmtFetchParticipants->fetchAll(PDO::FETCH_ASSOC);

                    //     $insertStmt = $conn->prepare("INSERT INTO survey_participants (survey_id, participant_id, full_name, email, mobile, specialization, payment_status, payment_amount, email_sent, invitation_link) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, 0, ?)");

                    //     foreach ($participantData as $participant) {
                    //         $invitation_link = "https://yourdomain.com/invite.php?survey=" . $survey_id . "&participant=" . $participant['id'];
                    //         $insertStmt->execute([
                    //             $survey_id,
                    //             $participant['id'],
                    //             $participant['full_name'],
                    //             $participant['email'],
                    //             $participant['mobile'],
                    //             $participant['specialization'],
                    //             $individualAmount,
                    //             $invitation_link
                    //         ]);
                    //     }
                    // }

                    //Updating Questions
                    $pdo = Database::getInstance();

                    // Step 1: Fetch existing question IDs from DB
                    $stmt = $pdo->prepare("SELECT id FROM questions WHERE survey_id = ?");
                    $stmt->execute([$survey_id]);
                    $existingQuestionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    // Step 2: Get incoming question IDs from POST
                    $incomingQuestionIds = array_column(array_filter($questions, function ($q) {
                        return isset($q['id']);
                    }), 'id');

                    // Step 3: Identify deleted questions
                    $deletedQuestionIds = array_diff($existingQuestionIds, $incomingQuestionIds);

                    // Step 4: Delete those questions and their answers
                    if (!empty($deletedQuestionIds)) {
                        $in = implode(',', array_fill(0, count($deletedQuestionIds), '?'));
                        $stmt = $pdo->prepare("DELETE FROM answers WHERE question_id IN ($in)");
                        $stmt->execute($deletedQuestionIds);

                        $stmt = $pdo->prepare("DELETE FROM questions WHERE id IN ($in)");
                        $stmt->execute($deletedQuestionIds);
                    }

                    foreach ($questions as $question) {
                        $question_id = $question['id'] ?? null;
                        $question_text = $question['question_text'] ?? '';
                        $question_type = $question['question_type'] ?? 'single';
                        $required = $question['required'] ?? 0;
                        $options = $question['options'] ?? [];

                        if ($question_text && is_array($options) && count($options) > 1) {
                            if ($question_id) {
                                // Update existing question
                                $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, question_type = ?, required = ? WHERE id = ? AND survey_id = ?");
                                $stmt->execute([$question_text, $question_type, $required, $question_id, $survey_id]);

                                // Fetch existing option IDs for this question
                                $stmt = $pdo->prepare("SELECT id FROM answers WHERE question_id = ?");
                                $stmt->execute([$question_id]);
                                $existingOptionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                                // Collect incoming option IDs
                                $incomingOptionIds = array_column(array_filter($options, function ($opt) {
                                    return isset($opt['id']);
                                }), 'id');

                                // Delete removed options
                                $deletedOptionIds = array_diff($existingOptionIds, $incomingOptionIds);
                                if (!empty($deletedOptionIds)) {
                                    $in = implode(',', array_fill(0, count($deletedOptionIds), '?'));
                                    $stmt = $pdo->prepare("DELETE FROM answers WHERE id IN ($in)");
                                    $stmt->execute($deletedOptionIds);
                                }

                                // Insert or update options
                                foreach ($options as $opt) {
                                    if (isset($opt['id'])) {
                                        // Update existing option
                                        $stmt = $pdo->prepare("UPDATE answers SET answer_text = ? WHERE id = ? AND question_id = ?");
                                        $stmt->execute([$opt['text'], $opt['id'], $question_id]);
                                    } else {
                                        // New option
                                        $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text) VALUES (?, ?)");
                                        $stmt->execute([$question_id, $opt['text']]);
                                    }
                                }
                            } else {
                                // New question
                                $stmt = $pdo->prepare("INSERT INTO questions (survey_id, question_text, question_type, required) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$survey_id, $question_text, $question_type, $required]);
                                $question_id = $pdo->lastInsertId();

                                // Insert options for new question
                                foreach ($options as $opt) {
                                    $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text) VALUES (?, ?)");
                                    $stmt->execute([$question_id, $opt['text']]);
                                }
                            }
                        }
                    }

                    $conn->commit();

                    echo json_encode(['success' => true, 'message' => 'Survey updated successfully!']);
                } catch (Exception $e) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Error updating survey: ' . $e->getMessage()]);
                }
                exit;
            } elseif ($action === 'delete') {
                session_start(); // ensure session is active
                $UserPermissions = $_SESSION['permissions'] ?? [];
                $perm = 'manage_surveys';
                if (!(str_contains($UserPermissions, $perm))) {
                    $response = ["success" => false, "message" => "You do not have permission to delete surveys."];
                    echo json_encode($response);
                    exit;
                }

                $survey_id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM `surveys` WHERE `id` = ?");
                $stmt->execute([$survey_id]);

                if ($stmt->rowCount()) {
                    $response = ["success" => true, "message" => "Client deleted successfully"];
                } else {
                    $response = ["success" => false, "message" => "Client not found or already deleted"];
                }
            }
        }

        // REPORT MANAGEMENT
        elseif ($entity === "report") {
            if ($action === 'fetch_survey_report') {
                $survey_id = $_POST['survey_id'];
                $graph_type = $_POST['graph_type'] ?? 'bar'; // bar, pie, line etc.

                try {
                    // Survey details
                    $stmt = $conn->prepare("SELECT s.*, c.organization_name FROM surveys s LEFT JOIN clients c ON s.client_id = c.id WHERE s.id = ?");
                    $stmt->execute([$survey_id]);
                    $survey = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$survey) {
                        echo json_encode(['success' => false, 'message' => 'Survey not found']);
                        exit;
                    }

                    // Count of completed participants
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM survey_participants WHERE survey_id = ? AND completed = 1");
                    $stmt->execute([$survey_id]);
                    $completed_count = $stmt->fetchColumn();

                    // Amount paid to participants
                    $stmt = $conn->prepare("SELECT SUM(payment_amount) FROM survey_participants WHERE survey_id = ? AND payment_status = 'Done'");
                    $stmt->execute([$survey_id]);
                    $amount_paid = $stmt->fetchColumn() ?? 0;

                    // Remaining
                    $amount_remaining = $survey['amount'] - $amount_paid;


                    // Questions
                    $stmt = $conn->prepare("SELECT id, question_text FROM questions WHERE survey_id = ?");
                    $stmt->execute([$survey_id]);
                    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($questions as &$q) {
                        $stmt = $conn->prepare("SELECT a.id, a.answer_text, COUNT(r.answer_id) as response_count
                                    FROM answers a
                                    LEFT JOIN responses r ON a.id = r.answer_id
                                    WHERE a.question_id = ?
                                    GROUP BY a.id, a.answer_text");
                        $stmt->execute([$q['id']]);
                        $q['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    $stmt = $conn->prepare("SELECT question_text, COUNT(r.answer_id) as total FROM questions q
                            LEFT JOIN responses r ON q.id = r.question_id AND r.survey_id = ?
                            WHERE q.survey_id = ?
                            GROUP BY q.id");
                    $stmt->execute([$survey_id, $survey_id]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $labels = array_column($results, 'question_text');
                    $data = array_column($results, 'total');

                    echo json_encode([
                        'success' => true,
                        'labels' => $labels,
                        'data' => $data,
                        'report' => [
                            'id' => $survey['id'],
                            'title' => $survey['title'],
                            'organization_name' => $survey['organization_name'],
                            'amount' => $survey['amount'],
                            'completed_count' => $completed_count,
                            'amount_paid' => $amount_paid,
                            'amount_remaining' => $amount_remaining,
                            'questions' => $questions
                        ]

                    ]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Error generating report: ' . $e->getMessage()]);
                }
                exit;
            }
            elseif ($action === 'fetch_disbursed_amount'){
                $survey_id = $_POST['survey_id'];
                $start = $_POST['start_date'];
                $end = $_POST['end_date'];
                $stmt = $conn->prepare("
                    SELECT SUM(payment_amount) as amount 
                    FROM survey_participants 
                    WHERE survey_id = ? 
                    AND payment_status = 'Done' 
                    AND DATE(updated_at) BETWEEN ? AND ?
                ");
                $stmt->execute([$survey_id, $start. ' 00:00:00', $end. ' 00:00:00']);
                $amount = $stmt->fetchColumn();

                echo json_encode(['success' => true, 'amount' => $amount]);
                exit;
            }
        }

        // PAYOUT MANAGEMENT
        elseif ($entity === 'payout') {
            if ($action === "fetch_surveys") {
                try {
                    $sql = "SELECT s.*, c.organization_name 
                            FROM surveys s 
                            LEFT JOIN clients c ON s.client_id = c.id";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($surveys)) {
                        echo "<tr><td colspan='6' class='text-center'>No surveys found.</td></tr>";
                    } else {
                        foreach ($surveys as $survey) {
                            echo "<tr data-survey-id='{$survey['id']}'>
                                    <td>{$survey['title']}</td>
                                    <td>{$survey['organization_name']}</td>
                                    <td>₹{$survey['amount']}</td>
                                    <td>{$survey['start_date']}</td>
                                    <td>{$survey['end_date']}</td>
                                    <td><button class='btn btn-sm btn-outline-primary view-btn' data-id='{$survey['id']}'>View</button></td>
                                  </tr>";
                        }
                    }
                } catch (PDOException $e) {
                    echo "<tr><td colspan='6' class='text-danger'>Error: " . $e->getMessage() . "</td></tr>";
                }
                exit;
            } elseif ($action === "fetch_survey_participants") {
                $survey_id = $_POST['survey_id'] ?? null;

                if (!$survey_id) {
                    echo json_encode(['status' => 'error', 'message' => 'Survey ID is required']);
                    exit;
                }

                try {
                    $stmt = $conn->prepare("SELECT *
                                            FROM survey_participants 
                                            WHERE survey_id = ?");
                    $stmt->execute([$survey_id]);
                    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode(['status' => 'success', 'data' => $participants]);
                    exit;
                } catch (PDOException $e) {
                    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]);
                }
                exit;
            } elseif ($action === "mark_paid") {
                $participant_id = $_POST['participant_id'];
                $survey_id = $_POST['survey_id'];

                try {
                    $stmt = $conn->prepare("UPDATE survey_participants SET payment_status = 'Done' WHERE survey_id = ? AND participant_id = ?");
                    $stmt->execute([$survey_id, $participant_id]);

                    echo json_encode(['status' => 'success']);
                    exit;
                } catch (PDOException $e) {
                    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                    exit;
                }
            } elseif ($action === "update_payment") {
                $survey_id = $_POST['survey_id'];
                $participant_id = $_POST['participant_id'];
                $payment_amount = $_POST['payment_amount'];
                $payment_status = $_POST['payment_status'];

                $update = $conn->prepare("UPDATE survey_participants 
                          SET payment_amount = ? 
                          WHERE survey_id = ? AND participant_id = ?");
                $update->execute([$payment_amount, $survey_id, $participant_id]);


                echo "success";
                exit;
            } elseif ($action === "fetch_responses") {
                $survey_id = $_POST['survey_id'];
                $participant_id = $_POST['participant_id'];

                $stmt = $conn->prepare("SELECT q.question_text, GROUP_CONCAT(a.answer_text SEPARATOR ', ') AS answer_text
        FROM responses r
        JOIN questions q ON r.question_id = q.id
        JOIN answers a ON FIND_IN_SET(a.id, r.answer_id)
        WHERE r.survey_id = ? AND r.participant_id = ?
        GROUP BY r.question_id
    ");
                $stmt->execute([$survey_id, $participant_id]);
                $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['status' => 'success', 'data' => $responses]);
                exit;
            }
        }




        //CLIENT MANAGEMENT
        elseif ($entity === "client") {
            $clientManager = new EntityManager('clients');
            if ($action === "read_single" && !empty($id)) {
                $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
                $stmt->execute([$id]);
                $client = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($client) {
                    $response = [
                        "success" => true,
                        "data" => $client,
                        "id" => $client['id'],
                        "organization_name" => $client['organization_name'],
                        "contact_person_name" => $client['contact_person_name'],
                        "contact_person_email" => $client['contact_person_email'],
                        "contact_person_mobile" => $client['contact_person_mobile'],
                        "address_line_1" => $client['address_line_1'],
                        "address_line_2" => $client['address_line_2'],
                        "city" => $client['city'],
                        "state" => $client['state'],
                        "country" => $client['country'],
                        "zip" => $client['zip'],
                        "agreement_file" => $client['agreement_file'],
                        "status" => $client['status']
                    ];
                } else {
                    $response = ["success" => false, "message" => "Client not found"];
                }
            } elseif ($action === "create") { // Add new client
                $organization_name = $_POST['organization_name'] ?? '';
                $contact_person_name = $_POST['contact_person_name'] ?? '';
                $contact_person_email = $_POST['contact_person_email'] ?? '';
                $contact_person_mobile = $_POST['contact_person_mobile'] ?? '';
                $address_line_1 = $_POST['address_line_1'] ?? '';
                $address_line_2 = $_POST['address_line_2'] ?? '';
                $zip = $_POST['zip'] ?? '';
                $city = $_POST['city'] ?? '';
                $state = $_POST['state'] ?? '';
                $country = $_POST['country'] ?? '';
                $status = $_POST['status'] ?? '';

                // Handling file upload
                $agreement_file = null;
                if (isset($_FILES['agreement_file']) && $_FILES['agreement_file']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['agreement_file']['tmp_name'];
                    $fileName = $_FILES['agreement_file']['name'];
                    $fileSize = $_FILES['agreement_file']['size'];
                    $fileType = $_FILES['agreement_file']['type'];

                    // Define the allowed file types and maximum file size
                    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    $maxSize = 10 * 1024 * 1024; // 10MB max

                    // Ensure directory exists
                    $uploadDir = './uploads/agreements/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true); // Create directory with full permissions
                    }

                    // Check if the file type and size are valid
                    if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
                        // Generate a unique name for the file
                        $newFileName = uniqid() . '_' . $fileName;
                        $uploadPath = $uploadDir . $newFileName;

                        // Move the uploaded file to the server's directory
                        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                            $agreement_file = $newFileName;
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Error uploading the file.']);
                            exit;
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Invalid file type or size exceeded.']);
                        exit;
                    }
                }

                if (!empty($organization_name) && !empty($contact_person_name) && !empty($contact_person_email) && !empty($contact_person_mobile)) {

                    // Validate email format
                    if (!filter_var($contact_person_email, FILTER_VALIDATE_EMAIL)) {
                        echo json_encode(["success" => false, "message" => "Invalid email format"]);
                        exit;
                    }

                    // Check if email already exists (Fixed variable name)
                    $checkStmt = $conn->prepare("SELECT `id` FROM `clients` WHERE `contact_person_email` = ? AND `id` != ?");
                    $checkStmt->execute([$contact_person_email, $id]);

                    if ($checkStmt->rowCount() > 0) {
                        echo json_encode(["success" => false, "message" => "Email already exists"]);
                        exit;
                    } else {
                        if ($agreement_file) {
                            $stmt = $conn->prepare("INSERT INTO clients (organization_name, contact_person_name, contact_person_email, contact_person_mobile, address_line_1, address_line_2, zip, city, state, country, status, agreement_file) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$organization_name, $contact_person_name, $contact_person_email, $contact_person_mobile, $address_line_1, $address_line_2, $zip, $city, $state, $country, $status, $agreement_file]);
                        } else {
                            $stmt = $conn->prepare("INSERT INTO clients (organization_name, contact_person_name, contact_person_email, contact_person_mobile, address_line_1, address_line_2, zip, city, state, country, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$organization_name, $contact_person_name, $contact_person_email, $contact_person_mobile, $address_line_1, $address_line_2, $zip, $city, $state, $country, $status]);
                        }
                        if ($stmt->rowCount()) {
                            $response = ["success" => true, "message" => "Client added successfully"];
                        } else {
                            $response = ["success" => false, "message" => "Failed to add client"];
                        }
                    }
                } else {
                    $response = ["success" => false, "message" => "Missing required fields"];
                }

                // echo json_encode($response);
            } elseif ($action === "update" && !empty($id)) {
                $organization_name = $_POST['organization_name'] ?? '';
                $contact_person_name = $_POST['contact_person_name'] ?? '';
                $contact_person_email = $_POST['contact_person_email'] ?? '';
                $contact_person_mobile = $_POST['contact_person_mobile'] ?? '';
                $address_line_1 = $_POST['address_line_1'] ?? '';
                $address_line_2 = $_POST['address_line_2'] ?? '';
                $zip = $_POST['zip'] ?? '';
                $city = $_POST['city'] ?? '';
                $state = $_POST['state'] ?? '';
                $country = $_POST['country'] ?? '';
                $status = $_POST['status'] ?? '';

                // Validate email format
                if (!filter_var($contact_person_email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(["success" => false, "message" => "Invalid email format"]);
                    exit;
                }

                // Check if email already exists (Fixed variable name)
                $checkStmt = $conn->prepare("SELECT `id` FROM `clients` WHERE `contact_person_email` = ? AND `id` != ?");
                $checkStmt->execute([$contact_person_email, $id]);

                if ($checkStmt->rowCount() > 0) {
                    echo json_encode(["success" => false, "message" => "Email already exists"]);
                    exit;
                } else {
                    // Handling file upload
                    $agreement_file = null;
                    if (isset($_FILES['agreement_file']) && $_FILES['agreement_file']['error'] === UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES['agreement_file']['tmp_name'];
                        $fileName = $_FILES['agreement_file']['name'];
                        $fileSize = $_FILES['agreement_file']['size'];
                        $fileType = $_FILES['agreement_file']['type'];

                        // Define the allowed file types and maximum file size
                        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                        $maxSize = 10 * 1024 * 1024; // 10MB max

                        // Check if the file type and size are valid
                        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
                            // Generate a unique name for the file
                            $newFileName = uniqid() . '_' . $fileName;
                            $uploadPath = './uploads/agreements/' . $newFileName;

                            // Move the uploaded file to the server's directory
                            if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                                $agreement_file = $newFileName;
                            } else {
                                echo json_encode(['success' => false, 'message' => 'Error uploading the file.']);
                                exit;
                            }
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Invalid file type or size exceeded.']);
                            exit;
                        }
                    }
                    try {
                        // Update client details and optionally the agreement file
                        if ($agreement_file) {
                            $stmt = $conn->prepare("UPDATE clients SET organization_name = ?, contact_person_name = ?, contact_person_email = ?, contact_person_mobile = ?, address_line_1 = ?, address_line_2 = ?, zip = ?, city = ?, state = ?, country = ?, status = ?, agreement_file = ? WHERE id = ?");
                            $stmt->execute([$organization_name, $contact_person_name, $contact_person_email, $contact_person_mobile, $address_line_1, $address_line_2, $zip, $city, $state, $country, $status, $agreement_file, $id]);
                        } else {
                            $stmt = $conn->prepare("UPDATE clients SET organization_name = ?, contact_person_name = ?, contact_person_email = ?, contact_person_mobile = ?, address_line_1 = ?, address_line_2 = ?, zip = ?, city = ?, state = ?, country = ?, status = ? WHERE id = ?");
                            $stmt->execute([$organization_name, $contact_person_name, $contact_person_email, $contact_person_mobile, $address_line_1, $address_line_2, $zip, $city, $state, $country, $status, $id]);
                        }

                        // Return updated client data
                        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
                        $stmt->execute([$id]);
                        $client = $stmt->fetch(PDO::FETCH_ASSOC);

                        echo json_encode(['success' => true, 'client' => $client]);
                    } catch (Exception $e) {
                        echo json_encode(['success' => false, 'message' => 'Error updating client: ' . $e->getMessage()]);
                    }
                }
                exit;
            } elseif ($action === 'fetch_all') {

                $clients = $clientManager->fetchAll();
                foreach ($clients as $client) {
                    echo "<tr>
                            <td>{$client['id']}</td>
                            <td>{$client['organization_name']}</td>
                            <td>{$client['contact_person_name']}</td>
                            <td>{$client['contact_person_email']}</td>
                            <td>{$client['contact_person_mobile']}</td>
                            <td>{$client['status']}</td>
                            <td>
                                <button class='btn btn-info view-client-btn' data-id='{$client['id']}'>View</button>
                                <button class='btn btn-warning edit-client-btn' data-id='{$client['id']}'>Edit</button>
                                <button class='btn btn-danger delete-client-btn' data-id='{$client['id']}'>Delete</button>
                            </td>
                        </tr>";
                }
            } elseif ($action === "delete" && !empty($id)) {

                $stmt = $conn->prepare("DELETE FROM `clients` WHERE `id` = ?");
                $stmt->execute([$id]);

                if ($stmt->rowCount()) {
                    $response = ["success" => true, "message" => "Client deleted successfully"];
                } else {
                    $response = ["success" => false, "message" => "Client not found or already deleted"];
                }
            } elseif ($action === "search") {
                $query = $_POST['query'] ?? '';

                $stmt = $conn->prepare("SELECT * FROM clients WHERE id LIKE ? OR organization_name LIKE ? OR contact_person_name LIKE ?");
                $searchQuery = "%$query%";
                $stmt->execute([$searchQuery, $searchQuery, $searchQuery]);
                $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($clients) {
                    echo json_encode(["success" => true, "data" => $clients]);
                } else {
                    echo json_encode(["success" => false, "message" => "No clients found"]);
                }
                exit;
            } elseif ($action === "fetch_sorted") {
                $sortColumn = $_POST['sortColumn'] ?? 'id';
                $sortOrder = $_POST['sortOrder'] ?? 'ASC';

                $validColumns = ["id", "organization_name", "contact_person_name"];
                if (!in_array($sortColumn, $validColumns)) {
                    $sortColumn = "id";
                }

                $query = "SELECT * FROM clients ORDER BY $sortColumn $sortOrder";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(["success" => true, "data" => $clients]);
                exit;
            } elseif ($action === "view_client" && !empty($id)) {
                $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
                $stmt->execute([$id]);
                $client = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($client) {
                    $response = [
                        "success" => true,
                        "data" => $client,
                        "modalContent" => "
                        <strong>Organization:</strong> {$client['organization_name']}<br>
                        <strong>Contact Person:</strong> {$client['contact_person_name']}<br>
                        <strong>Email:</strong> {$client['contact_person_email']}<br>
                        <strong>Mobile:</strong> {$client['contact_person_mobile']}<br>
                        <strong>Address:</strong> {$client['address_line_1']}, {$client['address_line_2']}, {$client['city']}, {$client['state']}, {$client['country']}<br>
                        <strong>Status:</strong> {$client['status']}<br>
                        " . (!empty($client['agreement_file']) ? "<strong>Agreement:</strong> <a href='./uploads/agreements/{$client['agreement_file']}' target='_blank'>Download</a>" : "<strong>Agreement:</strong> Not Uploaded") . "
                        "
                    ];
                } else {
                    $response = ["success" => false, "message" => "Client not found"];
                }
            } elseif ($action === "get_client") {
                try {
                    $stmt = $conn->prepare("SELECT id, organization_name FROM clients WHERE status = 'active'");
                    $stmt->execute();
                    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode(['success' => true, 'data' => $clients]);
                    exit; // Prevents extra response
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Error fetching clients: ' . $e->getMessage()]);
                    exit; // Exit on error
                }
            }


        

            //AGREEMENET MANAGEMENT
        } elseif ($entity === 'agreement') {
            if ($action === "view") {
                $stmt = $conn->prepare("SELECT `id`, `survey_id`, `type`, `content`, `status` FROM agreements WHERE `id` = ?");
                $stmt->execute([$id]);
                $agreement = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($agreement) {
                    $stmt = $conn->prepare("SELECT id, title FROM surveys WHERE id = ?");
                    $stmt->execute([$agreement['survey_id']]);
                    $survey = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $response = [
                        "success" => true,
                        "data" => $agreement,
                        "id" => $agreement['id'],
                        "type" => $agreement['type'],
                        "content" => $agreement['content'],
                        "title" => $agreement['survey_id'],
                        "status" => $agreement['status']
                    ];
                    // echo json_encode(["success" => true, "data" => $response]);
                } else {
                    $response = ["success" => false, "error" => "Agreement not found"];
                }
            }   elseif ($action === "remove") {
                $stmt = $conn->prepare("DELETE FROM agreements WHERE id = ?");
                $stmt->execute([$id]);

                if ($stmt->rowCount()) {
                    $response = ["success" => true, "message" => "Agreement deleted successfully"];
                } else {
                    $response = ["success" => false, "message" => "Agreement not found or already deleted"];
                }
            } 
            elseif ($action === "getDocuments"){
                    $survey_id = $_POST['survey_id'];
                    $type = "wl";
                    // $stmt = $conn->prepare("SELECT a.id, s.title AS survey_title, a.type, a.content FROM agreements a
                    //   LEFT JOIN surveys s ON a.survey_id = s.id WHERE a.survey_id = ? AND a.type = ?");
                    $stmt = $conn->prepare("SELECT content FROM agreements WHERE survey_id = ? AND `type` = ?");
                    $stmt->execute([$survey_id, $type]);
                    $results1 = $stmt->fetch(PDO::FETCH_ASSOC);
                    $type = "cl";
                    $stmt = $conn->prepare("SELECT content FROM agreements WHERE survey_id = ? AND `type` = ?");
                    $stmt->execute([$survey_id, $type]);
                    $results2 = $stmt->fetch(PDO::FETCH_ASSOC);
                    $type = "pa";
                    $stmt = $conn->prepare("SELECT content FROM agreements WHERE survey_id = ? AND `type` = ?");
                    $stmt->execute([$survey_id, $type]);
                    $results3 = $stmt->fetch(PDO::FETCH_ASSOC);
                    if($results1){
                        $response = [
                            'success' => true,
                            'data' => $results1,
                            'welcome_letter' => $results1['content'],
                            'consent_letter' => $results2['content'],
                            'participant_agreement' => $results3['content'],
                            'message' => 'Documents fetched successfully'
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'No documents found for this survey.'
                        ];
                    }
                // $documents = [
                //     'participant_agreement' => '',
                //     'welcome_letter' => '',
                //     'consent_letter' => ''
                // ];
                // foreach ($results as $row) {
                //     $documents[$row['type']] = $row['content'];
                // }
                // $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // $documents = $agreementManager->getDocumentsBySurvey($survey_id);
                // echo json_encode(['success' => true, 'message' => 'Documents fetched successfully']);
                // } catch (PDOException $e) {
                //     echo json_encode(['success' => false, 'message' => 'Error fetching documents: ' . $e->getMessage()]);
                //     exit;
                // }
            }
        }
    }
} catch (PDOException $e) {
    $response = ["success" => false, "message" => "Database error: " . $e->getMessage()];
}

echo json_encode($response);
