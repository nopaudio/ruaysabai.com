<?php
session_start();

define('USER_FILE', __DIR__ . '/users.json');

function load_users() {
    if (!file_exists(USER_FILE)) {
        file_put_contents(USER_FILE, json_encode([]));
    }
    $json = file_get_contents(USER_FILE);
    return json_decode($json, true);
}

function save_users($users) {
    file_put_contents(USER_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function register($username, $password) {
    $users = load_users();
    if (isset($users[$username])) {
        return "Username นี้มีอยู่แล้ว";
    }
    $users[$username] = password_hash($password, PASSWORD_DEFAULT);
    save_users($users);
    return "สมัครสมาชิกสำเร็จ";
}

function login($username, $password) {
    $users = load_users();
    if (!isset($users[$username])) {
        return "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
    if (password_verify($password, $users[$username])) {
        $_SESSION['username'] = $username;
        return true;
    }
    return "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}

function logout() {
    session_destroy();
}

function is_logged_in() {
    return isset($_SESSION['username']);
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        $message = register(trim($_POST['username']), $_POST['password']);
    } elseif (isset($_POST['login'])) {
        $result = login(trim($_POST['username']), $_POST['password']);
        $message = $result === true ? "เข้าสู่ระบบสำเร็จ" : $result;
    } elseif (isset($_POST['logout'])) {
        logout();
        $message = "ออกจากระบบแล้ว";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Membership System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* Professional, modern UI */
        body {
            background: linear-gradient(120deg,#f8fafc 0%, #e0e7ff 100%);
            font-family: 'Segoe UI', 'Prompt', Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #22223b;
            min-height: 100vh;
        }
        .container {
            max-width: 380px;
            margin: 48px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(31, 41, 55, 0.15);
            padding: 32px 28px;
        }
        h2 {
            text-align: center;
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: 0.5px;
        }
        h3 {
            margin-top: 28px;
            font-size: 1.15rem;
            color: #3e4a89;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 10px;
        }
        input[type="text"], input[type="password"] {
            padding: 12px 14px;
            border: 1.5px solid #c9d6ff;
            border-radius: 7px;
            font-size: 1rem;
            background: #f4f6fb;
            transition: border 0.2s;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: #5a67d8;
            outline: none;
            background: #e7ecff;
        }
        button {
            padding: 12px;
            background: linear-gradient(90deg, #5a67d8 30%, #667eea 100%);
            color: white;
            border: none;
            border-radius: 7px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px 0 rgba(90, 103, 216, 0.10);
        }
        button:hover {
            background: linear-gradient(90deg, #4c51bf 30%, #5a67d8 100%);
        }
        .message {
            margin: 16px 0 12px 0;
            text-align: center;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 1rem;
        }
        .success {
            background: #e3fcec;
            color: #256029;
            border: 1.5px solid #7ce2a6;
        }
        .error {
            background: #ffe7e7;
            color: #ab2222;
            border: 1.5px solid #ffaaaa;
        }
        .welcome {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
        }
        @media (max-width: 500px) {
            .container {
                width: 97vw;
                max-width: 97vw;
                padding: 22px 6vw;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>ระบบสมาชิก</h2>
        <?php if ($message) : ?>
            <div class="message <?= strpos($message, 'สำเร็จ') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if (is_logged_in()): ?>
            <div class="welcome">
                <p>👋 ยินดีต้อนรับ, <b><?= htmlspecialchars($_SESSION['username']) ?></b></p>
                <form method="post">
                    <button type="submit" name="logout">ออกจากระบบ</button>
                </form>
            </div>
        <?php else: ?>
            <h3>สมัครสมาชิก</h3>
            <form method="post" autocomplete="off">
                <input type="text" name="username" placeholder="ชื่อผู้ใช้ (a-z, 0-9)" required pattern="[a-zA-Z0-9]{3,20}">
                <input type="password" name="password" placeholder="รหัสผ่าน (ขั้นต่ำ 6 ตัวอักษร)" required minlength="6">
                <button type="submit" name="register">สมัครสมาชิก</button>
            </form>
            <h3>เข้าสู่ระบบ</h3>
            <form method="post" autocomplete="off">
                <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
                <input type="password" name="password" placeholder="รหัสผ่าน" required>
                <button type="submit" name="login">เข้าสู่ระบบ</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>