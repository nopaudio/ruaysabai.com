<?php
/**
 * Admin Security Enhancement Script
 * 
 * This script provides improved security measures for the admin section:
 * - Advanced brute force protection with IP tracking
 * - Secure session management
 * - CSRF protection
 * - Activity logging
 * - Role-based access control
 */

// Start or resume session with secure parameters
if (session_status() == PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Use HTTPS in production
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Include database configuration
require_once __DIR__ . '/../config.php';

/**
 * Admin Security Class
 */
class AdminSecurity {
    private $db;
    private $max_login_attempts = 5;
    private $lockout_time = 1800; // 30 minutes in seconds
    private $attempt_window = 3600; // 1 hour in seconds
    
    /**
     * Constructor - initialize database connection
     */
    public function __construct($db_config) {
        try {
            $this->db = new mysqli(
                $db_config['host'],
                $db_config['username'],
                $db_config['password'],
                $db_config['database']
            );
            
            if ($this->db->connect_error) {
                throw new Exception("Database connection error: " . $this->db->connect_error);
            }
            
            $this->db->set_charset($db_config['charset']);
            
            // Create login_attempts table if it doesn't exist
            $this->createLoginAttemptsTable();
            $this->createAdminLogsTable();
            
        } catch (Exception $e) {
            // Log error but don't expose details
            error_log("Database connection error: " . $e->getMessage());
            die("ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ");
        }
    }
    
    /**
     * Create login_attempts table if it doesn't exist
     */
    private function createLoginAttemptsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT(11) NOT NULL AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            username VARCHAR(50) NOT NULL,
            attempt_time DATETIME NOT NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            INDEX (ip_address),
            INDEX (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        if (!$this->db->query($sql)) {
            error_log("Error creating login_attempts table: " . $this->db->error);
        }
    }
    
    /**
     * Create admin_logs table if it doesn't exist
     */
    private function createAdminLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS admin_logs (
            id INT(11) NOT NULL AUTO_INCREMENT,
            admin_id INT(11) NOT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            module VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX (admin_id),
            INDEX (action),
            INDEX (module),
            INDEX (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        if (!$this->db->query($sql)) {
            error_log("Error creating admin_logs table: " . $this->db->error);
        }
    }
    
    /**
     * Authenticate admin user
     * 
     * @param string $username Username to authenticate
     * @param string $password Password to verify
     * @return array|bool User data array on success, false on failure
     */
    public function authenticate($username, $password) {
        // Check for brute force attempts
        if ($this->isUserLocked($username)) {
            $_SESSION['login_error'] = "บัญชีถูกล็อกชั่วคราวเนื่องจากมีการเข้าสู่ระบบล้มเหลวหลายครั้ง กรุณาลองใหม่ภายหลัง";
            return false;
        }
        
        // Sanitize input
        $username = $this->sanitizeInput($username);
        
        try {
            // First check if user exists in admins table
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                if (($user['is_admin'] == 1) && 
                    (password_verify($password, $user['password']) || $password === $user['password'])) {
                    
                    // Add admin role information
                    $user['role'] = 'admin';
                    
                    // Log successful attempt
                    $this->logLoginAttempt($username, true);
                    
                    // Update last login time
                    $update_stmt = $this->db->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    
                    return $user;
                }
            }
            
            // Log failed attempt
            $this->logLoginAttempt($username, false);
            return false;
            
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log login attempt
     * 
     * @param string $username Username attempted
     * @param bool $success Whether attempt was successful
     */
    private function logLoginAttempt($username, $success = false) {
        try {
            $ip = $this->getIpAddress();
            $stmt = $this->db->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time, success) VALUES (?, ?, NOW(), ?)");
            $stmt->bind_param("ssi", $ip, $username, $success);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error logging login attempt: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user account is locked due to too many failed attempts
     * 
     * @param string $username Username to check
     * @return bool True if account is locked, false otherwise
     */
    private function isUserLocked($username) {
        try {
            $ip = $this->getIpAddress();
            $time_window = date('Y-m-d H:i:s', time() - $this->attempt_window);
            
            // Count failed attempts from this IP for this username within time window
            $stmt = $this->db->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                                      WHERE (ip_address = ? OR username = ?) 
                                      AND success = 0 AND attempt_time > ?");
            $stmt->bind_param("sss", $ip, $username, $time_window);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            // Check recent successful logins
            $last_success_stmt = $this->db->prepare("SELECT attempt_time FROM login_attempts 
                                                  WHERE (ip_address = ? OR username = ?) 
                                                  AND success = 1 
                                                  ORDER BY attempt_time DESC LIMIT 1");
            $last_success_stmt->bind_param("ss", $ip, $username);
            $last_success_stmt->execute();
            $last_success_result = $last_success_stmt->get_result();
            
            if ($row['attempts'] >= $this->max_login_attempts) {
                // If there was a successful login after failed attempts, reset the counter
                if ($last_success_result->num_rows > 0) {
                    $last_success = $last_success_result->fetch_assoc();
                    $last_success_time = strtotime($last_success['attempt_time']);
                    
                    $last_failed_stmt = $this->db->prepare("SELECT MAX(attempt_time) as last_attempt FROM login_attempts 
                                                         WHERE (ip_address = ? OR username = ?) 
                                                         AND success = 0");
                    $last_failed_stmt->bind_param("ss", $ip, $username);
                    $last_failed_stmt->execute();
                    $last_failed_result = $last_failed_stmt->get_result();
                    
                    if ($last_failed_result->num_rows > 0) {
                        $last_failed = $last_failed_result->fetch_assoc();
                        if ($last_failed['last_attempt'] && strtotime($last_failed['last_attempt']) < $last_success_time) {
                            return false;
                        }
                    }
                }
                
                // Check if lockout period has passed
                $latest_attempt_stmt = $this->db->prepare("SELECT MAX(attempt_time) as latest FROM login_attempts 
                                                        WHERE (ip_address = ? OR username = ?) 
                                                        AND success = 0");
                $latest_attempt_stmt->bind_param("ss", $ip, $username);
                $latest_attempt_stmt->execute();
                $latest_attempt_result = $latest_attempt_stmt->get_result();
                
                if ($latest_attempt_result->num_rows > 0) {
                    $latest_attempt = $latest_attempt_result->fetch_assoc();
                    if ($latest_attempt['latest']) {
                        $lockout_end = strtotime($latest_attempt['latest']) + $this->lockout_time;
                        if (time() < $lockout_end) {
                            return true; // Account is still locked
                        }
                    }
                }
            }
            
            return false; // Account is not locked
        } catch (Exception $e) {
            error_log("Error checking if user is locked: " . $e->getMessage());
            return false; // Default to not locked in case of error
        }
    }
    
    /**
     * Record admin activity for audit purposes
     * 
     * @param string $action Action performed
     * @param string $details Additional details about the action
     * @param string $module Module where action was performed
     * @return bool Success status
     */
    public function logAdminActivity($action, $details = '', $module = '') {
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }
        
        $admin_id = $_SESSION['admin_id'];
        $ip_address = $this->getIpAddress();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Get current module from URL if not provided
        if (empty($module)) {
            $current_file = basename($_SERVER['PHP_SELF']);
            $module = str_replace('.php', '', $current_file);
        }
        
        try {
            $stmt = $this->db->prepare("INSERT INTO admin_logs (admin_id, action, details, module, ip_address, user_agent, created_at) 
                                     VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->bind_param("isssss", $admin_id, $action, $details, $module, $ip_address, $user_agent);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error logging admin activity: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client IP address with proxy support
     * 
     * @return string IP address
     */
    private function getIpAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Get the first IP in case of multiple proxies
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        // Validate IP format
        return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }
    
    /**
     * Generate and store CSRF token
     * 
     * @param string $form_name Form name for specific token
     * @return string CSRF token
     */
    public function generateCsrfToken($form_name = 'global') {
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$form_name] = [
            'token' => $token,
            'expires' => time() + 3600 // Token expires after 1 hour
        ];
        
        return $token;
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @param string $form_name Form name for specific token
     * @return bool True if token is valid, false otherwise
     */
    public function verifyCsrfToken($token, $form_name = 'global') {
        if (!isset($_SESSION['csrf_tokens'][$form_name])) {
            return false;
        }
        
        $stored = $_SESSION['csrf_tokens'][$form_name];
        
        if (time() > $stored['expires']) {
            unset($_SESSION['csrf_tokens'][$form_name]);
            return false;
        }
        
        return hash_equals($stored['token'], $token);
    }
    
    /**
     * Create CSRF token input field
     * 
     * @param string $form_name Form name for specific token
     * @return string HTML input field
     */
    public function csrfTokenField($form_name = 'global') {
        $token = $this->generateCsrfToken($form_name);
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
    
    /**
     * Sanitize user input
     * 
     * @param string $data Data to sanitize
     * @return string Sanitized data
     */
    public function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    /**
     * Enforce HTTPS for admin pages
     */
    public function enforceHttps() {
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirect");
            exit;
        }
    }
    
    /**
     * Check if user has admin privileges
     * 
     * @return bool True if user is admin, false otherwise
     */
    public function isAdmin() {
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }
        
        // Double-check from database to prevent session hijacking
        $stmt = $this->db->prepare("SELECT is_admin FROM users WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        return ($user && $user['is_admin'] == 1);
    }
    
    /**
     * Requires admin login or redirects to login page
     * 
     * @param string $redirect_url URL to redirect to after login
     */
    public function requireAdminLogin($redirect_url = null) {
        if (!$this->isAdmin()) {
            if ($redirect_url) {
                $_SESSION['redirect_url'] = $redirect_url;
            } else {
                $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            }
            
            $_SESSION['error_message'] = "กรุณาเข้าสู่ระบบในฐานะผู้ดูแลระบบก่อนเข้าใช้งาน";
            
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * Check password strength
     * 
     * @param string $password Password to check
     * @return array Result with status and message
     */
    public function checkPasswordStrength($password) {
        $result = [
            'strong' => false,
            'message' => []
        ];
        
        // Check length
        if (strlen($password) < 8) {
            $result['message'][] = "รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
        }
        
        // Check for uppercase
        if (!preg_match('/[A-Z]/', $password)) {
            $result['message'][] = "รหัสผ่านต้องมีตัวอักษรพิมพ์ใหญ่อย่างน้อย 1 ตัว";
        }
        
        // Check for lowercase
        if (!preg_match('/[a-z]/', $password)) {
            $result['message'][] = "รหัสผ่านต้องมีตัวอักษรพิมพ์เล็กอย่างน้อย 1 ตัว";
        }
        
        // Check for numbers
        if (!preg_match('/[0-9]/', $password)) {
            $result['message'][] = "รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว";
        }
        
        // Check for special characters
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $result['message'][] = "รหัสผ่านต้องมีอักขระพิเศษอย่างน้อย 1 ตัว";
        }
        
        // If no issues found, password is strong
        if (empty($result['message'])) {
            $result['strong'] = true;
        }
        
        return $result;
    }
}

// Create instance with global database config
$adminSecurity = new AdminSecurity($db_config);

/**
 * Show admin alert messages
 * 
 * @param string $message Alert message
 * @param string $type Alert type (success, danger, warning, info)
 */
function showAdminAlert($message, $type = 'success') {
    echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
    echo $message;
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

/**
 * Check for session messages and display alerts
 */
function showAdminSessionAlerts() {
    $alert_types = [
        'success_message' => 'success',
        'error_message' => 'danger',
        'warning_message' => 'warning',
        'info_message' => 'info'
    ];
    
    foreach ($alert_types as $message_key => $alert_type) {
        if (isset($_SESSION[$message_key])) {
            showAdminAlert($_SESSION[$message_key], $alert_type);
            unset($_SESSION[$message_key]);
        }
    }
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if email is valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if request is AJAX
 * 
 * @return bool True if request is AJAX, false otherwise
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Generate a secure random password
 * 
 * @param int $length Password length
 * @return string Generated password
 */
function generateSecurePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+;:,.?';
    $password = '';
    $max = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    
    return $password;
}
?>