<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    xero_connected: Boolean,
    flash: Object,
});

// Get the app URL from shared Inertia data
const page = usePage();
const appUrl = page.props.appUrl || '';

// Messages for user feedback
const successMessage = ref('');
const errorMessage = ref('');
const testResult = ref(null);
const isTestingConnection = ref(false);

// Check for Xero authentication from localStorage (set by the iframe-redirect view)
onMounted(() => {
    if (window.location.search.includes('xero_auth=true')) {
        // Get auth data from localStorage
        const code = localStorage.getItem('xero_auth_code');
        const state = localStorage.getItem('xero_auth_state');
        const timestamp = localStorage.getItem('xero_auth_time');
        
        if (code && state) {
            // Clear the data immediately to prevent reuse
            localStorage.removeItem('xero_auth_code');
            localStorage.removeItem('xero_auth_state');
            localStorage.removeItem('xero_auth_time');
            
            // Process this code on the server side
            successMessage.value = 'Processing Xero authorization...';
            
            // Send the code to the server
            router.post('/api/xero/process-auth', {
                code,
                state
            }, {
                onSuccess: () => {
                    successMessage.value = 'Successfully connected to Xero!';
                    setTimeout(() => {
                        // Reload page after success to update UI
                        window.location.href = '/dashboard';
                    }, 1500);
                },
                onError: (errors) => {
                    errorMessage.value = 'Failed to connect to Xero. ' + Object.values(errors).join(', ');
                }
            });
        }
    }
});

// Function to test Xero connection
const testXeroConnection = async () => {
    isTestingConnection.value = true;
    errorMessage.value = '';
    successMessage.value = 'Testing Xero connection...';
    testResult.value = null;
    
    try {
        // Use fetch with proper error handling
        const response = await fetch('/xero/verify-connection', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        // Get the response text first, then determine how to handle it
        const responseText = await response.text();
        
        // Check if we got a successful response
        if (!response.ok) {
            // Try to parse the error as JSON, but don't re-read the stream
            try {
                const errorData = JSON.parse(responseText);
                throw new Error(errorData.message || `Server error: ${response.status}`);
            } catch (parseError) {
                // If we can't parse JSON, it might be HTML error page
                if (responseText.includes('<!DOCTYPE') || responseText.includes('<html')) {
                    throw new Error(`Server error: ${response.status}. The server returned an HTML page instead of JSON.`);
                } else {
                    throw new Error(`Server error: ${response.status}. Response was not valid JSON: ${responseText.substring(0, 100)}...`);
                }
            }
        }
        
        // Parse the successful response - don't try to read the body again
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            throw new Error(`Failed to parse successful response as JSON. Response starts with: ${responseText.substring(0, 100)}...`);
        }
        
        testResult.value = data;
        
        if (data.connected) {
            successMessage.value = data.message;
            errorMessage.value = '';
        } else {
            errorMessage.value = data.message;
            successMessage.value = '';
        }
    } catch (error) {
        errorMessage.value = 'Error testing Xero connection: ' + (error.message || 'Unknown error');
        successMessage.value = '';
        testResult.value = {
            connected: false,
            message: error.message || 'Unknown error',
            error: true
        };
    } finally {
        isTestingConnection.value = false;
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Success/Error Messages -->
                <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <p>{{ $page.props.flash.success }}</p>
                </div>
                <div v-if="$page.props.flash && $page.props.flash.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <p>{{ $page.props.flash.error }}</p>
                </div>
                
                <!-- Local state messages -->
                <div v-if="successMessage" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <p>{{ successMessage }}</p>
                </div>
                <div v-if="errorMessage" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <p>{{ errorMessage }}</p>
                </div>

                <!-- Xero Connection Card -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Xero Connection</h3>
                        
                        <div v-if="xero_connected" class="flex items-center mb-4">
                            <div class="mr-4 flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-green-100">
                                    <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                            </div>
                            <div>
                                <h4 class="text-md font-medium text-gray-900">Connected to Xero</h4>
                                <p class="text-sm text-gray-500">Your application is connected to Xero. Currency exchanges will be synced automatically.</p>
                            </div>
                        </div>
                        
                        <div v-else class="flex items-center mb-4">
                            <div class="mr-4 flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-red-100">
                                    <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </span>
                            </div>
                            <div>
                                <h4 class="text-md font-medium text-gray-900">Not Connected to Xero</h4>
                                <p class="text-sm text-gray-500">Connect your Xero account to enable automatic syncing of currency exchanges.</p>
                            </div>
                        </div>
                        
                        <div class="flex">
                            <a v-if="!xero_connected" 
                                :href="route('xero.connect')"
                                target="_blank"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Connect to Xero
                            </a>
                            
                            <div v-else class="flex space-x-2">
                                <form :action="route('xero.disconnect')" method="POST">
                                    <input type="hidden" name="_token" :value="$page.props.csrf_token">
                                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                        Disconnect from Xero
                                    </button>
                                </form>
                                
                                <button @click="testXeroConnection" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <span v-if="isTestingConnection" class="inline-flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Testing...
                                    </span>
                                    <span v-else>Test Connection</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Card -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        You're logged in!
                    </div>
                </div>
                
                <!-- Xero Connection Test Results -->
                <div v-if="testResult" class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Xero Connection Test Results</h3>
                        
                        <div class="flex mb-4 items-center">
                            <div class="mr-4 flex-shrink-0">
                                <span v-if="testResult.connected" class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-green-100">
                                    <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span v-else class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-red-100">
                                    <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </span>
                            </div>
                            <div>
                                <h4 class="text-md font-medium text-gray-900">{{ testResult.message }}</h4>
                                <p v-if="testResult.error_type" class="text-sm text-red-500">Error type: {{ testResult.error_type }}</p>
                            </div>
                        </div>
                        
                        <!-- Show currencies if available -->
                        <div v-if="testResult.connected && testResult.data && testResult.data.currencies">
                            <h4 class="font-medium mb-2">Available Currencies:</h4>
                            <div class="bg-gray-50 p-4 rounded-md overflow-auto max-h-64">
                                <pre class="text-xs">{{ JSON.stringify(testResult.data.currencies, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
