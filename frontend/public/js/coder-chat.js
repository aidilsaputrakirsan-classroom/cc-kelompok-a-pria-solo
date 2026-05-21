/**
 * Coder Chat - Modern, ChatGPT/Gemini-inspired conversational UI.
 *
 * UI behaviors:
 *   - Responsive sidebar (drawer on mobile <= 768px)
 *   - Capsule composer with smooth textarea auto-resize (cap 200px)
 *   - Dynamic send button state (gray disabled / dark active)
 *   - Inline "thinking" indicator above composer
 *
 * Backend integration is preserved 1:1:
 *   - POST /projess/api/coder/chat
 *   - GET  /projess/api/coder/conversations
 *   - GET  /projess/api/coder/conversations/{id}
 *   - CSRF token via meta[name="csrf-token"]
 *   - File upload: text-like → UTF-8 read; binary → Base64 data URL JSON envelope
 *   - Assistant replies: when marked + DOMPurify are loaded (e.g. Employee AI page),
 *     Markdown/GFM is rendered to sanitized HTML with Bootstrap-friendly tables.
 */

(function () {
    'use strict';

    // ---------- DOM refs ----------
    let chatApp,
        chatMessages,
        chatForm,
        messageInput,
        sendBtn,
        attachFileBtn,
        fileInput,
        fileAttachmentArea,
        attachedFileName,
        attachedFileSize,
        removeFileBtn,
        personalitySelect,
        globalDragOverlay,
        loadingOverlay,
        conversationList,
        newChatBtn,
        activeConversationLabel,
        sidebarToggleBtn,
        sidebarCloseBtn,
        chatBackdrop,
        chatSidebar;

    // ---------- State ----------
    let attachedFile = null;
    let attachedFileContent = null;
    let attachedFiles = [];
    let currentConversationId = null;
    const MOBILE_BREAKPOINT = 768;
    const MAX_TEXTAREA_HEIGHT = 200; // px
    let apiBaseUrl = '';
    let chatEndpoint = '/api/coder/chat';
    let conversationsEndpoint = '/api/coder/conversations';
    let conversationDetailEndpoint = '/api/coder/conversations/{id}';
    const defaultWelcomeTitle = 'What are you working on?';
    const defaultWelcomeDescription = 'Saya siap membantu coding, debugging, dan review dokumen kerja harian Anda.';

    // ---------- Bootstrap ----------
    document.addEventListener('DOMContentLoaded', function () {
        chatApp                 = document.getElementById('coder-chat-app');
        chatMessages            = document.getElementById('chat-messages');
        chatForm                = document.getElementById('chat-form');
        messageInput            = document.getElementById('message-input');
        sendBtn                 = document.getElementById('send-btn');
        attachFileBtn           = document.getElementById('attach-file-btn');
        fileInput               = document.getElementById('file-input');
        fileAttachmentArea      = document.getElementById('file-attachment-area');
        attachedFileName        = document.getElementById('attached-file-name');
        attachedFileSize        = document.getElementById('attached-file-size');
        removeFileBtn           = document.getElementById('remove-file-btn');
        personalitySelect       = document.getElementById('personality-select');
        globalDragOverlay       = document.getElementById('global-drag-overlay');
        loadingOverlay          = document.getElementById('loading-overlay');
        conversationList        = document.getElementById('conversation-list');
        newChatBtn              = document.getElementById('new-chat-btn');
        activeConversationLabel = document.getElementById('active-conversation-label');
        sidebarToggleBtn        = document.getElementById('sidebar-toggle-btn');
        sidebarCloseBtn         = document.getElementById('sidebar-close-btn');
        chatBackdrop            = document.getElementById('chat-backdrop');
        chatSidebar             = document.getElementById('chat-sidebar');

        apiBaseUrl = resolveApiBaseUrl();
        chatEndpoint = resolveEndpoint('chatEndpoint', '/api/coder/chat');
        conversationsEndpoint = resolveEndpoint('conversationsEndpoint', '/api/coder/conversations');
        conversationDetailEndpoint = resolveEndpoint('conversationDetailEndpoint', '/api/coder/conversations/{id}');
        applyUiTextsFromDataset();

        const missing = [];
        const optionalMissing = [];
        if (!chatMessages)       missing.push('chatMessages');
        if (!chatForm)           missing.push('chatForm');
        if (!messageInput)       missing.push('messageInput');
        if (!sendBtn)            missing.push('sendBtn');
        if (!attachFileBtn)      optionalMissing.push('attachFileBtn');
        if (!fileInput)          optionalMissing.push('fileInput');
        if (!fileAttachmentArea) optionalMissing.push('fileAttachmentArea');
        if (!loadingOverlay)     missing.push('loadingOverlay');
        if (!conversationList)   missing.push('conversationList');
        if (!newChatBtn)         missing.push('newChatBtn');
        if (missing.length) {
            console.warn('[coder-chat] Missing elements:', missing);
        }
        if (optionalMissing.length && (attachFileBtn || fileInput || fileAttachmentArea)) {
            // Warn only if file upload is partially rendered (likely unintended).
            console.warn('[coder-chat] Missing optional file elements:', optionalMissing);
        }

        if (!chatMessages || !chatForm || !messageInput) {
            console.error('[coder-chat] Critical elements missing, aborting init.');
            return;
        }

        configureMarkdownPipeline();

        initializeEventListeners();
        initializeGlobalDragOverlay();
        setupTextareaAutoResize();
        updateSendButtonState();
        loadConversations();
        scrollToBottom();

        setTimeout(function () {
            if (messageInput) messageInput.focus();
        }, 120);
    });

    function resolveApiBaseUrl() {
        // Prefer explicit base (supports custom OpenAdmin prefixes).
        const fromData = chatApp && chatApp.dataset ? chatApp.dataset.baseUrl : '';
        if (fromData) return fromData.replace(/\/+$/, '');

        const meta = document.querySelector('meta[name="coder-chat-base"]');
        const fromMeta = meta && meta.content ? meta.content : '';
        if (fromMeta) return fromMeta.replace(/\/+$/, '');

        // Fallback: same directory (e.g. /{adminPrefix}/coder -> /{adminPrefix})
        const p = window.location.pathname || '';
        const parts = p.split('/').filter(Boolean);
        if (!parts.length) return '';
        return '/' + parts[0];
    }

    function resolveEndpoint(datasetKey, fallback) {
        if (chatApp && chatApp.dataset && chatApp.dataset[datasetKey]) {
            return chatApp.dataset[datasetKey];
        }
        return fallback;
    }

    function applyUiTextsFromDataset() {
        if (!chatApp || !chatApp.dataset) return;

        const chatTitleText = document.getElementById('chat-title-text');
        const welcomeTitle = document.getElementById('welcome-title');
        const welcomeDescription = document.getElementById('welcome-description');

        if (chatTitleText && chatApp.dataset.chatTitle) {
            chatTitleText.textContent = chatApp.dataset.chatTitle;
        }

        if (welcomeTitle && chatApp.dataset.welcomeTitle) {
            welcomeTitle.textContent = chatApp.dataset.welcomeTitle;
        }

        if (welcomeDescription && chatApp.dataset.welcomeDescription) {
            welcomeDescription.textContent = chatApp.dataset.welcomeDescription;
        }
    }

    // ---------- Event wiring ----------
    function initializeEventListeners() {
        // Form submission
        chatForm.addEventListener('submit', handleFormSubmit);

        // Enter to send, Shift+Enter for newline
        messageInput.addEventListener('keydown', function (e) {
            if ((e.key === 'Enter' || e.keyCode === 13) && !e.shiftKey) {
                e.preventDefault();
                e.stopPropagation();
                const message = messageInput.value.trim();
                if (message || attachedFile || attachedFiles.length > 0) {
                    handleFormSubmit(e);
                }
                return false;
            }
        });

        // Live state updates
        messageInput.addEventListener('input', function () {
            autoResizeTextarea();
            updateSendButtonState();
        });

        // File attachment
        if (attachFileBtn && fileInput) {
            attachFileBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.click();
            });
            attachFileBtn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', handleFileSelect);
        }

        if (removeFileBtn) {
            removeFileBtn.addEventListener('click', removeAttachedFile);
        }

        if (newChatBtn) {
            newChatBtn.addEventListener('click', function () {
                currentConversationId = null;
                updateActiveConversationLabel('Chat Baru');
                clearChatMessages();
                renderActiveConversationItem();
                closeSidebarIfMobile();
                if (messageInput) messageInput.focus();
            });
        }

        // Sidebar (mobile drawer)
        if (sidebarToggleBtn) {
            sidebarToggleBtn.addEventListener('click', openSidebar);
        }
        if (sidebarCloseBtn) {
            sidebarCloseBtn.addEventListener('click', closeSidebar);
        }
        if (chatBackdrop) {
            chatBackdrop.addEventListener('click', closeSidebar);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && chatApp && chatApp.classList.contains('is-sidebar-open')) {
                closeSidebar();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > MOBILE_BREAKPOINT && chatApp) {
                chatApp.classList.remove('is-sidebar-open');
            }
        });
    }

    function hasFileDragDataTransfer(dt) {
        if (!dt || !dt.types) {
            return false;
        }
        for (var i = 0; i < dt.types.length; i++) {
            if (dt.types[i] === 'Files') {
                return true;
            }
        }
        return false;
    }

    function handOffDroppedFiles(fileList) {
        if (!fileList || !fileList.length) {
            return;
        }
        var arr = Array.prototype.slice.call(fileList);
        if (fileInput && fileInput.multiple) {
            addFiles(arr);
        } else {
            handleLegacySingleFile(arr[0]);
        }
    }

    function hideGlobalDragOverlay() {
        if (!globalDragOverlay) {
            return;
        }
        globalDragOverlay.classList.add('d-none');
        globalDragOverlay.setAttribute('aria-hidden', 'true');
    }

    function showGlobalDragOverlay() {
        if (!globalDragOverlay) {
            return;
        }
        globalDragOverlay.classList.remove('d-none');
        globalDragOverlay.setAttribute('aria-hidden', 'false');
    }

    function initializeGlobalDragOverlay() {
        if (!globalDragOverlay) {
            return;
        }

        window.addEventListener('dragenter', function (e) {
            if (!hasFileDragDataTransfer(e.dataTransfer)) {
                return;
            }
            e.preventDefault();
            showGlobalDragOverlay();
        }, true);

        window.addEventListener('dragover', function (e) {
            if (hasFileDragDataTransfer(e.dataTransfer)) {
                e.preventDefault();
            }
        }, true);

        window.addEventListener('dragend', function () {
            hideGlobalDragOverlay();
        }, true);

        globalDragOverlay.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = 'copy';
            }
        });

        globalDragOverlay.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            hideGlobalDragOverlay();
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                handOffDroppedFiles(e.dataTransfer.files);
            }
        });
    }

    // ---------- Sidebar (mobile drawer) ----------
    function openSidebar() {
        if (chatApp) chatApp.classList.add('is-sidebar-open');
    }

    function closeSidebar() {
        if (chatApp) chatApp.classList.remove('is-sidebar-open');
    }

    function closeSidebarIfMobile() {
        if (window.innerWidth <= MOBILE_BREAKPOINT) {
            closeSidebar();
        }
    }

    // ---------- Composer state ----------
    function autoResizeTextarea() {
        if (!messageInput) return;
        messageInput.style.height = 'auto';
        const scrollH = messageInput.scrollHeight;
        const clamped = Math.min(scrollH, MAX_TEXTAREA_HEIGHT);
        messageInput.style.height = clamped + 'px';
        messageInput.style.overflowY =
            scrollH > MAX_TEXTAREA_HEIGHT ? 'auto' : 'hidden';
    }

    function setupTextareaAutoResize() {
        if (!messageInput) return;
        messageInput.style.height = 'auto';
        messageInput.style.overflowY = 'hidden';
    }

    function updateSendButtonState() {
        if (!sendBtn || !messageInput) return;
        const hasText = messageInput.value.trim().length > 0;
        const hasFile = !!attachedFile || attachedFiles.length > 0;
        sendBtn.disabled = !(hasText || hasFile);
    }

    // ---------- Form submit / API call (backend integration preserved) ----------
    function handleFormSubmit(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        if (sendBtn && sendBtn.disabled && !attachedFile && attachedFiles.length < 1 && !messageInput.value.trim()) {
            return;
        }

        if (!messageInput) {
            console.error('[coder-chat] messageInput element not found');
            return;
        }

        const message = messageInput.value.trim();
        if (!message && !attachedFile && attachedFiles.length < 1) {
            alert('Silakan masukkan pesan atau attach file');
            messageInput.focus();
            return;
        }

        if (!message && (attachedFile || attachedFiles.length > 0)) {
            alert('Silakan masukkan pesan untuk mengirim file');
            messageInput.focus();
            return;
        }

        if (message) {
            addMessage('user', message);
        }

        messageInput.value = '';
        autoResizeTextarea();
        updateSendButtonState();

        showLoading();

        const csrfToken =
            (document.querySelector('meta[name="csrf-token"]') || {}).content ||
            (document.querySelector('input[name="_token"]') || {}).value ||
            '';

        const formData = new FormData();
        formData.append('message', message);
        if (currentConversationId) {
            formData.append('conversation_id', currentConversationId);
        }
        if (personalitySelect && personalitySelect.value) {
            formData.append('personality', personalitySelect.value);
        }
        if (attachedFiles.length > 0) {
            attachedFiles.forEach(function (item) {
                if (item && item.file) {
                    formData.append('files[]', item.file);
                }
            });
        } else if (attachedFile && attachedFileContent) {
            formData.append('file_content', attachedFileContent);
            formData.append('file_name', attachedFile.name);
        }

        fetch(apiBaseUrl + chatEndpoint, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(async function (response) {
                let data;
                try {
                    const text = await response.text();
                    try {
                        data = JSON.parse(text);
                    } catch (parseErr) {
                        console.error('[coder-chat] Response is not valid JSON:', parseErr);
                        throw new Error('Response tidak valid: ' + text.substring(0, 100));
                    }
                } catch (err) {
                    console.error('[coder-chat] Error parsing response:', err);
                    throw err;
                }

                if (!response.ok) {
                    throw new Error(
                        data.message ||
                            data.error ||
                            'HTTP ' + response.status + ': ' + response.statusText
                    );
                }
                return data;
            })
            .then(function (data) {
                hideLoading();

                if (data.success) {
                    if (data.conversation_id) {
                        currentConversationId = data.conversation_id;
                        loadConversations();
                    }

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
                            responseText = JSON.stringify(data.data, null, 2);
                        }
                    } else if (data.message && data.success) {
                        responseText = data.message;
                    } else {
                        responseText = JSON.stringify(data, null, 2);
                    }

                    if (!responseText || responseText.trim() === '') {
                        responseText = 'Tidak ada respons dari AI. Silakan coba lagi.';
                    }

                    let fullMessage = responseText;

                    if (data.retrieval || data.retrieval_info) {
                        const retrieval = data.retrieval || data.retrieval_info;
                        if (
                            retrieval &&
                            retrieval.retrieved_data &&
                            retrieval.retrieved_data.length > 0
                        ) {
                            fullMessage += '\n\n--- 📚 Sumber Referensi ---\n';
                            retrieval.retrieved_data.forEach(function (item, index) {
                                fullMessage +=
                                    index +
                                    1 +
                                    '. ' +
                                    (item.filename || 'File') +
                                    ' (score: ' +
                                    item.score +
                                    ')\n';
                                if (item.page_content) {
                                    fullMessage +=
                                        '   ' +
                                        item.page_content.substring(0, 100) +
                                        '...\n';
                                }
                            });
                        }
                    }

                    if (data.functions || data.functions_info) {
                        const functions = data.functions || data.functions_info;
                        if (
                            functions &&
                            functions.called_functions &&
                            functions.called_functions.length > 0
                        ) {
                            fullMessage += '\n\n--- ⚙️ Fungsi yang Dipanggil ---\n';
                            functions.called_functions.forEach(function (func) {
                                fullMessage += '- ' + func + '\n';
                            });
                        }
                    }

                    if (data.guardrails || data.guardrails_info) {
                        const guardrails = data.guardrails || data.guardrails_info;
                        if (
                            guardrails &&
                            guardrails.triggered_guardrails &&
                            guardrails.triggered_guardrails.length > 0
                        ) {
                            fullMessage += '\n\n--- 🛡️ Guardrails yang Terpicu ---\n';
                            guardrails.triggered_guardrails.forEach(function (guard) {
                                fullMessage +=
                                    '- ' +
                                    (guard.rule_name || guard.rule) +
                                    ': ' +
                                    (guard.message || 'Triggered') +
                                    '\n';
                            });
                        }
                    }

                    addMessage('assistant', fullMessage);

                    if (attachedFile || attachedFiles.length > 0) {
                        removeAttachedFile();
                    }
                } else {
                    const errorMsg = data.message || data.error || 'Terjadi kesalahan';
                    console.error('[coder-chat] API returned error:', errorMsg);
                    addMessage('assistant', '❌ Error: ' + errorMsg, true);
                }
            })
            .catch(function (error) {
                hideLoading();
                console.error('[coder-chat] Fetch error:', error);
                addMessage(
                    'assistant',
                    '❌ Error: Gagal terhubung ke server. ' +
                        (error.message || 'Unknown error'),
                    true
                );
            });
    }

    // ---------- Conversation list ----------
    function loadConversations() {
        fetch(apiBaseUrl + conversationsEndpoint, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) return;
                const conversations = data.conversations || [];
                renderConversationList(conversations);

                if (!currentConversationId && conversations.length > 0) {
                    openConversation(conversations[0].conversation_id);
                } else {
                    renderActiveConversationItem();
                }
            })
            .catch(function (err) {
                console.error('[coder-chat] Failed loading conversations', err);
            });
    }

    function renderConversationList(conversations) {
        if (!conversationList) return;
        conversationList.innerHTML = '';

        if (!conversations.length) {
            const empty = document.createElement('li');
            empty.className = 'conversation-empty';
            empty.textContent = 'Belum ada percakapan.';
            conversationList.appendChild(empty);
            return;
        }

        conversations.forEach(function (item) {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            if (item.conversation_id === currentConversationId) {
                li.classList.add('active');
            }
            li.textContent = item.title || 'Chat Tanpa Judul';
            li.title = item.title || 'Chat Tanpa Judul';
            li.dataset.id = item.conversation_id;
            li.addEventListener('click', function () {
                openConversation(item.conversation_id);
                closeSidebarIfMobile();
            });
            conversationList.appendChild(li);
        });
    }

    function openConversation(conversationId) {
        if (!conversationId) return;

        const detailUrl = apiBaseUrl + conversationDetailEndpoint.replace('{id}', encodeURIComponent(conversationId));
        fetch(detailUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) return;

                currentConversationId = data.conversation_id;
                clearChatMessages();

                const messages = data.messages || [];
                messages.forEach(function (msg) {
                    const role = msg.role === 'assistant' ? 'assistant' : 'user';
                    addMessage(role, msg.content || '');
                });

                updateActiveConversationLabel(data.title || 'Chat');
                renderActiveConversationItem();
            })
            .catch(function (err) {
                console.error('[coder-chat] Failed opening conversation', err);
            });
    }

    function clearChatMessages() {
        if (!chatMessages) return;
        const title = (chatApp && chatApp.dataset && chatApp.dataset.welcomeTitle) || defaultWelcomeTitle;
        const description = (chatApp && chatApp.dataset && chatApp.dataset.welcomeDescription) || defaultWelcomeDescription;

        chatMessages.innerHTML =
            '<div class="welcome-message">' +
            '<div class="welcome-hero">' +
            '<h2 id="welcome-title">' + escapeHtml(title) + '</h2>' +
            '<p id="welcome-description">' + escapeHtml(description) + '</p>' +
            '</div></div>';
    }

    function updateActiveConversationLabel(title) {
        if (!activeConversationLabel) return;
        activeConversationLabel.textContent = title || 'Chat Baru';
    }

    function renderActiveConversationItem() {
        if (!conversationList) return;
        Array.prototype.forEach.call(
            conversationList.querySelectorAll('.list-group-item'),
            function (item) {
                item.classList.toggle('active', item.dataset.id === currentConversationId);
            }
        );
    }

    // ---------- File handling (text-like → UTF-8, binary → Base64 envelope) ----------
    function handleFileSelect(e) {
        const files = e.target.files ? Array.prototype.slice.call(e.target.files) : [];
        if (!files.length) return;

        if (fileInput && fileInput.multiple) {
            addFiles(files);
        } else {
            handleLegacySingleFile(files[0]);
        }

        if (fileInput) {
            fileInput.value = '';
        }
    }

    function addFiles(files) {
        if (!files || !files.length) return;
        let skippedLarge = 0;
        files.forEach(function (file) {
            if (!file) return;
            if (file.size > 10 * 1024 * 1024) {
                skippedLarge++;
                return;
            }
            attachedFiles.push({
                file: file,
                id: Date.now() + '-' + Math.random().toString(16).slice(2)
            });
        });
        if (skippedLarge > 0) {
            alert('Sebagian file dilewati karena melebihi 10MB per file.');
        }
        renderAttachmentTags();
        updateSendButtonState();
        scrollToBottom();
    }

    function handleLegacySingleFile(file) {
        if (!file) return;
        if (file.size > 10 * 1024 * 1024) {
            alert('Ukuran file maksimal 10MB');
            if (fileInput) fileInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (ev) {
            attachedFile = file;
            if (isTextLikeFile(file)) {
                attachedFileContent = ev.target.result;
            } else {
                attachedFileContent = JSON.stringify({
                    encoding: 'base64-data-url',
                    mime_type: file.type || 'application/octet-stream',
                    file_name: file.name,
                    content: ev.target.result
                });
            }

            if (attachedFileName) attachedFileName.textContent = file.name;
            if (attachedFileSize) attachedFileSize.textContent = formatFileSize(file.size);
            if (fileAttachmentArea) fileAttachmentArea.classList.remove('d-none');

            updateSendButtonState();
            scrollToBottom();
        };

        reader.onerror = function () {
            alert('Gagal membaca file');
            if (fileInput) fileInput.value = '';
        };

        if (isTextLikeFile(file)) {
            reader.readAsText(file, 'UTF-8');
        } else {
            reader.readAsDataURL(file);
        }
    }

    function isTextLikeFile(file) {
        if (!file) return false;
        const mimeType = (file.type || '').toLowerCase();
        if (!mimeType) {
            const extension = '.' + (file.name.split('.').pop() || '').toLowerCase();
            const textExtensions = [
                '.txt', '.md', '.log', '.json', '.xml', '.html', '.css', '.js',
                '.php', '.py', '.java', '.c', '.cpp', '.cs', '.go', '.rb', '.rs',
                '.swift', '.kt', '.yml', '.yaml', '.ini', '.conf', '.env'
            ];
            return textExtensions.indexOf(extension) !== -1;
        }
        return (
            mimeType.indexOf('text/') === 0 ||
            mimeType.indexOf('json') !== -1 ||
            mimeType.indexOf('xml') !== -1 ||
            mimeType.indexOf('javascript') !== -1
        );
    }

    function removeAttachedFile() {
        attachedFile = null;
        attachedFileContent = null;
        attachedFiles = [];
        if (fileInput) fileInput.value = '';
        if (fileAttachmentArea) fileAttachmentArea.classList.add('d-none');
        if (fileAttachmentArea) fileAttachmentArea.innerHTML = '';
        updateSendButtonState();
    }

    function renderAttachmentTags() {
        if (!fileAttachmentArea || !fileInput || !fileInput.multiple) {
            return;
        }

        fileAttachmentArea.innerHTML = '';
        if (!attachedFiles.length) {
            fileAttachmentArea.classList.add('d-none');
            return;
        }

        attachedFiles.forEach(function (item, index) {
            const tag = document.createElement('div');
            tag.className = 'employee-ai-tag';

            const name = document.createElement('span');
            name.className = 'employee-ai-tag-name';
            name.textContent = item.file.name;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'employee-ai-tag-remove';
            remove.setAttribute('aria-label', 'Hapus lampiran');
            remove.innerHTML = '<i class="icon-times" aria-hidden="true"></i>';
            remove.addEventListener('click', function () {
                attachedFiles.splice(index, 1);
                renderAttachmentTags();
                updateSendButtonState();
            });

            tag.appendChild(name);
            tag.appendChild(remove);
            fileAttachmentArea.appendChild(tag);
        });
        fileAttachmentArea.classList.remove('d-none');
    }

    function getAvatarSvg(role) {
        if (role === 'user') {
            return (
                '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" ' +
                'stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" ' +
                'aria-hidden="true" focusable="false">' +
                '<path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"/>' +
                '<path d="M4 20a8 8 0 0 1 16 0"/>' +
                '</svg>'
            );
        }
        return (
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" ' +
            'stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" ' +
            'aria-hidden="true" focusable="false">' +
            '<path d="M12 3a7 7 0 0 1 4.9 11.9l.6 2.1-2.1-.6A7 7 0 1 1 12 3Z"/>' +
            '<circle cx="9" cy="11" r="1" fill="currentColor" stroke="none"/>' +
            '<circle cx="15" cy="11" r="1" fill="currentColor" stroke="none"/>' +
            '<path d="M9 15h6"/>' +
            '</svg>'
        );
    }

    // ---------- Message rendering ----------
    function addMessage(role, content, isError) {
        if (!chatMessages) {
            console.error('[coder-chat] chatMessages element not found');
            return;
        }

        // Drop the welcome state once a real message lands.
        const welcome = chatMessages.querySelector('.welcome-message');
        if (welcome) welcome.remove();

        const messageDiv = document.createElement('div');
        messageDiv.className = 'chat-message ' + role;

        const row = document.createElement('div');
        row.className = 'chat-message-row';

        const avatar = document.createElement('div');
        avatar.className = 'chat-message-avatar chat-message-avatar--' + role;
        avatar.setAttribute('aria-hidden', 'true');
        avatar.innerHTML = getAvatarSvg(role);

        const body = document.createElement('div');
        body.className = 'chat-message-body';

        const bubble = document.createElement('div');
        bubble.className = 'message-bubble ' + role;

        if (isError) {
            bubble.style.backgroundColor = '#fee2e2';
            bubble.style.color = '#991b1b';
            bubble.style.borderRadius = '12px';
            bubble.style.padding = '10px 14px';
        }

        if (role === 'assistant' && !isError && hasMarkdownRendering()) {
            bubble.innerHTML = formatAssistantMarkdown(content);
        } else {
            bubble.innerHTML = formatMessageContent(content);
        }

        const time = document.createElement('div');
        time.className = 'message-time';
        time.textContent = new Date().toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        });

        body.appendChild(bubble);
        body.appendChild(time);
        row.appendChild(avatar);
        row.appendChild(body);
        messageDiv.appendChild(row);
        chatMessages.appendChild(messageDiv);

        scrollToBottom();
    }

    function hasMarkdownRendering() {
        return (
            typeof marked !== 'undefined' &&
            typeof marked.parse === 'function' &&
            typeof DOMPurify !== 'undefined' &&
            typeof DOMPurify.sanitize === 'function'
        );
    }

    function configureMarkdownPipeline() {
        if (typeof marked === 'undefined' || typeof marked.setOptions !== 'function') {
            return;
        }
        try {
            marked.setOptions({
                gfm: true,
                breaks: true,
                headerIds: false,
                mangle: false,
            });
        } catch (e) {
            console.warn('[coder-chat] marked.setOptions failed', e);
        }
    }

    /**
     * Wrap tables with Bootstrap table-responsive + table classes after sanitize.
     */
    function postProcessAssistantHtml(html) {
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        var tables = Array.prototype.slice.call(wrapper.querySelectorAll('table'));
        for (var i = 0; i < tables.length; i++) {
            var t = tables[i];
            var parent = t.parentNode;
            if (!parent) {
                continue;
            }
            if (parent.classList && parent.classList.contains('table-responsive')) {
                continue;
            }
            t.classList.add(
                'table',
                'table-sm',
                'table-bordered',
                'table-striped',
                'mb-0',
                'chat-md-table'
            );
            var wrap = document.createElement('div');
            wrap.className = 'table-responsive chat-md-table-wrap mb-3';
            parent.insertBefore(wrap, t);
            wrap.appendChild(t);
        }
        var links = Array.prototype.slice.call(wrapper.querySelectorAll('a[href]'));
        for (var j = 0; j < links.length; j++) {
            var a = links[j];
            var href = a.getAttribute('href') || '';
            if (href.indexOf('http:') === 0 || href.indexOf('https:') === 0 || href.indexOf('//') === 0) {
                a.setAttribute('target', '_blank');
                a.setAttribute('rel', 'noopener noreferrer');
            }
        }
        return wrapper.innerHTML;
    }

    function formatAssistantMarkdown(content) {
        var raw = content == null ? '' : String(content);
        if (!hasMarkdownRendering()) {
            return formatMessageContent(content);
        }
        try {
            var html = marked.parse(raw);
            html = DOMPurify.sanitize(html, {
                USE_PROFILES: { html: true },
                ADD_ATTR: ['target', 'rel'],
            });
            html = postProcessAssistantHtml(html);
            return '<div class="chat-md-content">' + html + '</div>';
        } catch (e) {
            console.warn('[coder-chat] Markdown render failed, falling back to plain text.', e);
            return formatMessageContent(content);
        }
    }

    function formatMessageContent(content) {
        let escaped = escapeHtml(content);

        escaped = escaped.replace(/```(\w+)?\n([\s\S]*?)```/g, function (m, lang, code) {
            return (
                '<div class="code-block"><strong>' +
                (lang || 'code') +
                '</strong><pre>' +
                escapeHtml(code) +
                '</pre></div>'
            );
        });

        escaped = escaped.replace(/`([^`]+)`/g, '<code>$1</code>');
        escaped = escaped.replace(/\n/g, '<br>');
        return escaped;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }

    function scrollToBottom() {
        if (!chatMessages) return;
        setTimeout(function () {
            if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
        }, 80);
    }

    // ---------- Loading indicator (inline, above composer) ----------
    function showLoading() {
        if (loadingOverlay) loadingOverlay.classList.remove('d-none');
        if (sendBtn) sendBtn.disabled = true;
    }

    function hideLoading() {
        if (loadingOverlay) loadingOverlay.classList.add('d-none');
        // Recompute based on current input state instead of blindly enabling.
        updateSendButtonState();
    }
})();
