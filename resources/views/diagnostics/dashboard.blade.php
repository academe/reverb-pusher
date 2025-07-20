<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Diagnostics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">WebSocket Diagnostics Dashboard</h1>
            
            <!-- Server Status -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Server Status</h2>
                <div x-data="serverStatus()" class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <button @click="checkStatus()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Check Reverb Status
                        </button>
                        <div x-show="loading" class="text-gray-600">Checking...</div>
                    </div>
                    
                    <div x-show="status" class="bg-gray-50 p-4 rounded">
                        <div class="flex items-center mb-2">
                            <span class="font-semibold">Reverb Server:</span>
                            <span x-text="status?.reverb_running ? 'RUNNING' : 'NOT RUNNING'" 
                                  :class="status?.reverb_running ? 'text-green-600 ml-2' : 'text-red-600 ml-2'"></span>
                        </div>
                        <pre x-text="status?.process_info" class="text-sm text-gray-600"></pre>
                    </div>
                </div>
            </div>

            <!-- Apps Overview -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">WebSocket Apps</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-blue-50 p-4 rounded">
                        <div class="text-2xl font-bold text-blue-600">{{ $totalApps }}</div>
                        <div class="text-blue-800">Total Apps</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded">
                        <div class="text-2xl font-bold text-green-600">{{ $activeApps }}</div>
                        <div class="text-green-800">Active Apps</div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded">
                        <div class="text-2xl font-bold text-yellow-600">{{ $totalApps - $activeApps }}</div>
                        <div class="text-yellow-800">Inactive Apps</div>
                    </div>
                </div>
            </div>

            <!-- Connection Testing -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Connection Testing</h2>
                <div x-data="connectionTester()" class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <select x-model="selectedApp" class="border rounded px-3 py-2">
                            <option value="">Select an app to test</option>
                            @foreach($apps as $app)
                                <option value="{{ $app->app_key }}">{{ $app->name }} ({{ $app->app_key }})</option>
                            @endforeach
                        </select>
                        <button @click="testConnection()" :disabled="!selectedApp" 
                                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 disabled:opacity-50">
                            Test Connection
                        </button>
                    </div>
                    
                    <div x-show="testing" class="text-gray-600">Testing connection...</div>
                    
                    <div x-show="testResults" class="bg-gray-50 p-4 rounded">
                        <h3 class="font-semibold mb-2">Test Results:</h3>
                        <div x-show="testResults?.app_found">
                            <div class="mb-2">
                                <span class="font-medium">App:</span> <span x-text="testResults?.app_name"></span>
                                <span :class="testResults?.app_active ? 'text-green-600 ml-2' : 'text-red-600 ml-2'"
                                      x-text="testResults?.app_active ? '(Active)' : '(Inactive)'"></span>
                            </div>
                            <div class="mb-2">
                                <span class="font-medium">WebSocket URL:</span> <span x-text="testResults?.websocket_url" class="font-mono text-sm"></span>
                            </div>
                            
                            <div class="space-y-2">
                                <template x-for="(test, name) in testResults?.tests" :key="name">
                                    <div class="flex items-center">
                                        <span x-text="name.replace('_', ' ').toUpperCase()" class="font-medium w-32"></span>
                                        <span :class="test.status === 'pass' ? 'text-green-600' : 'text-red-600'" 
                                              x-text="test.status.toUpperCase()"></span>
                                        <span x-show="test.error" x-text="test.error" class="text-red-600 ml-2 text-sm"></span>
                                        <span x-show="test.http_code" x-text="'HTTP ' + test.http_code" class="ml-2 text-sm"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Broadcast Testing -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Broadcast Testing</h2>
                <div x-data="broadcastTester()" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <select x-model="selectedApp" class="border rounded px-3 py-2">
                            <option value="">Select an app</option>
                            @foreach($apps as $app)
                                <option value="{{ $app->app_key }}">{{ $app->name }}</option>
                            @endforeach
                        </select>
                        <input x-model="channel" placeholder="Channel name (e.g., test-channel)" 
                               class="border rounded px-3 py-2">
                        <input x-model="message" placeholder="Test message" 
                               class="border rounded px-3 py-2">
                    </div>
                    
                    <button @click="sendBroadcast()" :disabled="!selectedApp || !channel" 
                            class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 disabled:opacity-50">
                        Send Test Broadcast
                    </button>
                    
                    <div x-show="broadcasting" class="text-gray-600">Sending broadcast...</div>
                    
                    <div x-show="broadcastResult" class="bg-gray-50 p-4 rounded">
                        <div :class="broadcastResult?.status === 'success' ? 'text-green-600' : 'text-red-600'" 
                             x-text="broadcastResult?.message"></div>
                        <div x-show="broadcastResult?.error" x-text="broadcastResult?.error" class="text-red-600 text-sm mt-1"></div>
                        <div x-show="broadcastResult?.details" class="text-sm text-gray-600 mt-2">
                            <pre x-text="JSON.stringify(broadcastResult?.details, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real-time Event Log -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Real-time Event Log</h2>
                <div x-data="eventLogger()" class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <button @click="toggleLogging()" 
                                :class="logging ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-500 hover:bg-blue-600'" 
                                class="text-white px-4 py-2 rounded">
                            <span x-text="logging ? 'Stop Logging' : 'Start Logging'"></span>
                        </button>
                        <button @click="clearLog()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            Clear Log
                        </button>
                    </div>
                    
                    <div class="bg-black text-green-400 p-4 rounded h-64 overflow-y-auto font-mono text-sm">
                        <template x-for="event in events" :key="event.id">
                            <div>
                                <span class="text-gray-400" x-text="event.timestamp"></span>
                                <span x-text="event.message"></span>
                            </div>
                        </template>
                        <div x-show="events.length === 0" class="text-gray-500">
                            No events logged yet. Start logging to see real-time WebSocket activity.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function serverStatus() {
            return {
                status: null,
                loading: false,
                
                async checkStatus() {
                    this.loading = true;
                    try {
                        const response = await fetch('/diagnostics/reverb-status');
                        this.status = await response.json();
                    } catch (error) {
                        console.error('Error checking status:', error);
                    }
                    this.loading = false;
                }
            }
        }

        function connectionTester() {
            return {
                selectedApp: '',
                testResults: null,
                testing: false,
                
                async testConnection() {
                    if (!this.selectedApp) return;
                    
                    this.testing = true;
                    this.testResults = null;
                    
                    try {
                        const response = await fetch('/diagnostics/test-connection', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify({
                                app_key: this.selectedApp
                            })
                        });
                        
                        this.testResults = await response.json();
                    } catch (error) {
                        console.error('Error testing connection:', error);
                        this.testResults = { error: 'Test failed: ' + error.message };
                    }
                    
                    this.testing = false;
                }
            }
        }

        function broadcastTester() {
            return {
                selectedApp: '',
                channel: 'test-channel',
                message: 'Hello from diagnostics!',
                broadcastResult: null,
                broadcasting: false,
                
                async sendBroadcast() {
                    if (!this.selectedApp || !this.channel) return;
                    
                    this.broadcasting = true;
                    this.broadcastResult = null;
                    
                    try {
                        const response = await fetch('/diagnostics/send-test-broadcast', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify({
                                app_key: this.selectedApp,
                                channel: this.channel,
                                message: this.message
                            })
                        });
                        
                        this.broadcastResult = await response.json();
                    } catch (error) {
                        console.error('Error sending broadcast:', error);
                        this.broadcastResult = { 
                            status: 'error',
                            message: 'Broadcast failed',
                            error: error.message 
                        };
                    }
                    
                    this.broadcasting = false;
                }
            }
        }

        function eventLogger() {
            return {
                events: [],
                logging: false,
                eventId: 0,
                
                toggleLogging() {
                    this.logging = !this.logging;
                    if (this.logging) {
                        this.addEvent('Logging started');
                    } else {
                        this.addEvent('Logging stopped');
                    }
                },
                
                clearLog() {
                    this.events = [];
                    this.eventId = 0;
                },
                
                addEvent(message) {
                    this.events.push({
                        id: ++this.eventId,
                        timestamp: new Date().toLocaleTimeString(),
                        message: message
                    });
                    
                    // Keep only last 100 events
                    if (this.events.length > 100) {
                        this.events = this.events.slice(-100);
                    }
                }
            }
        }
    </script>
</body>
</html>
