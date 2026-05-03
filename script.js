// Initialize on Page Load
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to today
    const dateInput = document.getElementById('date');
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);
    dateInput.value = today;

    // Set default filter date
    const filterDate = document.getElementById('filterDate');
    filterDate.value = today;

    // Load initial slots
    loadSlots();

    // Event listeners
    document.getElementById('bookingForm').addEventListener('submit', submitBooking);
    document.getElementById('filterTurf').addEventListener('change', loadSlots);
    document.getElementById('filterDate').addEventListener('change', loadSlots);
});

// ==================== VALIDATION FUNCTIONS ====================

/**
 * Validate full name
 */
function validateName(name) {
    const nameRegex = /^[a-zA-Z\s]{3,}$/;
    return nameRegex.test(name.trim());
}

/**
 * Validate mobile number (10 digits only)
 */
function validateMobile(mobile) {
    const mobileRegex = /^[0-9]{10}$/;
    return mobileRegex.test(mobile.trim());
}

/**
 * Validate email format
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email.trim());
}

/**
 * Validate time format (HH:MM)
 */
function validateTime(time) {
    const timeRegex = /^([0-1][0-9]|2[0-3]):[0-5][0-9]$/;
    return timeRegex.test(time);
}

/**
 * Validate date (must be today or future)
 */
function validateDate(dateStr) {
    const selectedDate = new Date(dateStr);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return selectedDate >= today;
}

/**
 * Validate time range (start < end)
 */
function validateTimeRange(startTime, endTime) {
    const start = parseInt(startTime.replace(':', ''));
    const end = parseInt(endTime.replace(':', ''));
    return start < end;
}

/**
 * Clear all error messages
 */
function clearErrors() {
    document.querySelectorAll('.error-msg').forEach(el => {
        el.textContent = '';
    });
}

/**
 * Show error message for a field
 */
function showError(fieldId, message) {
    const errorElement = document.getElementById(fieldId + 'Error');
    if (errorElement) {
        errorElement.textContent = message;
    }
}

// ==================== BOOKING FORM SUBMISSION ====================

/**
 * Handle booking form submission
 */
function submitBooking(e) {
    e.preventDefault();

    // Clear previous errors
    clearErrors();

    // Get form values
    const name = document.getElementById('name').value;
    const mobile = document.getElementById('mobile').value;
    const email = document.getElementById('email').value;
    const turfName = document.getElementById('turfName').value;
    const date = document.getElementById('date').value;
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;

    // Validation flags
    let isValid = true;

    // Validate Name
    if (!name || !validateName(name)) {
        showError('name', 'Name must be at least 3 characters (letters only)');
        isValid = false;
    }

    // Validate Mobile
    if (!mobile || !validateMobile(mobile)) {
        showError('mobile', 'Mobile number must be exactly 10 digits');
        isValid = false;
    }

    // Validate Email
    if (!email || !validateEmail(email)) {
        showError('email', 'Please enter a valid email address');
        isValid = false;
    }

    // Validate Turf Selection
    if (!turfName) {
        showError('turf', 'Please select a turf');
        isValid = false;
    }

    // Validate Date
    if (!date || !validateDate(date)) {
        showError('date', 'Please select a valid date (today or future)');
        isValid = false;
    }

    // Validate Start Time
    if (!startTime || !validateTime(startTime)) {
        showError('startTime', 'Please enter a valid start time');
        isValid = false;
    }

    // Validate End Time
    if (!endTime || !validateTime(endTime)) {
        showError('endTime', 'Please enter a valid end time');
        isValid = false;
    }

    // Validate Time Range
    if (startTime && endTime && !validateTimeRange(startTime, endTime)) {
        showError('startTime', 'Start time must be before end time');
        isValid = false;
    }

    if (!isValid) {
        showMessage('Please fix the errors above', 'error');
        return;
    }

    // Show loading state
    const submitBtn = document.querySelector('.btn-submit');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoader = document.getElementById('btnLoader');
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline';

    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'book');
    formData.append('name', name);
    formData.append('mobile', mobile);
    formData.append('email', email);
    formData.append('turf_name', turfName);
    formData.append('date', date);
    formData.append('start_time', startTime);
    formData.append('end_time', endTime);

    // Submit via AJAX
    fetch('booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';

        if (data.status === 'success') {
            // Show success modal
            showSuccessModal(data.data.booking_id);

            // Reset form
            document.getElementById('bookingForm').reset();
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date').value = today;

            // Reload slots
            loadSlots();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
        showMessage('An error occurred. Please try again.', 'error');
        console.error('Error:', error);
    });
}

/**
 * Show success modal
 */
function showSuccessModal(bookingId) {
    document.getElementById('bookingId').textContent = bookingId;
    document.getElementById('successMessage').textContent = 'Your turf has been booked successfully!';
    document.getElementById('successModal').style.display = 'block';
}

/**
 * Close modal
 */
function closeModal() {
    document.getElementById('successModal').style.display = 'none';
}

/**
 * Show message (success/error)
 */
