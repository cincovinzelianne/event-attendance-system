/**
 * Google Calendar Style Date/Time Picker
 * Mimics Google Calendar's date and time selection UI
 */

class GoogleCalendarPicker {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            showTime: true,
            showDate: true,
            minDate: new Date(),
            maxDate: null,
            defaultDate: new Date(),
            timeFormat: '24h',
            ...options
        };
        
        this.selectedDate = this.options.defaultDate;
        this.selectedTime = this.getCurrentTime();
        this.isOpen = false;
        
        this.init();
    }
    
    init() {
        this.createPicker();
        this.bindEvents();
    }
    
    createPicker() {
        this.container.innerHTML = `
            <div class="google-calendar-picker">
                <div class="picker-input" id="picker-input-${this.container.id}">
                    <div class="input-content">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="date-text">${this.formatDate(this.selectedDate)}</span>
                        ${this.options.showTime ? `<span class="time-text">${this.formatTime(this.selectedTime)}</span>` : ''}
                    </div>
                    <div class="input-arrow">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </div>
        `;
        
        // Create modal backdrop
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'picker-backdrop';
        this.backdrop.id = `picker-backdrop-${this.container.id}`;
        document.body.appendChild(this.backdrop);
        
        // Create modal dropdown
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'picker-dropdown';
        this.dropdown.id = `picker-dropdown-${this.container.id}`;
        this.dropdown.innerHTML = `
            <div class="picker-header">
                <button class="nav-btn prev-month" type="button">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="current-month">
                    <span class="month-year">${this.formatMonthYear(this.selectedDate)}</span>
                </div>
                <button class="nav-btn next-month" type="button">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="close-btn" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="picker-body">
                <div class="weekdays">
                    <div class="weekday">S</div>
                    <div class="weekday">M</div>
                    <div class="weekday">T</div>
                    <div class="weekday">W</div>
                    <div class="weekday">T</div>
                    <div class="weekday">F</div>
                    <div class="weekday">S</div>
                </div>
                <div class="calendar-grid" id="calendar-grid-${this.container.id}">
                    <!-- Calendar days will be generated here -->
                </div>
            </div>
            
            ${this.options.showTime ? `
            <div class="picker-footer">
                <div class="time-section">
                    <label>Time:</label>
                    <div class="time-inputs">
                        <input type="number" class="time-input hours" min="0" max="23" value="${this.selectedTime.hours}">
                        <span class="time-separator">:</span>
                        <input type="number" class="time-input minutes" min="0" max="59" value="${this.selectedTime.minutes}">
                    </div>
                </div>
                <div class="picker-actions">
                    <button type="button" class="btn-cancel">Cancel</button>
                    <button type="button" class="btn-ok">OK</button>
                </div>
            </div>
            ` : ''}
        `;
        document.body.appendChild(this.dropdown);
        
        this.generateCalendar();
    }
    
    generateCalendar() {
        const grid = document.getElementById(`calendar-grid-${this.container.id}`);
        if (!grid) {
            console.error('Calendar grid not found');
            return;
        }
        
        const year = this.selectedDate.getFullYear();
        const month = this.selectedDate.getMonth();
        
        console.log('Generating calendar for:', year, month);
        
        // Get first day of month and number of days
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDay = firstDay.getDay();
        
        // Get last day of previous month for padding
        const prevMonthLastDay = new Date(year, month, 0).getDate();
        
        grid.innerHTML = '';
        
        // Add days from previous month to fill the first week
        for (let i = startingDay - 1; i >= 0; i--) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day other-month';
            dayElement.textContent = (prevMonthLastDay - i).toString();
            dayElement.style.color = '#dadce0';
            grid.appendChild(dayElement);
        }
        
        // Add days of the current month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.textContent = day.toString();
            dayElement.dataset.day = day;
            dayElement.dataset.year = year;
            dayElement.dataset.month = month;
            
            // Check if this day is selected
            const currentDate = new Date(year, month, day);
            if (this.isSameDate(currentDate, this.selectedDate)) {
                dayElement.classList.add('selected');
            }
            
            // Check if this day is today
            if (this.isSameDate(currentDate, new Date())) {
                dayElement.classList.add('today');
            }
            
            // Check if this day is disabled
            if (this.isDateDisabled(currentDate)) {
                dayElement.classList.add('disabled');
            }
            
            grid.appendChild(dayElement);
        }
        
        // Add days from next month to fill the last week
        const totalCells = grid.children.length;
        const remainingCells = 42 - totalCells; // 6 weeks * 7 days = 42 cells
        
        for (let day = 1; day <= remainingCells; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day other-month';
            dayElement.textContent = day.toString();
            dayElement.style.color = '#dadce0';
            grid.appendChild(dayElement);
        }
        
        console.log('Calendar generated with', grid.children.length, 'cells');
    }
    
    bindEvents() {
        const input = document.getElementById(`picker-input-${this.container.id}`);
        const grid = document.getElementById(`calendar-grid-${this.container.id}`);
        
        // Toggle modal
        input.addEventListener('click', (e) => {
            e.stopPropagation();
            this.openModal();
        });
        
        // Close modal when clicking backdrop
        this.backdrop.addEventListener('click', () => {
            this.closeModal();
        });
        
        // Close modal when clicking close button
        const closeBtn = this.dropdown.querySelector('.close-btn');
        closeBtn.addEventListener('click', () => {
            this.closeModal();
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeModal();
            }
        });
        
        // Navigation buttons
        const prevBtn = this.dropdown.querySelector('.prev-month');
        const nextBtn = this.dropdown.querySelector('.next-month');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.selectedDate.setMonth(this.selectedDate.getMonth() - 1);
                this.generateCalendar();
                this.updateMonthYear();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.selectedDate.setMonth(this.selectedDate.getMonth() + 1);
                this.generateCalendar();
                this.updateMonthYear();
            });
        }
        
        // Calendar day clicks
        grid.addEventListener('click', (e) => {
            if (e.target.classList.contains('calendar-day') && 
                !e.target.classList.contains('disabled') && 
                !e.target.classList.contains('other-month')) {
                const day = parseInt(e.target.dataset.day);
                const year = parseInt(e.target.dataset.year);
                const month = parseInt(e.target.dataset.month);
                const newDate = new Date(year, month, day);
                this.selectDate(newDate);
            }
        });
        
        // Time inputs
        if (this.options.showTime) {
            const hoursInput = this.dropdown.querySelector('.hours');
            const minutesInput = this.dropdown.querySelector('.minutes');
            
            hoursInput.addEventListener('change', (e) => {
                this.selectedTime.hours = Math.max(0, Math.min(23, parseInt(e.target.value) || 0));
                e.target.value = this.selectedTime.hours;
            });
            
            minutesInput.addEventListener('change', (e) => {
                this.selectedTime.minutes = Math.max(0, Math.min(59, parseInt(e.target.value) || 0));
                e.target.value = this.selectedTime.minutes;
            });
        }
        
        // Action buttons
        const cancelBtn = this.dropdown.querySelector('.btn-cancel');
        const okBtn = this.dropdown.querySelector('.btn-ok');
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.closeModal();
            });
        }
        
        if (okBtn) {
            okBtn.addEventListener('click', () => {
                this.confirmSelection();
            });
        }
    }
    
    openModal() {
        this.isOpen = true;
        this.backdrop.classList.add('show');
        this.dropdown.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
        
        // Regenerate calendar when opening modal
        this.generateCalendar();
        this.updateMonthYear();
    }
    
    closeModal() {
        this.isOpen = false;
        this.backdrop.classList.remove('show');
        this.dropdown.classList.remove('show');
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    selectDate(date) {
        this.selectedDate = new Date(date);
        this.generateCalendar();
        this.updateInput();
    }
    
    confirmSelection() {
        this.updateInput();
        this.closeModal();
        this.triggerChange();
    }
    
    updateInput() {
        const dateText = this.container.querySelector('.date-text');
        const timeText = this.container.querySelector('.time-text');
        
        if (dateText) {
            dateText.textContent = this.formatDate(this.selectedDate);
        }
        
        if (timeText) {
            timeText.textContent = this.formatTime(this.selectedTime);
        }
    }
    
    updateMonthYear() {
        const monthYear = this.container.querySelector('.month-year');
        if (monthYear) {
            monthYear.textContent = this.formatMonthYear(this.selectedDate);
        }
    }
    
    triggerChange() {
        const event = new CustomEvent('dateTimeChange', {
            detail: {
                date: this.selectedDate,
                time: this.selectedTime,
                formatted: this.getFormattedValue()
            }
        });
        this.container.dispatchEvent(event);
    }
    
    getFormattedValue() {
        const dateStr = this.selectedDate.toISOString().split('T')[0];
        const timeStr = `${this.selectedTime.hours.toString().padStart(2, '0')}:${this.selectedTime.minutes.toString().padStart(2, '0')}`;
        return {
            date: dateStr,
            time: timeStr,
            datetime: `${dateStr}T${timeStr}:00`
        };
    }
    
    // Utility methods
    formatDate(date) {
        return date.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
    
    formatTime(time) {
        if (this.options.timeFormat === '12h') {
            const hours = time.hours === 0 ? 12 : time.hours > 12 ? time.hours - 12 : time.hours;
            const ampm = time.hours >= 12 ? 'PM' : 'AM';
            return `${hours}:${time.minutes.toString().padStart(2, '0')} ${ampm}`;
        }
        return `${time.hours.toString().padStart(2, '0')}:${time.minutes.toString().padStart(2, '0')}`;
    }
    
    formatMonthYear(date) {
        return date.toLocaleDateString('en-US', {
            month: 'long',
            year: 'numeric'
        });
    }
    
    getCurrentTime() {
        const now = new Date();
        return {
            hours: now.getHours(),
            minutes: now.getMinutes()
        };
    }
    
    isSameDate(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getDate() === date2.getDate();
    }
    
    isDateDisabled(date) {
        if (this.options.minDate && date < this.options.minDate) return true;
        if (this.options.maxDate && date > this.options.maxDate) return true;
        return false;
    }
    
    // Cleanup method to remove modal elements
    destroy() {
        if (this.backdrop && this.backdrop.parentNode) {
            this.backdrop.parentNode.removeChild(this.backdrop);
        }
        if (this.dropdown && this.dropdown.parentNode) {
            this.dropdown.parentNode.removeChild(this.dropdown);
        }
        document.body.style.overflow = '';
    }
}

// Auto-initialize pickers with data-google-calendar-picker attribute
document.addEventListener('DOMContentLoaded', function() {
    const pickerElements = document.querySelectorAll('[data-google-calendar-picker]');
    pickerElements.forEach(element => {
        new GoogleCalendarPicker(element.id);
    });
});
