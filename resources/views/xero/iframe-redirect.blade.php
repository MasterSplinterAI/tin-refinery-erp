<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xero Authorization</title>
    <style>
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 2s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 4px;
        }
        .error {
            background-color: #ffdddd;
            border-left: 6px solid #f44336;
            text-align: left;
            padding: 15px;
            margin: 15px 0;
        }
        .success {
            background-color: #ddffdd;
            border-left: 6px solid #4CAF50;
        }
        .info {
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
        }
        .manual-action {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            display: none;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        #debugSection {
            margin-top: 30px;
            padding: 15px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: left;
            display: none;
        }
        #debugToggle {
            background-color: #666;
            margin-top: 20px;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            background-color: #f7f7f7;
            padding: 10px;
            border-radius: 4px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <h1>Xero Authorization</h1>
    
    <div id="status" class="message info">
        <p>Xero authorization received. Processing...</p>
        <div class="loader"></div>
    </div>
    
    <div id="error" class="error" style="display: none;"></div>
    
    <div id="success" class="message success" style="display: none;">
        <p>Authorization successful! You can close this window and return to the application.</p>
    </div>
    
    <div class="manual-action" id="manualAction">
        <h3>Manual Action Required</h3>
        <p>We're having trouble automatically processing your Xero authorization. Please click the button below to complete the process:</p>
        
        <form id="authForm" method="POST" action="{{ url('api/xero/process-auth') }}">
            <input type="hidden" name="code" value="{{ request()->get('code') }}">
            <input type="hidden" name="state" value="{{ request()->get('state') }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <button type="submit" id="processAuthBtn">Process Authorization</button>
        </form>
        
        <div style="margin-top: 20px;">
            <a href="/dashboard" class="btn" style="display: inline-block; padding: 10px 15px; background-color: #666; color: white; text-decoration: none; border-radius: 4px;">Return to Dashboard</a>
        </div>
    </div>
    
    <div id="direct-access" class="message info" style="display: none;">
        <h3>Direct Access Detected</h3>
        <p>You've accessed this page directly without going through the Xero authorization process.</p>
        <p>This page is meant to be accessed as part of the OAuth flow after authorization with Xero.</p>
        <p>Please use one of the following options:</p>
        <div style="margin-top: 20px;">
            <a href="{{ url('/xero-test') }}" class="btn" style="display: inline-block; padding: 10px 15px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;">Go to Test Page</a>
            <a href="{{ url('/dashboard') }}" class="btn" style="display: inline-block; padding: 10px 15px; background-color: #666; color: white; text-decoration: none; border-radius: 4px;">Return to Dashboard</a>
        </div>
    </div>
    
    <button id="debugToggle" onclick="toggleDebug()">Show Debug Info</button>
    
    <div id="debugSection">
        <h3>Debug Information</h3>
        <p>This information might help troubleshoot any issues:</p>
        
        <h4>Authorization Details:</h4>
        <ul>
            <li>Code present: {{ request()->has('code') ? 'Yes' : 'No' }}</li>
            <li>State present: {{ request()->has('state') ? 'Yes' : 'No' }}</li>
            <li>CSRF Token: {{ csrf_token() ? 'Generated' : 'Missing' }}</li>
        </ul>
        
        <div id="debugOutput"></div>
    </div>

    <script>
        // Function to collect debug information
        function collectDebugInfo() {
            return {
                url: window.location.href,
                code: "{{ request()->get('code') ? 'present (length: ' . strlen(request()->get('code')) . ')' : 'missing' }}",
                state: "{{ request()->get('state') ? 'present (length: ' . strlen(request()->get('state')) . ')' : 'missing' }}",
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString(),
                referrer: document.referrer || 'none',
                screenSize: `${window.innerWidth}x${window.innerHeight}`
            };
        }
        
        // Function to toggle debug information
        function toggleDebug() {
            const debugSection = document.getElementById('debugSection');
            const debugToggle = document.getElementById('debugToggle');
            
            if (debugSection.style.display === 'none' || !debugSection.style.display) {
                debugSection.style.display = 'block';
                debugToggle.textContent = 'Hide Debug Info';
                
                // Update debug output
                const debugInfo = collectDebugInfo();
                document.getElementById('debugOutput').innerHTML = `<pre>${JSON.stringify(debugInfo, null, 2)}</pre>`;
            } else {
                debugSection.style.display = 'none';
                debugToggle.textContent = 'Show Debug Info';
            }
        }
        
        // Handle manual form submission
        document.getElementById('authForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const code = "{{ request()->get('code') }}";
            const state = "{{ request()->get('state') }}";
            const token = document.querySelector('input[name="_token"]').value;
            
            // Show processing state
            document.getElementById('processAuthBtn').disabled = true;
            document.getElementById('processAuthBtn').textContent = 'Processing...';
            
            // Use fetch with application/x-www-form-urlencoded format
            fetch('{{ url('api/xero/process-auth') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: `code=${encodeURIComponent(code)}&state=${encodeURIComponent(state)}&_token=${encodeURIComponent(token)}`
            })
            .then(response => {
                // Log the response
                console.log('Manual form response status:', response.status);
                
                const debugInfo = collectDebugInfo();
                debugInfo.responseStatus = response.status;
                document.getElementById('debugOutput').innerHTML = `<pre>${JSON.stringify(debugInfo, null, 2)}</pre>`;
                
                return response.json().catch(e => {
                    console.error('Error parsing JSON response:', e);
                    return { error: `Failed to parse server response (${response.status})` };
                });
            })
            .then(data => {
                console.log('Manual form response data:', data);
                
                if (data.success) {
                    document.getElementById('error').style.display = 'none';
                    document.getElementById('status').style.display = 'none';
                    document.getElementById('manualAction').style.display = 'none';
                    document.getElementById('success').style.display = 'block';
                    
                    if (data.test_mode) {
                        document.getElementById('success').innerHTML = `
                            <h3>Test Authorization Successful</h3>
                            <p>${data.message || 'Test Xero connection successful'}</p>
                            <p>This was a test authorization. In a real scenario, your Xero account would now be connected.</p>
                            <a href="/dashboard" class="btn" style="display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">Return to Dashboard</a>
                        `;
                    } else {
                        document.getElementById('success').innerHTML = `
                            <h3>Authorization Successful</h3>
                            <p>${data.message || 'Xero connection established'}</p>
                            <p>You can now close this window and return to the application.</p>
                            <a href="/dashboard" class="btn" style="display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">Return to Dashboard</a>
                        `;
                    }
                } else {
                    document.getElementById('error').style.display = 'block';
                    document.getElementById('error').innerHTML = `
                        <h3>Error Processing Authorization</h3>
                        <p>There was a problem processing your Xero authorization manually:</p>
                        <p>${data.error || 'Unknown error occurred'}</p>
                    `;
                    document.getElementById('processAuthBtn').disabled = false;
                    document.getElementById('processAuthBtn').textContent = 'Try Again';
                }
            })
            .catch(error => {
                console.error('Error with manual form submission:', error);
                document.getElementById('error').style.display = 'block';
                document.getElementById('error').innerHTML = `
                    <h3>Error Processing Authorization</h3>
                    <p>There was a problem processing your request:</p>
                    <p>${error.message}</p>
                `;
                document.getElementById('processAuthBtn').disabled = false;
                document.getElementById('processAuthBtn').textContent = 'Try Again';
            });
        });
        
        // Process the authorization immediately
        document.addEventListener('DOMContentLoaded', function() {
            const code = "{{ request()->get('code') }}";
            const state = "{{ request()->get('state') }}";
            
            if (!code) {
                document.getElementById('status').style.display = 'none';
                document.getElementById('error').style.display = 'none';
                document.getElementById('direct-access').style.display = 'block';
                document.getElementById('manualAction').style.display = 'none';
                
                // Update debug info
                toggleDebug();
                return;
            }
            
            // CSRF token for fetch request
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || document.querySelector('input[name="_token"]')?.value;
            
            // Process the authorization
            fetch('{{ url('api/xero/process-auth') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    code: code,
                    state: state
                })
            })
            .then(response => {
                // Log the response status for debugging
                console.log('Response status:', response.status);
                
                // Update debug info
                const debugInfo = collectDebugInfo();
                debugInfo.responseStatus = response.status;
                document.getElementById('debugOutput').innerHTML = `<pre>${JSON.stringify(debugInfo, null, 2)}</pre>`;
                
                if (!response.ok) {
                    return response.json().catch(e => {
                        throw new Error(`Server responded with status: ${response.status}`);
                    }).then(errorData => {
                        throw new Error(errorData.error || `Server responded with status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Success:', data);
                document.getElementById('status').style.display = 'none';
                document.getElementById('success').style.display = 'block';
                
                // Store the authorization info in localStorage for the parent window to access
                try {
                    localStorage.setItem('xero_auth_code', code);
                    localStorage.setItem('xero_auth_state', state);
                    localStorage.setItem('xero_auth_time', new Date().toISOString());
                } catch (e) {
                    console.error('Error storing auth data in localStorage:', e);
                }
                
                // Update success message if this was a test
                if (data.test_mode) {
                    document.getElementById('success').innerHTML = `
                        <h3>Test Authorization Successful</h3>
                        <p>${data.message || 'Test connection successful'}</p>
                        <p>This was a test authorization. In a real scenario, your Xero account would now be connected.</p>
                        <p>You can close this window and return to the application.</p>
                        <div style="margin-top: 20px;">
                            <a href="/dashboard" class="btn" style="display: inline-block; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">Return to Dashboard</a>
                            <button onclick="window.close()" class="btn" style="display: inline-block; margin-left: 10px; padding: 10px 15px; background-color: #666; color: white; text-decoration: none; border-radius: 4px;">Close Window</button>
                        </div>
                    `;
                } else {
                    document.getElementById('success').innerHTML = `
                        <h3>Authorization Successful!</h3>
                        <p>${data.message || 'Your Xero account has been connected successfully.'}</p>
                        <p>You can close this window and return to the application. Your dashboard will be updated automatically.</p>
                        <div style="margin-top: 20px;">
                            <a href="/dashboard?xero_auth=true" class="btn" style="display: inline-block; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">Return to Dashboard</a>
                            <button onclick="window.close()" class="btn" style="display: inline-block; margin-left: 10px; padding: 10px 15px; background-color: #666; color: white; text-decoration: none; border-radius: 4px;">Close Window</button>
                        </div>
                    `;
                }
                
                // Attempt to notify the parent window
                try {
                    if (window.opener) {
                        window.opener.postMessage({ 
                            type: 'xero-auth-success',
                            test_mode: data.test_mode || false,
                            code: code,
                            state: state
                        }, '*');
                        
                        // Attempt to reload the parent window's dashboard with the xero_auth flag
                        try {
                            window.opener.location.href = '/dashboard?xero_auth=true';
                        } catch (e) {
                            console.error('Error redirecting parent window:', e);
                        }
                    }
                } catch (e) {
                    console.error('Error posting message to parent:', e);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('status').style.display = 'none';
                document.getElementById('error').style.display = 'block';
                
                // Check if this was a test code error
                const isTestCodeError = 
                    window.location.href.includes('code=test_code') && 
                    !error.message.includes('test');
                
                if (isTestCodeError) {
                    document.getElementById('error').innerHTML = `
                        <h3>Test Authorization Error</h3>
                        <p>There was a problem processing your test authorization:</p>
                        <p>${error.message}</p>
                        <p>This is expected for test authorizations if the server is not configured to handle test codes.</p>
                        <p>For a real Xero authorization, please use the official connect button in the application.</p>
                    `;
                } else {
                    document.getElementById('error').innerHTML = `
                        <h3>Error Processing Authorization</h3>
                        <p>There was a problem processing your Xero authorization:</p>
                        <p>${error.message}</p>
                        <p>You can try the manual authorization option below.</p>
                    `;
                }
                
                // Show manual action after a failure
                document.getElementById('manualAction').style.display = 'block';
            });
            
            // Show manual action after a timeout as a fallback
            setTimeout(function() {
                if (document.getElementById('status').style.display !== 'none') {
                    document.getElementById('manualAction').style.display = 'block';
                }
            }, 5000);
        });
    </script>
</body>
</html> 