<?php
/**
 * API: Get Current Active Session & CSRF Token
 * Method: GET
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

$user = get_authenticated_user();
$csrfToken = generate_csrf_token();

json_response([
    'success'    => true,
    'user'       => $user,
    'csrf_token' => $csrfToken
]);
