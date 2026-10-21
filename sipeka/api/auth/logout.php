<?php
/**
 * API: User Logout
 * Method: POST / GET
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

logout_user();

json_response([
    'success' => true,
    'message' => 'Sesi telah diakhiri.'
]);
