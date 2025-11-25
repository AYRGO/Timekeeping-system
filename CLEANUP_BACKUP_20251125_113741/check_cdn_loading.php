<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDN Diagnostic Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-card {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 4px;
            font-weight: bold;
            margin-left: 10px;
        }
        .loading { background: #fbbf24; color: #78350f; }
        .success { background: #10b981; color: white; }
        .failed { background: #ef4444; color: white; }
        h1 { color: #1f2937; }
        .info { background: #dbeafe; padding: 15px; border-left: 4px solid #3b82f6; margin: 20px 0; }
        .code { background: #f3f4f6; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔍 CDN Resources Diagnostic Test</h1>
    
    <div class="info">
        <strong>Purpose:</strong> This page tests if external CDN resources can load on this PC. 
        If any tests fail, it indicates network restrictions (firewall, proxy, or antivirus) are blocking external resources.
    </div>

    <div class="test-card">
        <h3>1. Tailwind CSS Test</h3>
        <p>Status: <span id="tailwind-status" class="status loading">Testing...</span></p>
        <div id="tailwind-test" class="hidden bg-blue-500 text-white p-4 rounded" style="display:none;">
            ✓ If you can see this styled blue box, Tailwind CSS loaded successfully!
        </div>
    </div>

    <div class="test-card">
        <h3>2. Font Awesome Icons Test</h3>
        <p>Status: <span id="fontawesome-status" class="status loading">Testing...</span></p>
        <div id="fontawesome-test">
            <i class="fas fa-check-circle" style="font-size: 24px; color: green;"></i>
            <i class="fas fa-times-circle" style="font-size: 24px; color: red;"></i>
            <i class="fas fa-user" style="font-size: 24px; color: blue;"></i>
            <span style="font-size: 12px; color: #666;"> ← You should see 3 icons here</span>
        </div>
    </div>

    <div class="test-card">
        <h3>3. Chart.js Library Test</h3>
        <p>Status: <span id="chartjs-status" class="status loading">Testing...</span></p>
        <canvas id="test-chart" width="400" height="200"></canvas>
    </div>

    <div class="test-card">
        <h3>4. Alpine.js Test</h3>
        <p>Status: <span id="alpine-status" class="status loading">Testing...</span></p>
        <div x-data="{ message: 'Alpine.js is working!' }" id="alpine-test">
            <p x-text="message"></p>
        </div>
    </div>

    <div class="test-card">
        <h3>📊 Test Results Summary</h3>
        <div id="summary"></div>
    </div>

    <div class="test-card" style="background: #fef3c7;">
        <h3>🛠️ Solutions if Tests Fail:</h3>
        <ol>
            <li><strong>Corporate Network:</strong> Contact IT to whitelist these CDN domains:
                <div class="code">
                    - cdn.jsdelivr.net<br>
                    - cdnjs.cloudflare.com<br>
                    - unpkg.com
                </div>
            </li>
            <li><strong>Firewall/Antivirus:</strong> Temporarily disable to test if it's the blocker</li>
            <li><strong>Alternative Solution:</strong> Download libraries locally instead of using CDNs</li>
            <li><strong>Browser Cache:</strong> Clear browser cache (Ctrl+Shift+Del) and try again</li>
        </ol>
    </div>

    <!-- Load CDN Resources -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" id="tailwind-link">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" id="fontawesome-link">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js" id="chartjs-script"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" id="alpine-script"></script>

    <script>
        let results = {
            tailwind: false,
            fontawesome: false,
            chartjs: false,
            alpine: false
        };

        // Test 1: Tailwind CSS
        document.getElementById('tailwind-link').addEventListener('load', function() {
            document.getElementById('tailwind-test').style.display = 'block';
            document.getElementById('tailwind-status').textContent = 'LOADED';
            document.getElementById('tailwind-status').className = 'status success';
            results.tailwind = true;
            updateSummary();
        });
        document.getElementById('tailwind-link').addEventListener('error', function() {
            document.getElementById('tailwind-status').textContent = 'FAILED';
            document.getElementById('tailwind-status').className = 'status failed';
            updateSummary();
        });

        // Test 2: Font Awesome
        document.getElementById('fontawesome-link').addEventListener('load', function() {
            document.getElementById('fontawesome-status').textContent = 'LOADED';
            document.getElementById('fontawesome-status').className = 'status success';
            results.fontawesome = true;
            updateSummary();
        });
        document.getElementById('fontawesome-link').addEventListener('error', function() {
            document.getElementById('fontawesome-status').textContent = 'FAILED';
            document.getElementById('fontawesome-status').className = 'status failed';
            updateSummary();
        });

        // Test 3: Chart.js
        document.getElementById('chartjs-script').addEventListener('load', function() {
            if (typeof Chart !== 'undefined') {
                document.getElementById('chartjs-status').textContent = 'LOADED';
                document.getElementById('chartjs-status').className = 'status success';
                results.chartjs = true;
                
                // Draw a simple test chart
                const ctx = document.getElementById('test-chart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Test 1', 'Test 2', 'Test 3'],
                        datasets: [{
                            label: 'Sample Data',
                            data: [12, 19, 8],
                            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
            updateSummary();
        });
        document.getElementById('chartjs-script').addEventListener('error', function() {
            document.getElementById('chartjs-status').textContent = 'FAILED';
            document.getElementById('chartjs-status').className = 'status failed';
            updateSummary();
        });

        // Test 4: Alpine.js
        setTimeout(function() {
            if (typeof Alpine !== 'undefined') {
                document.getElementById('alpine-status').textContent = 'LOADED';
                document.getElementById('alpine-status').className = 'status success';
                results.alpine = true;
            } else {
                document.getElementById('alpine-status').textContent = 'FAILED';
                document.getElementById('alpine-status').className = 'status failed';
                document.getElementById('alpine-test').innerHTML = '<p style="color: #ef4444;">Alpine.js failed to load</p>';
            }
            updateSummary();
        }, 2000);

        function updateSummary() {
            const passed = Object.values(results).filter(r => r).length;
            const total = Object.keys(results).length;
            const percentage = Math.round((passed / total) * 100);
            
            let summaryHTML = `<p><strong>Tests Passed:</strong> ${passed} / ${total} (${percentage}%)</p>`;
            
            if (passed === total) {
                summaryHTML += '<p style="color: #10b981; font-weight: bold;">✅ All CDN resources loaded successfully! The admin homepage should work properly.</p>';
            } else if (passed === 0) {
                summaryHTML += '<p style="color: #ef4444; font-weight: bold;">❌ No CDN resources loaded. This PC is blocking external resources.</p>';
                summaryHTML += '<p><strong>Action Required:</strong> Contact your IT department or download libraries locally.</p>';
            } else {
                summaryHTML += '<p style="color: #f59e0b; font-weight: bold;">⚠️ Some CDN resources failed to load. The page may look incomplete.</p>';
            }
            
            document.getElementById('summary').innerHTML = summaryHTML;
        }

        // Initial timeout check
        setTimeout(function() {
            if (document.getElementById('tailwind-status').textContent === 'Testing...') {
                document.getElementById('tailwind-status').textContent = 'FAILED/TIMEOUT';
                document.getElementById('tailwind-status').className = 'status failed';
            }
            if (document.getElementById('fontawesome-status').textContent === 'Testing...') {
                document.getElementById('fontawesome-status').textContent = 'FAILED/TIMEOUT';
                document.getElementById('fontawesome-status').className = 'status failed';
            }
            if (document.getElementById('chartjs-status').textContent === 'Testing...') {
                document.getElementById('chartjs-status').textContent = 'FAILED/TIMEOUT';
                document.getElementById('chartjs-status').className = 'status failed';
            }
            updateSummary();
        }, 5000);
    </script>
</body>
</html>
