<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Logs - QickMed</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: #2d2d30;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007acc;
        }
        
        .header h1 {
            margin: 0;
            color: #007acc;
        }
        
        .log-controls {
            background: #252526;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .log-controls select,
        .log-controls button {
            padding: 8px 12px;
            background: #3c3c3c;
            border: 1px solid #5a5a5a;
            color: #d4d4d4;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .log-controls button:hover {
            background: #007acc;
        }
        
        .log-viewer {
            background: #1e1e1e;
            border: 1px solid #3c3c3c;
            border-radius: 8px;
            height: 500px;
            overflow-y: auto;
            padding: 15px;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .log-line {
            margin-bottom: 5px;
            padding: 2px 0;
        }
        
        .log-line.ERROR {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }
        
        .log-line.CART {
            background: rgba(76, 175, 80, 0.1);
            color: #4caf50;
        }
        
        .log-line.API {
            background: rgba(33, 150, 243, 0.1);
            color: #2196f3;
        }
        
        .log-line.DB {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .timestamp {
            color: #858585;
        }
        
        .log-type {
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            margin: 0 5px;
        }
        
        .log-type.ERROR {
            background: #f44336;
            color: white;
        }
        
        .log-type.CART {
            background: #4caf50;
            color: white;
        }
        
        .log-type.API {
            background: #2196f3;
            color: white;
        }
        
        .log-type.DB {
            background: #ffc107;
            color: black;
        }
        
        .stats {
            background: #252526;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: #3c3c3c;
            border-radius: 4px;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007acc;
        }
        
        .stat-label {
            color: #858585;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Log Viewer</h1>
            <p>Real-time cart action logs</p>
        </div>
        
        <div class="stats" id="stats">
            <!-- Stats will be loaded here -->
        </div>
        
        <div class="log-controls">
            <label>Log File:</label>
            <select id="logSelect">
                <?php
                $logDir = __DIR__;
                $logFiles = glob($logDir . '/*.log');
                rsort($logFiles); // Newest first
                
                foreach ($logFiles as $file) {
                    $filename = basename($file);
                    echo "<option value='{$filename}'>{$filename}</option>";
                }
                ?>
            </select>
            
            <button onclick="refreshLog()">🔄 Refresh</button>
            <button onclick="clearCurrentLog()">🗑️ Clear</button>
            <button onclick="toggleAutoRefresh()" id="autoRefreshBtn">▶️ Auto Refresh</button>
            
            <label style="margin-left: 20px;">Filter:</label>
            <select id="filterType">
                <option value="">All</option>
                <option value="CART">Cart Actions</option>
                <option value="API">API Calls</option>
                <option value="ERROR">Errors</option>
                <option value="DB">Database</option>
            </select>
        </div>
        
        <div class="log-viewer" id="logViewer">
            <div style="color: #858585; text-align: center;">Select a log file to view</div>
        </div>
    </div>

    <script>
        let autoRefreshInterval = null;
        let isAutoRefresh = false;
        
        function refreshLog() {
            const selectedFile = document.getElementById('logSelect').value;
            const filterType = document.getElementById('filterType').value;
            
            if (!selectedFile) return;
            
            fetch(`read_log.php?file=${selectedFile}&filter=${filterType}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('logViewer').innerHTML = data;
                    // Auto scroll to bottom
                    const viewer = document.getElementById('logViewer');
                    viewer.scrollTop = viewer.scrollHeight;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('logViewer').innerHTML = '<div style="color: #f44336;">Error loading log file</div>';
                });
                
            // Update stats
            updateStats();
        }
        
        function updateStats() {
            fetch('log_stats.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('stats').innerHTML = `
                        <div class="stat-item">
                            <div class="stat-number">${data.total_lines || 0}</div>
                            <div class="stat-label">Total Lines</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">${data.cart_actions || 0}</div>
                            <div class="stat-label">Cart Actions</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">${data.api_calls || 0}</div>
                            <div class="stat-label">API Calls</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">${data.errors || 0}</div>
                            <div class="stat-label">Errors</div>
                        </div>
                    `;
                })
                .catch(error => console.error('Stats error:', error));
        }
        
        function clearCurrentLog() {
            const selectedFile = document.getElementById('logSelect').value;
            if (!selectedFile) return;
            
            if (confirm(`Clear log file: ${selectedFile}?`)) {
                fetch(`clear_log.php?file=${selectedFile}`, { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            refreshLog();
                        } else {
                            alert('Failed to clear log');
                        }
                    });
            }
        }
        
        function toggleAutoRefresh() {
            if (isAutoRefresh) {
                clearInterval(autoRefreshInterval);
                isAutoRefresh = false;
                document.getElementById('autoRefreshBtn').innerHTML = '▶️ Auto Refresh';
            } else {
                autoRefreshInterval = setInterval(refreshLog, 3000);
                isAutoRefresh = true;
                document.getElementById('autoRefreshBtn').innerHTML = '⏸️ Stop Auto';
            }
        }
        
        // Event listeners
        document.getElementById('logSelect').addEventListener('change', refreshLog);
        document.getElementById('filterType').addEventListener('change', refreshLog);
        
        // Initial load
        if (document.getElementById('logSelect').value) {
            refreshLog();
        }
        
        updateStats();
    </script>
</body>
</html> 