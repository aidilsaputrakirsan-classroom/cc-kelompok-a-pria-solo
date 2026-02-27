<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CoderController extends Controller
{
    /**
     * Show chat interface
     * Route: GET /coder
     */
    public function index(Content $content)
    {
        // Use Font Awesome 5 from OpenAdmin (already loaded)
        // No need to load additional Font Awesome
        
        // Load custom CSS and JS
        Admin::css(asset('css/coder-chat.css'));
        Admin::js(asset('js/coder-chat.js'));

        return $content
            ->title('AI Coder Assistant')
            ->description('Chat interaktif dengan AI untuk bantuan coding')
            ->body(view('coder.chat'));
    }

    /**
     * Send message to AI chatbot
     * Route: POST /api/coder/chat
     */
    public function chat(Request $request)
    {
        try {
            // Validasi request
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:10000',
                'file_content' => 'nullable|string|max:50000', // Content dari file teks yang di-attach
                'file_name' => 'nullable|string|max:255', // Nama file yang di-attach
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $message = $request->input('message');
            $fileContent = $request->input('file_content');
            $fileName = $request->input('file_name');

            // Ambil API key dari config (best practice Laravel)
            $apiKey = config('services.coder_ai.api_key');
            $apiUrl = config('services.coder_ai.url', 'https://b4mjpyopy7txcyz5u55vptdi.agents.do-ai.run');

            // Trim whitespace jika ada
            $apiKey = $apiKey ? trim($apiKey) : null;

            // Log untuk debugging (tanpa menampilkan full API key)
            Log::info('Checking API key configuration', [
                'has_api_key' => !empty($apiKey),
                'api_key_length' => $apiKey ? strlen($apiKey) : 0,
                'api_key_preview' => $apiKey ? substr($apiKey, 0, 5) . '***' : 'null',
                'api_url' => $apiUrl,
                'env_direct' => env('CODER_AI_API_KEY') ? 'exists' : 'not_found'
            ]);

            if (empty($apiKey)) {
                Log::error('CODER_AI_API_KEY tidak ditemukan', [
                    'config_value' => config('services.coder_ai.api_key'),
                    'env_direct' => env('CODER_AI_API_KEY') ? 'exists' : 'not_found',
                    'suggestion' => 'Pastikan CODER_AI_API_KEY ada di .env dan jalankan: php artisan config:clear'
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'API key tidak dikonfigurasi. Pastikan CODER_AI_API_KEY ada di file .env dan jalankan: php artisan config:clear'
                ], 500);
            }

            // Siapkan payload sesuai format API dokumentasi
            // Format: messages array dengan role dan content
            $content = $message;
            
            // Jika ada file yang di-attach, tambahkan ke content
            if (!empty($fileContent) && !empty($fileName)) {
                $content .= "\n\n[File: " . $fileName . "]\n" . $fileContent;
            }
            
            $payload = [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $content
                    ]
                ],
                'stream' => false,
                'include_functions_info' => false,
                'include_retrieval_info' => false,
                'include_guardrails_info' => false
            ];

            // Endpoint sesuai dokumentasi
            $endpoint = rtrim($apiUrl, '/') . '/api/v1/chat/completions';

            Log::info('Sending message to AI chatbot', [
                'endpoint' => $endpoint,
                'has_file' => !empty($fileContent),
                'file_name' => $fileName ?? null,
                'message_length' => strlen($message),
                'content_length' => strlen($content)
            ]);

            $response = null;
            $lastError = null;

            try {
                // Kirim request sesuai format dokumentasi
                $response = Http::timeout(120)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Accept' => 'application/json'
                    ])
                    ->post($endpoint, $payload);

                Log::info('API response received', [
                    'status' => $response->status(),
                    'endpoint' => $endpoint
                ]);

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error('Exception calling API', [
                    'endpoint' => $endpoint,
                    'error' => $lastError,
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Handle response
            if (!$response || !$response->successful()) {
                $statusCode = $response ? $response->status() : 500;
                $errorBody = $response ? ($response->json() ?? $response->body()) : ($lastError ?? 'No response received');

                Log::error('AI chatbot API error', [
                    'status' => $statusCode,
                    'endpoint' => $endpoint,
                    'body_preview' => is_string($errorBody) ? substr($errorBody, 0, 500) : (is_array($errorBody) ? json_encode($errorBody) : $errorBody),
                    'last_error' => $lastError
                ]);

                $errorMessage = 'Error dari AI chatbot';
                if ($statusCode === 405) {
                    $errorMessage = 'Method tidak diizinkan (405). Endpoint mungkin tidak menerima POST request.';
                } elseif ($statusCode === 404) {
                    $errorMessage = 'Endpoint tidak ditemukan (404). Silakan periksa URL API.';
                } elseif ($statusCode === 401 || $statusCode === 403) {
                    $errorMessage = 'Autentikasi gagal (' . $statusCode . '). Silakan periksa API key.';
                } else {
                    $errorMessage = 'Error dari AI chatbot: HTTP ' . $statusCode;
                    if (is_array($errorBody) && isset($errorBody['error']['message'])) {
                        $errorMessage = $errorBody['error']['message'];
                    } elseif (is_array($errorBody) && isset($errorBody['message'])) {
                        $errorMessage = $errorBody['message'];
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $errorBody,
                    'status_code' => $statusCode
                ], $statusCode);
            }

            // Try to get JSON response, fallback to body if not JSON
            $responseBody = $response->body();
            $responseData = null;
            
            try {
                $responseData = $response->json();
            } catch (\Exception $e) {
                // If not JSON, treat as plain text
                Log::warning('Response is not JSON, treating as plain text', [
                    'error' => $e->getMessage(),
                    'body_preview' => substr($responseBody, 0, 500)
                ]);
                $responseData = $responseBody;
            }
            
            // Log full response for debugging
            Log::info('AI chatbot response received', [
                'has_response' => !empty($responseData),
                'response_type' => gettype($responseData),
                'response_keys' => is_array($responseData) ? array_keys($responseData) : 'not_array',
                'has_retrieval_info' => is_array($responseData) && isset($responseData['retrieval_info']),
                'has_functions_info' => is_array($responseData) && isset($responseData['functions_info']),
                'has_guardrails_info' => is_array($responseData) && isset($responseData['guardrails_info']),
                'response_preview' => is_string($responseData) ? substr($responseData, 0, 500) : (is_array($responseData) ? json_encode(array_slice($responseData, 0, 2), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'unknown')
            ]);

            // Extract response text from OpenAI-style format
            // Format: { "choices": [{ "message": { "content": "..." } }] }
            $responseText = null;
            
            Log::info('Extracting response text', [
                'response_data_type' => gettype($responseData),
                'is_array' => is_array($responseData),
                'has_choices' => is_array($responseData) && isset($responseData['choices']),
                'choices_count' => is_array($responseData) && isset($responseData['choices']) ? count($responseData['choices']) : 0
            ]);
            
            if (is_string($responseData)) {
                $responseText = trim($responseData);
                Log::info('Response is string', ['length' => strlen($responseText)]);
            } elseif (is_array($responseData)) {
                // Try OpenAI-style format first (choices[0].message.content)
                if (isset($responseData['choices']) && is_array($responseData['choices']) && !empty($responseData['choices'])) {
                    $firstChoice = $responseData['choices'][0];
                    Log::info('First choice structure', [
                        'has_message' => isset($firstChoice['message']),
                        'has_content' => isset($firstChoice['message']['content']),
                        'content_empty' => isset($firstChoice['message']['content']) ? empty(trim($firstChoice['message']['content'])) : 'not_set',
                        'has_reasoning_content' => isset($firstChoice['message']['reasoning_content']),
                        'has_text' => isset($firstChoice['text']),
                        'choice_keys' => array_keys($firstChoice),
                        'message_type' => isset($firstChoice['message']) ? gettype($firstChoice['message']) : 'not_set',
                        'message_keys' => isset($firstChoice['message']) && is_array($firstChoice['message']) ? array_keys($firstChoice['message']) : 'not_array',
                        'content_preview' => isset($firstChoice['message']['content']) ? substr($firstChoice['message']['content'], 0, 100) : 'not_set',
                        'reasoning_preview' => isset($firstChoice['message']['reasoning_content']) ? substr($firstChoice['message']['reasoning_content'], 0, 100) : 'not_set'
                    ]);
                    
                    // Try different paths to get content
                    // Priority: content (actual answer) > text > other fields
                    // Skip reasoning_content as it contains system instructions, not the actual answer
                    if (isset($firstChoice['message']['content']) && !empty(trim($firstChoice['message']['content']))) {
                        $responseText = $firstChoice['message']['content'];
                        Log::info('Found content in choices[0].message.content', ['length' => strlen($responseText), 'preview' => substr($responseText, 0, 100)]);
                    } elseif (isset($firstChoice['message']) && is_string($firstChoice['message']) && !empty(trim($firstChoice['message']))) {
                        $responseText = $firstChoice['message'];
                        Log::info('Found message as string in choices[0].message', ['length' => strlen($responseText)]);
                    } elseif (isset($firstChoice['text']) && !empty(trim($firstChoice['text']))) {
                        $responseText = $firstChoice['text'];
                        Log::info('Found text in choices[0].text', ['length' => strlen($responseText)]);
                    } elseif (isset($firstChoice['delta']['content']) && !empty(trim($firstChoice['delta']['content']))) {
                        $responseText = $firstChoice['delta']['content'];
                        Log::info('Found content in choices[0].delta.content', ['length' => strlen($responseText)]);
                    } elseif (isset($firstChoice['content']) && !empty(trim($firstChoice['content']))) {
                        $responseText = $firstChoice['content'];
                        Log::info('Found content in choices[0].content', ['length' => strlen($responseText)]);
                    } elseif (isset($firstChoice['message']) && is_array($firstChoice['message'])) {
                        // Try to find content field first, skip reasoning_content and role
                        foreach ($firstChoice['message'] as $key => $value) {
                            // Skip role and reasoning_content fields (reasoning_content is system instructions)
                            if ($key === 'role' || $key === 'reasoning_content') {
                                continue;
                            }
                            // Prioritize 'content' field
                            if ($key === 'content' && is_string($value) && !empty(trim($value))) {
                                $responseText = $value;
                                Log::info('Found content in choices[0].message[' . $key . ']', ['length' => strlen($responseText)]);
                                break;
                            }
                        }
                        
                        // If still not found, try other string fields (but still skip reasoning_content)
                        if ($responseText === null) {
                            foreach ($firstChoice['message'] as $key => $value) {
                                if ($key === 'role' || $key === 'reasoning_content') {
                                    continue;
                                }
                                if (is_string($value) && !empty(trim($value))) {
                                    $responseText = $value;
                                    Log::info('Found string in choices[0].message[' . $key . ']', ['length' => strlen($responseText)]);
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Last resort: use reasoning_content only if nothing else found
                    if ($responseText === null && isset($firstChoice['message']['reasoning_content']) && !empty(trim($firstChoice['message']['reasoning_content']))) {
                        $responseText = $firstChoice['message']['reasoning_content'];
                        Log::warning('Using reasoning_content as fallback (may contain system instructions)', ['length' => strlen($responseText)]);
                    }
                }
                
                // If not OpenAI format, try other common formats
                // Skip 'response' field if it only contains role name like "assistant"
                if ($responseText === null) {
                    Log::info('Trying other response formats');
                    $responseText = $responseData['message'] 
                        ?? $responseData['text'] 
                        ?? $responseData['content']
                        ?? $responseData['answer']
                        ?? $responseData['output']
                        ?? (isset($responseData['data']['content']) ? $responseData['data']['content'] : null)
                        ?? (isset($responseData['data']) && is_string($responseData['data']) ? $responseData['data'] : null)
                        ?? null;
                    
                    // Only use 'response' field if it's not just a role name
                    if ($responseText === null && isset($responseData['response'])) {
                        $responseField = $responseData['response'];
                        // Check if it's not just "assistant", "user", or "system"
                        if (is_string($responseField) && !in_array(strtolower(trim($responseField)), ['assistant', 'user', 'system']) && !empty(trim($responseField))) {
                            $responseText = $responseField;
                        }
                    }
                    
                    if ($responseText !== null) {
                        Log::info('Found response in alternative format', ['length' => strlen($responseText)]);
                    }
                }
                
                // If still null, try to get first string value
                if ($responseText === null) {
                    Log::info('Searching for first string value in response');
                    foreach ($responseData as $key => $value) {
                        if (is_string($value) && !empty(trim($value))) {
                            $responseText = $value;
                            Log::info('Found string value', ['key' => $key, 'length' => strlen($responseText)]);
                            break;
                        } elseif (is_array($value)) {
                            // Recursively search in nested arrays
                            $found = $this->extractStringFromArray($value);
                            if ($found) {
                                $responseText = $found;
                                Log::info('Found string in nested array', ['key' => $key, 'length' => strlen($responseText)]);
                                break;
                            }
                        }
                    }
                }
                
                // If still null, convert to JSON string
                if ($responseText === null) {
                    $responseText = json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    Log::warning('No text found, converting to JSON', ['json_length' => strlen($responseText)]);
                }
            } else {
                $responseText = (string) $responseData;
                Log::info('Converting response to string', ['length' => strlen($responseText)]);
            }
            
            // Ensure we have a response
            if (empty($responseText) || trim($responseText) === '') {
                Log::error('Empty response text extracted', [
                    'response_data_type' => gettype($responseData),
                    'response_data_structure' => is_array($responseData) ? json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $responseData,
                    'response_text' => $responseText,
                    'response_keys' => is_array($responseData) ? array_keys($responseData) : 'not_array'
                ]);
                
                // Try one more time with full response as JSON
                if (is_array($responseData)) {
                    $responseText = json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    Log::warning('Using full response as JSON since no text found', ['json_length' => strlen($responseText)]);
                } else {
                    $responseText = 'Tidak ada respons dari AI chatbot. Silakan coba lagi.';
                }
            } else {
                Log::info('Successfully extracted response', [
                    'length' => strlen($responseText),
                    'preview' => substr($responseText, 0, 100),
                    'has_retrieval_info' => is_array($responseData) && isset($responseData['retrieval_info']),
                    'has_functions_info' => is_array($responseData) && isset($responseData['functions_info']),
                    'has_guardrails_info' => is_array($responseData) && isset($responseData['guardrails_info'])
                ]);
            }

            // Prepare response with additional info if available
            $responsePayload = [
                'success' => true,
                'response' => $responseText,
                'data' => $responseData
            ];

            // Include additional info if present in response
            // Check for retrieval_info (from include_retrieval_info parameter)
            if (is_array($responseData)) {
                // Check top-level retrieval_info
                if (isset($responseData['retrieval_info'])) {
                    $responsePayload['retrieval_info'] = $responseData['retrieval_info'];
                }
                // Check for retrieval object (alternative format)
                elseif (isset($responseData['retrieval'])) {
                    $responsePayload['retrieval'] = $responseData['retrieval'];
                }
                
                // Check for functions_info (from include_functions_info parameter)
                if (isset($responseData['functions_info'])) {
                    $responsePayload['functions_info'] = $responseData['functions_info'];
                }
                // Check for functions object (alternative format)
                elseif (isset($responseData['functions'])) {
                    $responsePayload['functions'] = $responseData['functions'];
                }
                
                // Check for guardrails_info (from include_guardrails_info parameter)
                if (isset($responseData['guardrails_info'])) {
                    $responsePayload['guardrails_info'] = $responseData['guardrails_info'];
                }
                // Check for guardrails object (alternative format)
                elseif (isset($responseData['guardrails'])) {
                    $responsePayload['guardrails'] = $responseData['guardrails'];
                }
            }

            Log::info('Final response payload prepared', [
                'has_retrieval' => isset($responsePayload['retrieval']) || isset($responsePayload['retrieval_info']),
                'has_functions' => isset($responsePayload['functions']) || isset($responsePayload['functions_info']),
                'has_guardrails' => isset($responsePayload['guardrails']) || isset($responsePayload['guardrails_info']),
                'response_length' => strlen($responseText)
            ]);

            return response()->json($responsePayload);

        } catch (\Exception $e) {
            Log::error('Exception in CoderController@chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function to extract string from nested array
     */
    private function extractStringFromArray($array, $maxDepth = 3, $currentDepth = 0)
    {
        if ($currentDepth >= $maxDepth) {
            return null;
        }
        
        foreach ($array as $key => $value) {
            if (is_string($value) && !empty(trim($value))) {
                return $value;
            } elseif (is_array($value)) {
                $found = $this->extractStringFromArray($value, $maxDepth, $currentDepth + 1);
                if ($found) {
                    return $found;
                }
            }
        }
        
        return null;
    }

    /**
     * Upload and read text file
     * Route: POST /api/coder/upload-file
     */
    public function uploadFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:txt,text,log,md,json,xml,html,css,js,php,py,java,cpp,c,cs,go,rs,swift,kt,rb,sh,bat,yml,yaml,ini,conf,env|max:10240', // Max 10MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileContent = file_get_contents($file->getRealPath());

            // Validasi bahwa file benar-benar teks
            if (!mb_check_encoding($fileContent, 'UTF-8')) {
                // Coba decode jika bukan UTF-8
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'auto');
            }

            Log::info('Text file uploaded', [
                'file_name' => $fileName,
                'file_size' => strlen($fileContent),
                'file_type' => $file->getMimeType()
            ]);

            return response()->json([
                'success' => true,
                'file_name' => $fileName,
                'file_content' => $fileContent,
                'file_size' => strlen($fileContent)
            ]);

        } catch (\Exception $e) {
            Log::error('Exception in CoderController@uploadFile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membaca file: ' . $e->getMessage()
            ], 500);
        }
    }
}
