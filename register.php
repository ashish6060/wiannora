<?php

session_start();

require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Create Account';

$error = '';
$success = '';


// =====================================================
// REGISTRATION PROCESSING
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------------------------------------------------
    // GET FORM DATA
    // -------------------------------------------------

    $name = trim($_POST['name'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $password = $_POST['password'] ?? '';

    $confirmPassword = $_POST['confirm_password'] ?? '';

    $terms = $_POST['terms'] ?? '';


    // -------------------------------------------------
    // NAME VALIDATION
    // -------------------------------------------------

    if ($name === '') {

        $error = 'Please enter your full name.';

    } elseif (strlen($name) < 2) {

        $error = 'Your name must contain at least 2 characters.';

    } elseif (strlen($name) > 100) {

        $error = 'Your name is too long.';

    }


    // -------------------------------------------------
    // EMAIL VALIDATION
    // -------------------------------------------------

    if ($error === '') {

        if ($email === '') {

            $error = 'Please enter your email address.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = 'Please enter a valid email address.';

        } elseif (strlen($email) > 150) {

            $error = 'Your email address is too long.';

        }

    }


    // -------------------------------------------------
    // PASSWORD VALIDATION
    // -------------------------------------------------

    if ($error === '') {

        if ($password === '') {

            $error = 'Please create a password.';

        } elseif (strlen($password) < 8) {

            $error = 'Password must contain at least 8 characters.';

        } elseif (strlen($password) > 255) {

            $error = 'Password is too long.';

        }

    }


    // -------------------------------------------------
    // CONFIRM PASSWORD
    // -------------------------------------------------

    if ($error === '') {

        if ($confirmPassword === '') {

            $error = 'Please confirm your password.';

        } elseif ($password !== $confirmPassword) {

            $error = 'Passwords do not match.';

        }

    }


    // -------------------------------------------------
    // TERMS & CONDITIONS
    // -------------------------------------------------

    if ($error === '') {

        if ($terms !== '1') {

            $error =
                'Please accept the Terms & Conditions and Privacy Policy.';

        }

    }


    // -------------------------------------------------
    // CHECK EXISTING EMAIL
    // -------------------------------------------------

    if ($error === '') {

        try {

            $checkUser = $pdo->prepare(
                "SELECT id
                 FROM users
                 WHERE email = :email
                 LIMIT 1"
            );

            $checkUser->execute([
                'email' => $email
            ]);

            $existingUser = $checkUser->fetch();

            if ($existingUser) {

                $error =
                    'An account with this email address already exists.';

            }

        } catch (PDOException $e) {

            error_log(
                'Wiannora Registration Check Error: ' .
                $e->getMessage()
            );

            $error =
                'Something went wrong. Please try again later.';
        }

    }


    // -------------------------------------------------
    // CREATE ACCOUNT
    // -------------------------------------------------

    if ($error === '') {

        try {

            // -----------------------------------------
            // HASH PASSWORD
            // -----------------------------------------

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // -----------------------------------------
            // INSERT USER
            // -----------------------------------------

            $insertUser = $pdo->prepare(
                "INSERT INTO users
                (
                    name,
                    email,
                    password,
                    phone,
                    status
                )
                VALUES
                (
                    :name,
                    :email,
                    :password,
                    :phone,
                    :status
                )"
            );


            $insertUser->execute([
                'name'     => $name,
                'email'    => $email,
                'password' => $hashedPassword,
                'phone'    => null,
                'status'   => 'active'
            ]);


            // -----------------------------------------
            // SUCCESS
            // -----------------------------------------

            $success =
                'Your account has been created successfully.';


            // Clear fields after successful registration
            $name = '';
            $email = '';

            /*
             * Redirect after a short successful registration
             * using JavaScript so the success message can be
             * displayed before going to login.
             */
            echo '<script>
                setTimeout(function () {
                    window.location.href = "login.php";
                }, 1500);
            </script>';


        } catch (PDOException $e) {

            error_log(
                'Wiannora Registration Error: ' .
                $e->getMessage()
            );

            $error =
                'Unable to create your account right now. Please try again later.';
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

    <title>Create Account | Wiannora</title>

    <meta
        name="description"
        content="Create your Wiannora Beauty account."
    >

    <!-- Google Fonts -->
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Register CSS -->
    <link
        rel="stylesheet"
        href="assets/css/register.css"
    >

</head>

<body>

<div class="register-page">

    <!-- ========================================
         LEFT VISUAL
    ======================================== -->

    <section class="register-visual">

        <div class="visual-content">

            <span>
                WELCOME TO WIANNORA
            </span>

            <h1>
                Your beauty,
                your story.
            </h1>

            <p>
                Create your Wiannora account and discover
                beauty essentials curated for every version
                of yourself.
            </p>

        </div>

    </section>


    <!-- ========================================
         REGISTER PANEL
    ======================================== -->

    <section class="register-panel">

        <div class="register-box">

            <!-- LOGO -->

            <a href="index.php">

                <img
                    class="register-logo"
                    src="assets/images/wiannora-logo.jpeg"
                    alt="Wiannora"
                >

            </a>


            <!-- HEADING -->

            <div class="register-heading">

                <span class="eyebrow">
                    JOIN WIANNORA
                </span>

                <h2>
                    Create Account
                </h2>

                <p>
                    Start your personal beauty journey with us.
                </p>

            </div>


            <!-- ====================================
                 SERVER MESSAGE
            ===================================== -->

            <?php if ($error !== ''): ?>

                <div
                    class="register-message register-error"
                    role="alert"
                >
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <?php if ($success !== ''): ?>

                <div
                    class="register-message register-success"
                    role="status"
                >
                    <?= htmlspecialchars($success) ?>
                </div>

            <?php endif; ?>


            <!-- REGISTER FORM -->

            <form
                method="POST"
                action=""
                autocomplete="on"
            >

                <!-- FULL NAME -->

                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <div class="input-wrap">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >

                            <circle
                                cx="12"
                                cy="8"
                                r="3.5"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />

                            <path
                                d="M5 20C5.8 16.7 8.1 15 12 15C15.9 15 18.2 16.7 19 20"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />

                        </svg>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control"
                            placeholder="Enter your full name"
                            value="<?= htmlspecialchars($name ?? '') ?>"
                            required
                            autocomplete="name"
                        >

                    </div>

                </div>


                <!-- EMAIL -->

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
                            value="<?= htmlspecialchars($email ?? '') ?>"
                            required
                            autocomplete="email"
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

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
                            class="form-control password-field"
                            placeholder="Create a password"
                            required
                            minlength="8"
                            autocomplete="new-password"
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


                <!-- CONFIRM PASSWORD -->

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
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
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control password-field"
                            placeholder="Confirm your password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="confirmPasswordToggle"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <!-- TERMS -->

                <label class="terms">

                    <input
                        type="checkbox"
                        name="terms"
                        value="1"
                        required
                    >

                    <span>

                        I agree to the

                        <a href="terms.php">
                            Terms & Conditions
                        </a>

                        and

                        <a href="privacy.php">
                            Privacy Policy
                        </a>.

                    </span>

                </label>


                <!-- REGISTER BUTTON -->

                <button
                    type="submit"
                    class="register-button"
                    <?= $success !== '' ? 'disabled' : '' ?>
                >
                    CREATE ACCOUNT
                </button>

            </form>


            <!-- DIVIDER -->

            <div class="divider">

                <span>
                    ALREADY A MEMBER?
                </span>

            </div>


            <!-- LOGIN -->

            <p class="login-text">

                Already have an account?

                <a href="login.php">
                    Sign in
                </a>

            </p>


            <!-- HOME -->

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

    // ========================================
    // PASSWORD
    // ========================================

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


    // ========================================
    // CONFIRM PASSWORD
    // ========================================

    const confirmPasswordInput =
        document.getElementById("confirm_password");

    const confirmPasswordToggle =
        document.getElementById("confirmPasswordToggle");


    confirmPasswordToggle.addEventListener("click", function () {

        if (confirmPasswordInput.type === "password") {

            confirmPasswordInput.type = "text";

            confirmPasswordToggle.textContent = "Hide";

        } else {

            confirmPasswordInput.type = "password";

            confirmPasswordToggle.textContent = "Show";

        }

    });


    // ========================================
    // PASSWORD MATCHING
    // ========================================

    const registerForm =
        document.querySelector("form");


    registerForm.addEventListener("submit", function (event) {

        if (
            passwordInput.value !==
            confirmPasswordInput.value
        ) {

            event.preventDefault();

            alert("Passwords do not match.");

            confirmPasswordInput.focus();

        }

    });

</script>

</body>
</html>