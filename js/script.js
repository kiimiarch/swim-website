// Login form validation
function validateForm() {
    const phone = document.getElementById('phone').value.trim();
    const password = document.getElementById('password').value;

    if (!phone || !password) {
        alert('لطفاً تمامی فیلدها را پر کنید');
        return false;
    }

    if (!/^09\d{9}$/.test(phone)) {
        alert('شماره موبایل نامعتبر است');
        return false;
    }

    if (password.length < 4) {
        alert('رمز عبور باید حداقل ۴ کاراکتر باشد');
        return false;
    }

    return true;
}

// Dashboard JavaScript functionality

// Form validation for profile updates
function validateProfileForm() {
    const firstName = document.getElementById('first_name').value.trim();
    const lastName = document.getElementById('last_name').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const email = document.getElementById('email').value.trim();

    if (!firstName || !lastName) {
        alert('لطفاً نام و نام خانوادگی را وارد کنید');
        return false;
    }

    if (phone && !/^(09)\d{9}$/.test(phone)) {
        alert('شماره تلفن نامعتبر است');
        return false;
    }

    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('آدرس ایمیل نامعتبر است');
        return false;
    }

    return true;
}

// Function to handle AI assistant responses
function handleAIResponse(message) {
    // This function can be expanded to handle more complex AI interactions
    console.log('AI Response:', message);
}

// Function to update dashboard stats if needed
function updateDashboardStats() {
    // This could be used to update stats via AJAX if needed
    console.log('Dashboard stats updated');
}

// Initialize dashboard functionality when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard loaded successfully');

    // Add any initialization code here
    const profileForm = document.querySelector('.profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            if (!validateProfileForm()) {
                e.preventDefault();
            }
        });
    }
});