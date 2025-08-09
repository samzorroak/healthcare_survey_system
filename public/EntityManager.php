<?php
require_once '../config.php'; // Include DB connection

class EntityManager {
    private $conn;
    private $table;

    public function __construct($table) {
        $this->conn = Database::getInstance(); // Use singleton database connection
        $this->table = $table;
    }

    // Fetch all records
    public function fetchAll() {
        try {
            $stmt = $this->conn->query("SELECT * FROM `$this->table` ORDER BY `id` DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            die("Error fetching records: " . $e->getMessage());
        }
    }

    // Fetch active records
    public function fetchActive() {
        try {
            $stmt = $this->conn->query("SELECT * FROM `$this->table` WHERE status = 'active' ORDER BY `id`");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            die("Error fetching records: " . $e->getMessage());
        }
    }

    // Fetch a single record by ID
    public function fetchById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM `$this->table` WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            die("Error fetching record: " . $e->getMessage());
        }
    }

    // Insert a new record
    public function insert($data) {
        try {
            $columns = implode(", ", array_keys($data));
            $values = ":" . implode(", :", array_keys($data));
            $sql = "INSERT INTO `$this->table` ($columns) VALUES ($values)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($data);
        } catch (Exception $e) {
            die("Error inserting record: " . $e->getMessage());
        }
    }

    // Update an existing record
    public function update($id, $data) {
        try {
            $setClause = implode(", ", array_map(fn($col) => "$col = :$col", array_keys($data)));
            $sql = "UPDATE `$this->table` SET $setClause WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $data['id'] = $id;
            return $stmt->execute($data);
        } catch (Exception $e) {
            die("Error updating record: " . $e->getMessage());
        }
    }

    // Delete a record
    public function delete($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM `$this->table` WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            die("Error deleting record: " . $e->getMessage());
        }
    }

    // ======================= New Features =======================

    // Fetch participants for survey allocation based on state, city, and specialization
    public function fetchEligibleParticipants($state, $city, $specialization) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM participants
                WHERE state = :state AND city = :city AND specialization = :specialization
            ");
            $stmt->execute([
                'state' => $state,
                'city' => $city,
                'specialization' => $specialization
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            die("Error fetching participants: " . $e->getMessage());
        }
    }

    // Allocate participants to a survey and send invitations
    public function allocateParticipants($surveyId, $participantIds) {
        try {
            foreach ($participantIds as $participantId) {
                $stmt = $this->conn->prepare("
                    INSERT INTO invitations (survey_id, participant_id, invitation_date, status)
                    VALUES (:survey_id, :participant_id, NOW(), 'pending')
                ");
                $stmt->execute([
                    'survey_id' => $surveyId,
                    'participant_id' => $participantId
                ]);

                // Fetch participant email for invitation
                $participant = $this->fetchById($participantId);
                $this->sendInvitationEmail($participant['email'], $surveyId);
            }
            return true;
        } catch (Exception $e) {
            die("Error allocating participants: " . $e->getMessage());
        }
    }

    // Send invitation email to participants
    private function sendInvitationEmail($email, $surveyId) {
        $surveyLink = "http://example.com/survey_portal.php?survey_id=$surveyId";
        $subject = "Survey Invitation";
        $message = "You have been invited to participate in a survey. Click the link below to start:\n\n$surveyLink";
        
        // Using PHP mail() function for simplicity
        mail($email, $subject, $message);
    }

    // Mark survey completion for a participant
    public function markSurveyCompleted($participantId, $surveyId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE survey_responses
                SET status = 'completed'
                WHERE participant_id = :participant_id AND survey_id = :survey_id
            ");
            $stmt->execute([
                'participant_id' => $participantId,
                'survey_id' => $surveyId
            ]);
            return true;
        } catch (Exception $e) {
            die("Error marking survey completion: " . $e->getMessage());
        }
    }
}
?>
