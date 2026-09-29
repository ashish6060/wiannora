<?php

session_start();

require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Login';

$error = '';
$success = '';

$email = '';


/* ========================================
   ADMIN CREDENTIALS
======================================== */

$adminEmail = 'admin@wiannora.com';
$adminPassword = 'admin123';


/* ========================================
   LOGIN PROCESS
======================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);


    /* ========================================
       BASIC VALIDATION
    ======================================== */

    if ($email === '' || $password === '') {

        $error = 'Please enter your email and password.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } else {


        /* ========================================
           ADMIN LOGIN
        ======================================== */

        if (
            $email === $adminEmail &&
            $password === $adminPassword
        ) {

            /*
             * Regenerate session ID
             * for security.
             */

            session_regenerate_id(true);


            /*
             * Create admin session.
             */

            
session_regenerate_id(true);

$_SESSION['admin'] = true;
$_SESSION['admin_email'] = $adminEmail;
$_SESSION['admin_logged_in'] = true;
$_SESSION['role'] = 'admin';

/*
|--------------------------------------------------------------------------
| REMOVE NORMAL USER SESSION
|--------------------------------------------------------------------------
*/

unset(
    $_SESSION['user_id'],
    $_SESSION['user_name'],
    $_SESSION['user_email'],
    $_SESSION['logged_in']
);

header('Location: products.php');
exit;



            /*
             * Redirect admin to dashboard.
             */

            header('Location: products.php');
            exit;
        }


        /* ========================================
           NORMAL USER LOGIN
        ======================================== */

        try {

            /* ========================================
               FIND USER
            ======================================== */

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    name,
                    email,
                    password,
                    status
                FROM users
                WHERE email = :email
                LIMIT 1
            ");

            $stmt->execute([
                'email' => $email
            ]);

            $user = $stmt->fetch();


            /* ========================================
               CHECK USER LOGIN
            ======================================== */

            if (
                !$user ||
                !password_verify($password, $user['password'])
            ) {

                $error = 'Invalid email or password.';

            } elseif ($user['status'] !== 'active') {

                $error = 'Your account is not active. Please contact support.';

            } else {


                /* ========================================
                   REGENERATE SESSION ID
                ======================================== */

                session_regenerate_id(true);


                /* ========================================
                   CREATE USER SESSION
                ======================================== */

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];

                $_SESSION['logged_in'] = true;
                $_SESSION['role'] = 'user';

                /*
                |--------------------------------------------------------------------------
                | REMOVE ADMIN SESSION
                |--------------------------------------------------------------------------
                */

                unset(
                    $_SESSION['admin'],
                    $_SESSION['admin_email'],
                    $_SESSION['admin_logged_in']
                );

                header('Location: index.php');
                exit;



                /* ========================================
                   REMEMBER ME
                ======================================== */

                if ($remember) {

                    /*
                     * Keep PHP session alive
                     * for 30 days.
                     */

                    ini_set(
                        'session.gc_maxlifetime',
                        2592000
                    );

                    setcookie(
                        session_name(),
                        session_id(),
                        [
                            'expires' => time() + 2592000,
                            'path' => '/',
                            'secure' => isset($_SERVER['HTTPS']),
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]
                    );
                }


                /* ========================================
                   USER LOGIN SUCCESS
                ======================================== */

                header('Location: index.php');
                exit;
            }

        } catch (PDOException $e) {

            /*
             * Log database error.
             * Do not show database details
             * to the user.
             */

            error_log(
                'Wiannora Login Error: ' .
                $e->getMessage()
            );

            $error =
                'Something went wrong. Please try again later.';
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Login | Wiannora</title>

<meta
    name="description"
    content="Login to your Wiannora Beauty account."
>


<!-- ========================================
     GOOGLE FONTS
======================================== -->

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
    rel="stylesheet"
>


<!-- ========================================
     LOGIN CSS
======================================== -->

<link
    rel="stylesheet"
    href="assets/css/login.css"
>

</head>


<body>

<div class="login-page">


<!-- ========================================
     LEFT VISUAL
======================================== -->

<section class="login-visual">

    <div class="visual-content">

        <span>
            WELCOME TO WIANNORA
        </span>

        <h1>
            Beauty that feels like you.
        </h1>

        <p>
            Discover your favourite beauty essentials,
            save your favourites and enjoy a more personal
            shopping experience.
        </p>

    </div>

</section>


<!-- ========================================
     LOGIN PANEL
======================================== -->

<section class="login-panel">

    <div class="login-box">


        <!-- ========================================
             LOGO
        ======================================== -->

        <a href="index.php">

            <img
                class="login-logo"
                src="assets/images/wiannora-logo.jpeg"
                alt="Wiannora"
            >

        </a>


        <!-- ========================================
             HEADING
        ======================================== -->

        <div class="login-heading">

            <span class="eyebrow">
                YOUR BEAUTY SPACE
            </span>

            <h2>
                Welcome Back
            </h2>

            <p>
                Sign in to continue your Wiannora journey.
            </p>

        </div>


        <!-- ========================================
             ERROR MESSAGE
        ======================================== -->

        <?php if ($error !== ''): ?>

            <div
                class="login-message login-error"
                role="alert"
            >

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- ========================================
             LOGIN FORM
        ======================================== -->

        <form
            action=""
            method="POST"
            autocomplete="on"
        >


            <!-- ====================================
                 EMAIL
            ==================================== -->

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <div class="input-wrap">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >

                        <rect
                            x="3"
                            y="5"
                            width="18"
                            height="14"
                            rx="2"
                            stroke="currentColor"
                            stroke-width="1.7"
                        />

                        <path
                            d="M4 7L12 13L20 7"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                    </svg>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                        autocomplete="email"
                    >

                </div>

            </div>


            <!-- ====================================
                 PASSWORD
            ==================================== -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <div class="input-wrap">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >

                        <rect
                            x="5"
                            y="10"
                            width="14"
                            height="10"
                            rx="2"
                            stroke="currentColor"
                            stroke-width="1.7"
                        />

                        <path
                            d="M8 10V7.5C8 5.29 9.79 3.5 12 3.5C14.21 3.5 16 5.29 16 7.5V10"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />

                    </svg>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="passwordToggle"
                    >
                        Show
                    </button>

                </div>

            </div>


            <!-- ====================================
                 OPTIONS
            ==================================== -->

            <div class="form-options">

                <label class="remember">

                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                    >

                    Remember me

                </label>


                <a
                    href="forgot-password.php"
                    class="forgot-link"
                >
                    Forgot password?
                </a>

            </div>


            <!-- ====================================
                 LOGIN BUTTON
            ==================================== -->

            <button
                type="submit"
                class="login-button"
            >

                SIGN IN

            </button>

        </form>


        <!-- ========================================
             DIVIDER
        ======================================== -->

        <div class="divider">

            <span>
                NEW TO WIANNORA?
            </span>

        </div>


        <!-- ========================================
             REGISTER
        ======================================== -->

        <p class="register-text">

            Don't have an account?

            <a href="register.php">
                Create an account
            </a>

        </p>


        <!-- ========================================
             HOME
        ======================================== -->

        <a
            href="index.php"
            class="back-home"
        >
            ← Back to Wiannora
        </a>

    </div>

</section>

</div>


<!-- ========================================
     JAVASCRIPT
======================================== -->

<script>

const passwordInput =
    document.getElementById("password");

const passwordToggle =
    document.getElementById("passwordToggle");


passwordToggle.addEventListener("click", function () {

    if (passwordInput.type === "password") {

        passwordInput.type = "text";

        passwordToggle.textContent = "Hide";

    } else {

        passwordInput.type = "password";

        passwordToggle.textContent = "Show";

    }

});

</script>

</body>

</html>