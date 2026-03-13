/**
 * Coder Chat JavaScript
 * Handles interactive chat with AI chatbot
 */

(function() {
    'use strict';

    // Declare variables
    let chatMessages, chatForm, messageInput, sendBtn, attachFileBtn, fileInput;
    let fileAttachmentArea, attachedFileName, attachedFileSize, removeFileBtn, loadingOverlay;
    let attachedFile = null;
    let attachedFileContent = null;

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        // Get all elements after DOM is ready
        chatMessages = document.getElementById('chat-messages');
        chatForm = document.getElementById('chat-form');
        messageInput = document.getElementById('message-input');
        sendBtn = document.getElementById('send-btn');
        attachFileBtn = document.getElementById('attach-file-btn');
        fileInput = document.getElementById('file-input');
        fileAttachmentArea = document.getElementById('file-attachment-area');
        attachedFileName = document.getElementById('attached-file-name');
        attachedFileSize = document.getElementById('attached-file-size');
        removeFileBtn = document.getElementById('remove-file-btn');
        loadingOverlay = document.getElementById('loading-overlay');

        // Check if essential elements exist (warn but don't block)
        const missingElements = [];
        if (!chatMessages) missingElements.push('chatMessages');
        if (!chatForm) missingElements.push('chatForm');
        if (!messageInput) missingElements.push('messageInput');
        if (!sendBtn) missingElements.push('sendBtn');
        if (!attachFileBtn) missingElements.push('attachFileBtn');
        if (!fileInput) missingElements.push('fileInput');
        if (!fileAttachmentArea) missingElements.push('fileAttachmentArea');
        if (!attachedFileName) missingElements.push('attachedFileName');
        if (!attachedFileSize) missingElements.push('attachedFileSize');
        if (!removeFileBtn) missingElements.push('removeFileBtn');
        if (!loadingOverlay) missingElements.push('loadingOverlay');
        
        if (missingElements.length > 0) {
            console.warn('Some elements are missing:', missingElements);
        }
        
        // Only block if essential elements are missing
        if (!chatMessages || !chatForm || !messageInput) {
            console.error('Critical elements missing, cannot initialize');
            return;
        }

        initializeEventListeners();
        scrollToBottom();
    });

    function initializeEventListeners() {
        // Check if essential elements exist
        if (!chatForm || !messageInput) {
            console.error('Cannot initialize event listeners: missing essential elements', {
                chatForm: !!chatForm,
                messageInput: !!messageInput
            });
            return;
        }

        // Form submission
        chatForm.addEventListener('submit', handleFormSubmit);

        // Enter key handling (Shift+Enter for new line, Enter to send)
        messageInput.addEventListener('keydown', function(e) {
            // Jika Enter ditekan tanpa Shift, kirim pesan
            if (e.key === 'Enter' || e.keyCode === 13) {
                if (!e.shiftKey) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Pastikan ada pesan atau file yang di-attach
                    const message = messageInput.value.trim();
                    if (message || attachedFile) {
                        handleFormSubmit(e);
                    }
                    return false;
                }
                // Jika Shift+Enter, biarkan default behavior (baris baru)
            }
        });

        // Juga handle keypress sebagai backup
        messageInput.addEventListener('keypress', function(e) {
            if ((e.key === 'Enter' || e.keyCode === 13) && !e.shiftKey) {
                e.preventDefault();
                const message = messageInput.value.trim();
                if (message || attachedFile) {
                    handleFormSubmit(e);
                }
                return false;
            }
        });

        // File attachment
        if (attachFileBtn && fileInput) {
            attachFileBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Attach file button clicked');
                if (fileInput) {
                    fileInput.click();
                } else {
                    console.error('fileInput element not found');
                }
            });
            
            // Also handle direct click on button
            attachFileBtn.addEventListener('mousedown', function(e) {
                e.preventDefault();
            });
        } else {
            console.error('Attach file button or file input not found', {
                attachFileBtn: !!attachFileBtn,
                fileInput: !!fileInput
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', handleFileSelect);
        }
        
        if (removeFileBtn) {
            removeFileBtn.addEventListener('click', removeAttachedFile);
        }
    }

    function handleFormSubmit(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Prevent multiple submissions
        if (sendBtn.disabled) {
            return;
        }

        if (!messageInput) {
            console.error('messageInput element not found');
            return;
        }

        const message = messageInput.value.trim();
        if (!message && !attachedFile) {
            alert('Silakan masukkan pesan atau attach file');
            if (messageInput) {
                messageInput.focus();
            }
            return;
        }

        if (!message && attachedFile) {
            alert('Silakan masukkan pesan untuk mengirim file');
            if (messageInput) {
                messageInput.focus();
            }
            return;
        }

        // Add user message to chat
        if (message) {
            addMessage('user', message);
        }

        // Clear input
        messageInput.value = '';
        messageInput.style.height = 'auto';

        // Show loading
        showLoading();

        // Prepare payload
        const payload = {
            message: message
        };

        if (attachedFile && attachedFileContent) {
            payload.file_content = attachedFileContent;
            payload.file_name = attachedFile.name;
        }

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                         document.querySelector('input[name="_token"]')?.value ||
                         '';

        // Send to API
        fetch('/projess/api/coder/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(async response => {
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            
            // Try to parse as JSON
            let data;
            try {
                const text = await response.text();
                console.log('Response text (first 500 chars):', text.substring(0, 500));
                
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    // If not JSON, treat as error
                    console.error('Response is not valid JSON:', e);
                    throw new Error('Response tidak valid: ' + text.substring(0, 100));
                }
            } catch (error) {
                console.error('Error parsing response:', error);
                throw error;
            }
            
            // Check if response indicates error
            if (!response.ok) {
                throw new Error(data.message || data.error || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            return data;
        })
        .then(data => {
            console.log('Response data:', data);
            hideLoading();

            if (data.success) {
                // Extract response text from various possible formats
                let responseText = '';
                
                if (typeof data.response === 'string') {
                    responseText = data.response;
                } else if (data.data) {
                    if (typeof data.data === 'string') {
                        responseText = data.data;
                    } else if (data.data.response) {
                        responseText = data.data.response;
                    } else if (data.data.message) {
                        responseText = data.data.message;
                    } else if (data.data.text) {
                        responseText = data.data.text;
                    } else if (data.data.content) {
                        responseText = data.data.content;
                    } else {
                        // Try to stringify if it's an object
                        responseText = JSON.stringify(data.data, null, 2);
                    }
                } else if (data.message && data.success) {
                    responseText = data.message;
                } else {
                    responseText = JSON.stringify(data, null, 2);
                }

                // If response is empty, show a message
                if (!responseText || responseText.trim() === '') {
                    responseText = 'Tidak ada respons dari AI. Silakan coba lagi.';
                }

                console.log('Extracted response text:', responseText);
                
                // Build full message with additional info if available
                let fullMessage = responseText;
                
                // Add retrieval info if available
                if (data.retrieval || data.retrieval_info) {
                    const retrieval = data.retrieval || data.retrieval_info;
                    if (retrieval && retrieval.retrieved_data && retrieval.retrieved_data.length > 0) {
                        fullMessage += '\n\n--- 📚 Sumber Referensi ---\n';
                        retrieval.retrieved_data.forEach((item, index) => {
                            fullMessage += `${index + 1}. ${item.filename || 'File'} (score: ${item.score})\n`;
                            if (item.page_content) {
                                fullMessage += `   ${item.page_content.substring(0, 100)}...\n`;
                            }
                        });
                    }
                }
                
                // Add functions info if available
                if (data.functions || data.functions_info) {
                    const functions = data.functions || data.functions_info;
                    if (functions && functions.called_functions && functions.called_functions.length > 0) {
                        fullMessage += '\n\n--- ⚙️ Fungsi yang Dipanggil ---\n';
                        functions.called_functions.forEach(func => {
                            fullMessage += `- ${func}\n`;
                        });
                    }
                }
                
                // Add guardrails info if available
                if (data.guardrails || data.guardrails_info) {
                    const guardrails = data.guardrails || data.guardrails_info;
                    if (guardrails && guardrails.triggered_guardrails && guardrails.triggered_guardrails.length > 0) {
                        fullMessage += '\n\n--- 🛡️ Guardrails yang Terpicu ---\n';
                        guardrails.triggered_guardrails.forEach(guard => {
                            fullMessage += `- ${guard.rule_name || guard.rule}: ${guard.message || 'Triggered'}\n`;
                        });
                    }
                }
                
                addMessage('assistant', fullMessage);

                // Remove attached file after sending
                if (attachedFile) {
                    removeAttachedFile();
                }
            } else {
                // Show error
                const errorMsg = data.message || data.error || 'Terjadi kesalahan';
                console.error('API returned error:', errorMsg);
                addMessage('assistant', '❌ Error: ' + errorMsg, true);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Fetch error:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack
            });
            addMessage('assistant', '❌ Error: Gagal terhubung ke server. ' + (error.message || 'Unknown error'), true);
        });
    }

    function handleFileSelect(e) {
        console.log('File selected event triggered', e);
        const file = e.target.files[0];
        if (!file) {
            console.log('No file selected');
            return;
        }
        
        console.log('File selected:', {
            name: file.name,
            size: file.size,
            type: file.type
        });

        // Validate file type (text files only)
        const allowedTypes = [
            'text/plain', 'text/html', 'text/css', 'text/javascript',
            'application/json', 'application/xml', 'text/xml',
            'text/markdown', 'text/x-log'
        ];

        const allowedExtensions = ['.txt', '.text', '.log', '.md', '.json', '.xml', 
            '.html', '.css', '.js', '.php', '.py', '.java', '.cpp', '.c', '.cs', 
            '.go', '.rs', '.swift', '.kt', '.rb', '.sh', '.bat', '.yml', '.yaml', 
            '.ini', '.conf', '.env'];

        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        const isValidType = allowedTypes.includes(file.type) || 
                           allowedExtensions.includes(fileExtension);

        if (!isValidType) {
            alert('Hanya file teks yang diizinkan (txt, md, json, xml, html, css, js, php, py, dll)');
            fileInput.value = '';
            return;
        }

        // Validate file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('Ukuran file maksimal 10MB');
            fileInput.value = '';
            return;
        }

        // Read file content
        const reader = new FileReader();
        reader.onload = function(e) {
            attachedFile = file;
            attachedFileContent = e.target.result;

            // Show attachment area
            if (attachedFileName) {
                attachedFileName.textContent = file.name;
            }
            if (attachedFileSize) {
                attachedFileSize.textContent = formatFileSize(file.size);
            }
            if (fileAttachmentArea) {
                fileAttachmentArea.classList.remove('d-none');
            }

            // Scroll to bottom
            scrollToBottom();
        };

        reader.onerror = function() {
            alert('Gagal membaca file');
            fileInput.value = '';
        };

        reader.readAsText(file, 'UTF-8');
    }

    function removeAttachedFile() {
        attachedFile = null;
        attachedFileContent = null;
        if (fileInput) {
            fileInput.value = '';
        }
        if (fileAttachmentArea) {
            fileAttachmentArea.classList.add('d-none');
        }
    }

    function addMessage(role, content, isError = false) {
        if (!chatMessages) {
            console.error('chatMessages element not found');
            return;
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${role}`;

        const bubble = document.createElement('div');
        bubble.className = `message-bubble ${role}`;
        
        if (isError) {
            bubble.style.backgroundColor = '#dc3545';
            bubble.style.color = 'white';
        }

        // Format content (support code blocks)
        const formattedContent = formatMessageContent(content);
        bubble.innerHTML = formattedContent;

        const time = document.createElement('div');
        time.className = 'message-time';
        time.textContent = new Date().toLocaleTimeString('id-ID', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });

        messageDiv.appendChild(bubble);
        messageDiv.appendChild(time);
        chatMessages.appendChild(messageDiv);

        scrollToBottom();
    }

    function formatMessageContent(content) {
        // Escape HTML first
        let escaped = escapeHtml(content);

        // Detect code blocks (```language\ncode\n```)
        escaped = escaped.replace(/```(\w+)?\n([\s\S]*?)```/g, function(match, lang, code) {
            return '<div class="code-block"><strong>' + (lang || 'code') + ':</strong><pre>' + escapeHtml(code) + '</pre></div>';
        });

        // Detect inline code (`code`)
        escaped = escaped.replace(/`([^`]+)`/g, '<code style="background-color: #f4f4f4; padding: 2px 4px; border-radius: 3px;">$1</code>');

        // Convert newlines to <br>
        escaped = escaped.replace(/\n/g, '<br>');

        return escaped;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function scrollToBottom() {
        if (!chatMessages) return;
        setTimeout(() => {
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }, 100);
    }

    function showLoading() {
        if (loadingOverlay) {
            loadingOverlay.classList.remove('d-none');
        }
        if (sendBtn) {
            sendBtn.disabled = true;
        }
    }

    function hideLoading() {
        if (loadingOverlay) {
            loadingOverlay.classList.add('d-none');
        }
        if (sendBtn) {
            sendBtn.disabled = false;
        }
    }

    // Auto-resize textarea - will be set up after DOM is ready
    function setupTextareaAutoResize() {
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Focus pada textarea saat halaman dimuat
            setTimeout(() => {
                if (messageInput) {
                    messageInput.focus();
                }
            }, 100);
        }
    }

    // Call setup after DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(setupTextareaAutoResize, 100);
    });

})();
