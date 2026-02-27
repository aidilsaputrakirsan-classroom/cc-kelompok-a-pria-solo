{{-- Floating Calculator Widget --}}
<div id="calculatorWidget" class="calculator-widget {{ isset($hiddenByDefault) && $hiddenByDefault ? 'hidden' : '' }}">
    {{-- Drag Handle Header --}}
    <div class="calculator-header" id="calculatorDragHandle">
        <div class="calculator-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"></path>
                <path d="M7 11h10M12 7v10"></path>
            </svg>
            <span>Kalkulator</span>
        </div>
        <button class="calculator-close-btn" id="closeCalculatorBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    {{-- Display Screen --}}
    <div class="calculator-display">
        <input 
            type="text" 
            id="calculatorDisplay" 
            class="calculator-input"
            readonly
            value="0"
            placeholder="0"
        >
        <div class="calculator-history" id="calculatorHistory">0</div>
    </div>

    {{-- Button Grid --}}
    <div class="calculator-buttons">
        {{-- Row 1: Clear & Operations --}}
        <button class="calc-btn calc-btn-function" data-action="clear">C</button>
        <button class="calc-btn calc-btn-function" data-action="delete">DEL</button>
        <button class="calc-btn calc-btn-operator" data-operator="/">÷</button>
        <button class="calc-btn calc-btn-operator" data-operator="*">×</button>

        {{-- Row 2: Numbers 7-9 & Minus --}}
        <button class="calc-btn calc-btn-number" data-number="7">7</button>
        <button class="calc-btn calc-btn-number" data-number="8">8</button>
        <button class="calc-btn calc-btn-number" data-number="9">9</button>
        <button class="calc-btn calc-btn-operator" data-operator="-">−</button>

        {{-- Row 3: Numbers 4-6 & Plus --}}
        <button class="calc-btn calc-btn-number" data-number="4">4</button>
        <button class="calc-btn calc-btn-number" data-number="5">5</button>
        <button class="calc-btn calc-btn-number" data-number="6">6</button>
        <button class="calc-btn calc-btn-operator" data-operator="+">+</button>

        {{-- Row 4: Numbers 1-3 & Equals --}}
        <button class="calc-btn calc-btn-number" data-number="1">1</button>
        <button class="calc-btn calc-btn-number" data-number="2">2</button>
        <button class="calc-btn calc-btn-number" data-number="3">3</button>
        <button class="calc-btn calc-btn-equals" data-action="equals">=</button>

        {{-- Row 5: Zero, Decimal & Extra --}}
        <button class="calc-btn calc-btn-number calc-btn-zero" data-number="0">0</button>
        <button class="calc-btn calc-btn-number" data-number=".">.</button>
        <button class="calc-btn calc-btn-function" data-action="percent">%</button>
    </div>
</div>

{{-- FAB Button to Open Calculator --}}
<button id="fabCalculator" class="fab-calculator {{ !isset($hiddenByDefault) || !$hiddenByDefault ? 'hidden' : '' }}">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"></path>
        <path d="M7 11h10M12 7v10"></path>
    </svg>
    <span class="fab-text">Kalkulator</span>
</button>

