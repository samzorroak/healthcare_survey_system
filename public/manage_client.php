<?php
include '../config.php'; // Ensure config.php is properly included

header('Content-Type: application/json');

// Get the database connection instance
$conn = Database::getInstance();

$response = ["success" => false, "message" => "Invalid request"];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $entity = $_POST['entity'] ?? '';
    $id = $_POST['id'] ?? '';

    if ($action == 'addClient') {
        $stmt = $conn->prepare("INSERT INTO clients (organization_name, contact_person_name, contact_person_email, contact_person_mobile, address_line_1, address_line_2, zip, city, state, country, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['organization_name'], $_POST['contact_person_name'], $_POST['contact_person_email'], $_POST['contact_person_mobile'], $_POST['address_line_1'], $_POST['address_line_2'], $_POST['zip'], $_POST['city'], $_POST['state'], $_POST['country'], $_POST['status']]);
        echo "Client added successfully!";
    } 

    elseif ($action == 'getClient') {
        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    }

    elseif ($action == 'updateClient') {
        $stmt = $conn->prepare("UPDATE clients SET organization_name=?, contact_person_name=?, contact_person_email=?, contact_person_mobile=?, address_line_1=?, address_line_2=?, zip=?, city=?, state=?, country=?, status=? WHERE id=?");
        $stmt->execute([$_POST['organization_name'], $_POST['contact_person_name'], $_POST['contact_person_email'], $_POST['contact_person_mobile'], $_POST['address_line_1'], $_POST['address_line_2'], $_POST['zip'], $_POST['city'], $_POST['state'], $_POST['country'], $_POST['status'], $_POST['id']]);
        echo "Client updated successfully!";
    }

    elseif ($action == 'deleteClient') {
        $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo "Client deleted successfully!";
    }

    elseif ($action == 'viewClient') {
        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$_POST['id']]);
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
            $response = ["success" => false, "error" => "Client not found"];
        }
    }
}
?>
