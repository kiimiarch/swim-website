<?php
/**
 * Utility functions for the application
 */

/**
 * Redirect to a page with a message stored in session
 */
function redirect_with_message($page, $type, $message) {
    $_SESSION[$type] = $message;
    header("Location: $page");
    exit;
}

/**
 * Validate phone number format
 */
function validate_phone($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Iranian phone number format
    // Could be 10 digits starting with 09 or 11 digits starting with +989 or 989
    if (preg_match('/^(09\d{9}|989\d{9}|\+989\d{9})$/', $phone)) {
        return true;
    }
    
    // Alternative: accept any 10+ digit number that looks like a phone
    if (strlen($phone) >= 10 && strlen($phone) <= 15) {
        return true;
    }
    
    return false;
}