<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container-fluid coder-chat-container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-code"></i> AI Coder Assistant
                    </h3>
                </div>
                <div class="card-body p-0">
                    <!-- Chat Messages Area -->
                    <div id="chat-messages" class="chat-messages">
                        <div class="welcome-message">
                            <div class="alert alert-info mb-0">
                                <i class="icon-info-circle"></i> 
                                Selamat datang! Saya adalah AI Assistant yang siap membantu Anda dengan coding. 
                                Anda dapat mengirim pesan atau meng-attach file teks untuk dianalisis.
                            </div>
                        </div>
                    </div>

                    <!-- File Attachment Area -->
                    <div id="file-attachment-area" class="file-attachment-area d-none">
                        <div class="card border-primary">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="icon-file-alt text-primary"></i>
                                        <span id="attached-file-name" class="ml-2"></span>
                                        <small class="text-muted ml-2" id="attached-file-size"></small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="remove-file-btn">
                                        <i class="icon-times"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="chat-input-area">
                        <form id="chat-form" class="chat-form">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <button type="button" class="btn btn-outline-secondary" id="attach-file-btn" title="Attach File" style="cursor: pointer;">
                                        <i class="icon-paperclip" aria-hidden="true"></i>
                                        <span class="icon-fallback" style="display: none;">📎</span>
                                    </button>
                                    <input type="file" id="file-input" class="d-none" accept=".txt,.text,.log,.md,.json,.xml,.html,.css,.js,.php,.py,.java,.cpp,.c,.cs,.go,.rs,.swift,.kt,.rb,.sh,.bat,.yml,.yaml,.ini,.conf,.env">
                                </div>
                                <textarea 
                                    id="message-input" 
                                    class="form-control" 
                                    rows="2" 
                                    placeholder="Ketik pesan Anda di sini... (Enter untuk kirim, Shift+Enter untuk baris baru)"
                                    required
                                    autocomplete="off"></textarea>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary" id="send-btn">
                                        <i class="icon-paper-plane"></i> Kirim
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="loading-overlay d-none">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
    </div>
    <p class="mt-2">Mengirim pesan ke AI...</p>
</div>

<style>
.coder-chat-container {
    height: calc(100vh - 200px);
    display: flex;
    flex-direction: column;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background-color: #f8f9fa;
    min-height: 400px;
    max-height: calc(100vh - 350px);
}

.chat-message {
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
}

.chat-message.user {
    align-items: flex-end;
}

.chat-message.assistant {
    align-items: flex-start;
}

.message-bubble {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 18px;
    word-wrap: break-word;
    white-space: pre-wrap;
}

.message-bubble.user {
    background-color: #007bff;
    color: white;
    border-bottom-right-radius: 4px;
}

.message-bubble.assistant {
    background-color: white;
    color: #333;
    border: 1px solid #dee2e6;
    border-bottom-left-radius: 4px;
}

.message-time {
    font-size: 11px;
    color: #6c757d;
    margin-top: 4px;
    padding: 0 4px;
}

.file-attachment-area {
    padding: 10px 20px;
    background-color: #fff;
    border-top: 1px solid #dee2e6;
}

.chat-input-area {
    padding: 15px 20px;
    background-color: #fff;
    border-top: 1px solid #dee2e6;
}

.chat-form textarea {
    resize: none;
    border: none;
    box-shadow: none;
}

.chat-form textarea:focus {
    box-shadow: none;
    border: none;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    color: white;
}

.welcome-message {
    margin-bottom: 20px;
}

.code-block {
    background-color: #f4f4f4;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    margin: 10px 0;
    overflow-x: auto;
    font-family: 'Courier New', monospace;
    font-size: 13px;
}

.message-bubble pre {
    background-color: transparent;
    border: none;
    padding: 0;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>