<style>
    /* ========================================
       FLOATING CALCULATOR WIDGET
       ======================================== */

    .calculator-widget {
        position: fixed;
        bottom: 30px;
        right: 360px;
        width: 280px;
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 1px rgba(0, 0, 0, 0.1);
        z-index: 1100;
        display: flex !important;
        flex-direction: column;
        cursor: grab;
        user-select: none;
        transform: translateZ(0);
        transition: box-shadow 0.3s ease;
        border: 1px solid #e5e7eb;
        max-height: 90vh;
        overflow: hidden;
    }

    .calculator-widget:hover {
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.2), 0 0 1px rgba(0, 0, 0, 0.1);
    }

    .calculator-widget.dragging {
        cursor: grabbing;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.25);
        transition: none;
    }

    .calculator-widget.hidden {
        display: none !important;
    }

    /* ========================================
       HEADER & DRAG HANDLE
       ======================================== */

    .calculator-header {
        padding: 12px 16px;
        background: linear-gradient(135deg, #1d1d1f 0%, #1a1a1a 100%);
        border-radius: 16px 16px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: grab;
        user-select: none;
        flex-shrink: 0;
    }

    .calculator-header:active {
        cursor: grabbing;
    }

    .calculator-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: white;
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: -0.3px;
    }

    .calculator-title svg {
        flex-shrink: 0;
        width: 18px;
        height: 18px;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    }

    .calculator-close-btn {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        border-radius: 8px;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        color: white;
        flex-shrink: 0;
        padding: 0;
    }

    .calculator-close-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: rotate(90deg);
    }

    .calculator-close-btn:active {
        transform: rotate(90deg) scale(0.95);
    }

    .calculator-close-btn svg {
        width: 16px;
        height: 16px;
    }

    /* ========================================
       DISPLAY SCREEN
       ======================================== */

    .calculator-display {
        padding: 14px;
        background: #f6f6f6;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex-shrink: 0;
    }

    .calculator-input {
        width: 100%;
        padding: 12px;
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
        text-align: right;
        font-family: 'Monaco', 'Courier New', monospace;
        letter-spacing: 1px;
        transition: all 0.2s ease;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .calculator-input:focus {
        outline: none;
        border-color: #1d1d1f;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02), 0 0 0 3px rgba(29, 29, 31, 0.1);
    }

    .calculator-history {
        font-size: 0.7rem;
        color: #9ca3af;
        text-align: right;
        font-family: 'Monaco', 'Courier New', monospace;
        min-height: 14px;
        padding: 0 4px;
    }

    /* ========================================
       BUTTON GRID
       ======================================== */

    .calculator-buttons {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 9px;
        padding: 14px;
        background: #f6f6f6;
        flex: 1;
        overflow-y: auto;
    }

    .calc-btn {
        aspect-ratio: 1;
        border: none;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        user-select: none;
        position: relative;
        overflow: hidden;
        min-height: 32px;
    }

    .calc-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.4s, height 0.4s;
    }

    .calc-btn:active::before {
        width: 100%;
        height: 100%;
    }

    /* Number Buttons */
    .calc-btn-number {
        background: white;
        color: #111827;
        border: 1px solid #e5e7eb;
    }

    .calc-btn-number:hover {
        background: #f9fafb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .calc-btn-number:active {
        transform: translateY(0);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    }

    /* Operator Buttons */
    .calc-btn-operator {
        background: linear-gradient(135deg, #1d1d1f 0%, #1a1a1a 100%);
        color: white;
        border: none;
        font-weight: 800;
    }

    .calc-btn-operator:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(29, 29, 31, 0.4);
    }

    .calc-btn-operator:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(29, 29, 31, 0.3);
    }

    /* Function Buttons */
    .calc-btn-function {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        color: white;
        border: none;
        font-size: 0.85rem;
    }

    .calc-btn-function:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(75, 85, 99, 0.4);
    }

    .calc-btn-function:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(75, 85, 99, 0.3);
    }

    /* Equals Button */
    .calc-btn-equals {
        background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
        color: white;
        border: none;
        font-weight: 800;
    }

    .calc-btn-equals:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(31, 41, 55, 0.4);
    }

    .calc-btn-equals:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(31, 41, 55, 0.3);
    }

    /* Zero Button (Span 2 columns) */
    .calc-btn-zero {
        grid-column: span 2;
    }

    /* ========================================
       FAB CALCULATOR BUTTON
       ======================================== */

    .fab-calculator {
        position: fixed;
        bottom: 100px;
        right: 30px;
        background: #1a1a1a;
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1090;
        letter-spacing: 0.3px;
        user-select: none;
    }

    .fab-calculator:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(0, 0, 0, 0.12);
        background: #2a2a2a;
    }

    .fab-calculator:active {
        transform: translateY(0);
    }

    .fab-calculator svg {
        flex-shrink: 0;
        width: 18px;
        height: 18px;
    }

    .fab-calculator.hidden {
        display: none;
    }

    /* ========================================
       RESPONSIVE DESIGN
       ======================================== */

    /* Tablet (768px and below) */
    @media (max-width: 1024px) {
        .calculator-widget {
            width: 260px;
            right: 320px;
        }

        .calculator-buttons {
            gap: 8px;
            padding: 12px;
        }

        .calc-btn {
            font-size: 0.85rem;
        }
    }

    /* Small tablet & large phone (768px and below) */
    @media (max-width: 768px) {
        .calculator-widget {
            width: 240px;
            right: auto;
            bottom: auto;
            left: 10px;
            top: 10px;
            max-height: 85vh;
        }

        .calculator-header {
            padding: 10px 12px;
        }

        .calculator-title {
            font-size: 0.9rem;
            gap: 6px;
        }

        .calculator-display {
            padding: 12px;
            gap: 5px;
        }

        .calculator-input {
            font-size: 1.1rem;
            padding: 10px;
        }

        .calculator-history {
            font-size: 0.65rem;
        }

        .calculator-buttons {
            gap: 8px;
            padding: 12px;
        }

        .calc-btn {
            font-size: 0.8rem;
            min-height: 30px;
        }

        .calculator-footer {
            padding: 8px 12px;
        }

        .calc-memory-btn {
            font-size: 0.7rem;
            padding: 4px 8px;
        }

        .fab-calculator {
            bottom: 90px;
            right: 20px;
            padding: 10px 16px;
            font-size: 0.8rem;
        }
    }

    /* Small phone (480px and below) */
    @media (max-width: 480px) {
        .calculator-widget {
            width: 220px;
            left: 8px;
            top: 8px;
        }

        .calculator-header {
            padding: 9px 10px;
        }

        .calculator-title {
            font-size: 0.85rem;
            gap: 5px;
        }

        .calculator-title svg {
            width: 16px;
            height: 16px;
        }

        .calculator-close-btn {
            width: 26px;
            height: 26px;
        }

        .calculator-display {
            padding: 10px;
            gap: 4px;
        }

        .calculator-input {
            font-size: 1rem;
            padding: 8px;
        }

        .calculator-history {
            font-size: 0.6rem;
        }

        .calculator-buttons {
            gap: 7px;
            padding: 10px;
        }

        .calc-btn {
            font-size: 0.75rem;
            min-height: 28px;
        }

        .calculator-footer {
            padding: 6px 10px;
        }

        .calc-memory-btn {
            font-size: 0.65rem;
            padding: 3px 6px;
        }

        .fab-calculator {
            bottom: 15px;
            right: 15px;
            padding: 8px 14px;
            font-size: 0.75rem;
        }

        .fab-text {
            display: none;
        }
    }

    /* Extra small phone (320px) */
    @media (max-width: 360px) {
        .calculator-widget {
            width: 200px;
        }

        .calculator-input {
            font-size: 0.95rem;
        }

        .calc-btn {
            font-size: 0.7rem;
            min-height: 26px;
        }
    }

    /* ========================================
       ANIMATIONS
       ======================================== */

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .calculator-widget {
        animation: slideInUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    .calc-btn.pressed {
        animation: pulse 0.2s ease;
    }
</style>

<script>
    // ========================================
    // CALCULATOR LOGIC
    // ========================================

    class Calculator {
        constructor() {
            this.display = document.getElementById('calculatorDisplay');
            this.historyDisplay = document.getElementById('calculatorHistory');
            this.widget = document.getElementById('calculatorWidget');
            this.fabBtn = document.getElementById('fabCalculator');
            this.closeBtn = document.getElementById('closeCalculatorBtn');
            this.dragHandle = document.getElementById('calculatorDragHandle');

            this.currentValue = '0';
            this.previousValue = '';
            this.operator = null;
            this.shouldResetDisplay = false;
            this.isBeingDragged = false;
            this.offsetX = 0;
            this.offsetY = 0;

            this.initEventListeners();
            this.initDragFunctionality();
            this.loadState();
        }

        initEventListeners() {
            // Number buttons
            document.querySelectorAll('.calc-btn-number').forEach(btn => {
                btn.addEventListener('click', (e) => this.handleNumber(e.target.closest('.calc-btn-number').dataset.number));
            });

            // Operator buttons
            document.querySelectorAll('.calc-btn-operator').forEach(btn => {
                btn.addEventListener('click', (e) => this.handleOperator(e.target.closest('.calc-btn-operator').dataset.operator));
            });

            // Function buttons
            document.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const action = e.target.closest('[data-action]').dataset.action;
                    switch(action) {
                        case 'clear': this.clear(); break;
                        case 'delete': this.delete(); break;
                        case 'equals': this.equals(); break;
                        case 'percent': this.percent(); break;
                    }
                });
            });

            // FAB button - toggle widget visibility
            this.fabBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleWidget();
            });

            // Close button
            this.closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.hideWidget();
            });

            // Keyboard support
            document.addEventListener('keydown', (e) => this.handleKeyboard(e));
        }

        initDragFunctionality() {
            this.dragHandle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.startDrag(e.clientX, e.clientY);
            });

            this.dragHandle.addEventListener('touchstart', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const touch = e.touches[0];
                this.startDrag(touch.clientX, touch.clientY);
            });

            document.addEventListener('mousemove', (e) => this.moveDrag(e.clientX, e.clientY));
            document.addEventListener('touchmove', (e) => {
                if (this.isBeingDragged && e.touches.length > 0) {
                    this.moveDrag(e.touches[0].clientX, e.touches[0].clientY);
                }
            });

            document.addEventListener('mouseup', () => this.endDrag());
            document.addEventListener('touchend', () => this.endDrag());
        }

        startDrag(clientX, clientY) {
            this.isBeingDragged = true;
            const rect = this.widget.getBoundingClientRect();
            this.offsetX = clientX - rect.left;
            this.offsetY = clientY - rect.top;
            this.widget.classList.add('dragging');
            this.dragHandle.style.cursor = 'grabbing';
        }

        moveDrag(clientX, clientY) {
            if (!this.isBeingDragged) return;

            const newX = clientX - this.offsetX;
            const newY = clientY - this.offsetY;

            // Constrain to viewport
            const maxX = window.innerWidth - this.widget.offsetWidth;
            const maxY = window.innerHeight - this.widget.offsetHeight;

            this.widget.style.position = 'fixed';
            this.widget.style.left = Math.max(0, Math.min(newX, maxX)) + 'px';
            this.widget.style.top = Math.max(0, Math.min(newY, maxY)) + 'px';
            this.widget.style.right = 'auto';
            this.widget.style.bottom = 'auto';
        }

        endDrag() {
            if (this.isBeingDragged) {
                this.isBeingDragged = false;
                this.widget.classList.remove('dragging');
                this.dragHandle.style.cursor = 'grab';
                this.saveState();
            }
        }

        handleNumber(num) {
            if (this.shouldResetDisplay) {
                this.currentValue = '';
                this.shouldResetDisplay = false;
            }

            if (num === '.' && this.currentValue.includes('.')) return;
            if (this.currentValue === '0' && num !== '.') this.currentValue = '';

            this.currentValue += num;
            this.updateDisplay();
        }

        handleOperator(op) {
            if (this.operator !== null && !this.shouldResetDisplay) {
                this.equals();
            }

            this.previousValue = this.currentValue;
            this.operator = op;
            this.shouldResetDisplay = true;
            this.historyDisplay.textContent = `${this.previousValue} ${op}`;
        }

        equals() {
            if (this.operator === null || this.shouldResetDisplay) return;

            const prev = parseFloat(this.previousValue);
            const current = parseFloat(this.currentValue);
            let result;

            switch(this.operator) {
                case '+': result = prev + current; break;
                case '-': result = prev - current; break;
                case '*': result = prev * current; break;
                case '/': result = current !== 0 ? prev / current : 0; break;
                default: return;
            }

            this.historyDisplay.textContent = `${this.previousValue} ${this.operator} ${this.currentValue} =`;
            this.currentValue = Math.round(result * 100000000) / 100000000;
            this.operator = null;
            this.shouldResetDisplay = true;
            this.updateDisplay();
        }

        percent() {
            const value = parseFloat(this.currentValue);
            this.currentValue = (value / 100).toString();
            this.updateDisplay();
        }

        clear() {
            this.currentValue = '0';
            this.previousValue = '';
            this.operator = null;
            this.historyDisplay.textContent = '0';
            this.updateDisplay();
        }

        delete() {
            if (this.currentValue === '0') return;
            this.currentValue = this.currentValue.slice(0, -1) || '0';
            this.updateDisplay();
        }

        handleKeyboard(e) {
            const widgetDisplay = window.getComputedStyle(this.widget).display;
            if (widgetDisplay === 'none') return;

            const key = e.key;

            if (/\d/.test(key)) this.handleNumber(key);
            else if (['+', '-', '*', '/'].includes(key)) this.handleOperator(key === '*' ? '*' : key === '/' ? '/' : key);
            else if (key === '.') this.handleNumber('.');
            else if (key === 'Enter') this.equals();
            else if (key === 'Backspace') this.delete();
            else if (key.toLowerCase() === 'c') this.clear();
        }

        updateDisplay() {
            const displayValue = this.currentValue === '0' ? '0' : this.currentValue;
            this.display.value = displayValue.length > 12 ? parseFloat(displayValue).toExponential(6) : displayValue;
        }

        toggleWidget() {
            const isHidden = this.widget.classList.contains('hidden');
            if (isHidden) {
                this.showWidget();
            } else {
                this.hideWidget();
            }
        }

        showWidget() {
            this.widget.classList.remove('hidden');
            this.fabBtn.classList.add('hidden');
        }

        hideWidget() {
            this.widget.classList.add('hidden');
            this.fabBtn.classList.remove('hidden');
        }

        saveState() {
            const state = {
                currentValue: this.currentValue,
                previousValue: this.previousValue,
                operator: this.operator,
                position: {
                    left: this.widget.style.left,
                    top: this.widget.style.top
                }
            };
            localStorage.setItem('calculatorState', JSON.stringify(state));
        }

        loadState() {
            const saved = localStorage.getItem('calculatorState');
            if (saved) {
                try {
                    const state = JSON.parse(saved);
                    this.currentValue = state.currentValue;
                    this.previousValue = state.previousValue;
                    this.operator = state.operator;

                    if (state.position.left) this.widget.style.left = state.position.left;
                    if (state.position.top) this.widget.style.top = state.position.top;

                    this.updateDisplay();
                } catch (e) {
                    console.error('Failed to load calculator state:', e);
                }
            }
        }
    }

    // Initialize calculator when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.calculator = new Calculator();
        });
    } else {
        window.calculator = new Calculator();
    }
</script>