function showMessage(message, type) {
    const msgElement = document.getElementById('bookingMessage');
    msgElement.textContent = message;
    msgElement.className = `message ${type}`;
    msgElement.style.display = 'block';

    // Auto-hide error messages after 5 seconds
    if (type === 'error') {
        setTimeout(() => {
            msgElement.style.display = 'none';
        }, 5000);
    }
}

// ==================== SLOTS MANAGEMENT ====================

/**
 * Load available slots
 */
function loadSlots() {
    const turf = document.getElementById('filterTurf').value;
    const date = document.getElementById('filterDate').value;
    const slotsBody = document.getElementById('slotsBody');

    if (!date) {
        slotsBody.innerHTML = '<tr><td colspan="2" style="text-align: center; padding: 2rem;">Please select a date</td></tr>';
        return;
    }

    // Show loading state
    slotsBody.innerHTML = '<tr><td colspan="2" style="text-align: center; padding: 2rem;">Loading slots...</td></tr>';

    // Fetch slots via AJAX
    fetch(`slots.php?action=get_slots&date=${date}&turf=${encodeURIComponent(turf)}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displaySlots(data.data.slots);
            } else {
                slotsBody.innerHTML = `<tr><td colspan="2" style="text-align: center; padding: 2rem; color: red;">${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            slotsBody.innerHTML = '<tr><td colspan="2" style="text-align: center; padding: 2rem; color: red;">Failed to load slots</td></tr>';
            console.error('Error:', error);
        });
}

/**
 * Display slots in table
 */
function displaySlots(slots) {
    const slotsBody = document.getElementById('slotsBody');
    
    if (slots.length === 0) {
        slotsBody.innerHTML = '<tr><td colspan="2" style="text-align: center; padding: 2rem;">No slots available</td></tr>';
        return;
    }

    let html = '';
    slots.forEach(slot => {
        const statusClass = slot.is_available ? 'available' : 'reserved';
        const clickable = slot.is_available ? 'onclick="fillSlotTime(\'' + slot.start_time + '\', \'' + slot.end_time + '\')"' : '';
        
        html += `
            <tr>
                <td>
                    <strong>${slot.start_time} - ${slot.end_time}</strong>
                </td>
                <td>
                    <span class="status-badge ${statusClass}" ${clickable}>
                        ${slot.status}
                    </span>
                </td>
            </tr>
        `;
    });

    slotsBody.innerHTML = html;
}

/**
 * Fill start and end time when clicking on available slot
 */
function fillSlotTime(startTime, endTime) {
    document.getElementById('startTime').value = startTime;
    document.getElementById('endTime').value = endTime;

    // Scroll to form
    document.getElementById('bookingForm').scrollIntoView({ behavior: 'smooth' });

    // Highlight form
    document.querySelector('.booking-form').style.boxShadow = '0 0 0 3px rgba(39, 174, 96, 0.3)';
    setTimeout(() => {
        document.querySelector('.booking-form').style.boxShadow = '';
    }, 2000);
}

// ==================== AUTO-REFRESH SLOTS ====================

/**
 * Auto-refresh slots every 30 seconds
 */
setInterval(loadSlots, 30000);

// ==================== CLOSE MODAL ON OUTSIDE CLICK ====================
window.addEventListener('click', function(event) {
    const modal = document.getElementById('successModal');
    if (event.target === modal) {
        closeModal();
    }
});

// ==================== REAL-TIME VALIDATION ====================

/**
 * Validate name in real-time
 */
document.getElementById('name')?.addEventListener('blur', function() {
    if (this.value && !validateName(this.value)) {
        showError('name', 'Name must be at least 3 characters (letters only)');
    } else {
        showError('name', '');
    }
});

/**
 * Validate mobile in real-time
 */
document.getElementById('mobile')?.addEventListener('blur', function() {
    if (this.value && !validateMobile(this.value)) {
        showError('mobile', 'Mobile number must be exactly 10 digits');
    } else {
        showError('mobile', '');
    }
});

/**
 * Validate email in real-time
 */
document.getElementById('email')?.addEventListener('blur', function() {
    if (this.value && !validateEmail(this.value)) {
        showError('email', 'Please enter a valid email address');
    } else {
        showError('email', '');
    }
});

/**
 * Validate start time
 */
document.getElementById('startTime')?.addEventListener('change', function() {
    const endTime = document.getElementById('endTime').value;
    if (this.value && endTime && !validateTimeRange(this.value, endTime)) {
        showError('startTime', 'Start time must be before end time');
    } else {
        showError('startTime', '');
    }
});

/**
 * Validate end time
 */
document.getElementById('endTime')?.addEventListener('change', function() {
    const startTime = document.getElementById('startTime').value;
    if (this.value && startTime && !validateTimeRange(startTime, this.value)) {
        showError('endTime', 'End time must be after start time');
    } else {
        showError('endTime', '');
    }
});

// ==================== MOBILE NUMBER INPUT - ONLY DIGITS ====================

document.getElementById('mobile')?.addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
});

// ==================== UTILITY FUNCTIONS ====================

/**
 * Format time from 24-hour to 12-hour format
 */
function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

/**
 * Format date
 */
function formatDate(dateStr) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateStr).toLocaleDateString('en-US', options);
}
