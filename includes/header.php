<?php

/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| PAGE INFORMATION
|--------------------------------------------------------------------------
*/

$pageTitle = $pageTitle ?? 'Wiannora Beauty';

$currentPage = basename($_SERVER['PHP_SELF']);


/*
|--------------------------------------------------------------------------
| USER ROLE
|--------------------------------------------------------------------------
*/

$isAdmin = (
    isset($_SESSION['role']) &&
    $_SESSION['role'] === 'admin'
);

$isUser = (
    isset($_SESSION['role']) &&
    $_SESSION['role'] === 'user' &&
    isset($_SESSION['user_id'])
);

$isLoggedIn = $isAdmin || $isUser;

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($pageTitle) ?> | Wiannora
    </title>

    <meta
        name="description"
        content="Wiannora — beauty in self-love. Explore modern makeup, lip colour and skincare-inspired essentials."
    >

    <!-- GOOGLE FONTS -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- MAIN CSS -->

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">

</head>


<body>


<!-- =========================================================
     ANNOUNCEMENT BAR
========================================================= -->

<div class="announcement">
    <span></span>
</div>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="site-header" id="siteHeader">

    <div class="container nav-wrap">

        <!-- MOBILE MENU -->

        <button
            class="mobile-menu"
            aria-label="Open menu"
            aria-expanded="false"
            type="button"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>


        <!-- LOGO -->

        <a
            class="brand"
            href="index.php"
            aria-label="Wiannora home"
        >
            <img
                src="assets/images/wiannora-logo.jpeg"
                alt="Wiannora"
            >
        </a>


        <!-- MAIN NAVIGATION -->

        <nav class="main-nav" id="mainNav">

            <a
                class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"
                href="index.php"
            >
                Home
            </a>

            <a
                class="<?= $currentPage === 'products.php' ? 'active' : '' ?>"
                href="products.php"
            >
                Shop
            </a>

            <a
                class="<?= $currentPage === 'collections.php' ? 'active' : '' ?>"
                href="collections.php"
            >
                Collections
            </a>

            <a
                class="<?= $currentPage === 'about.php' ? 'active' : '' ?>"
                href="about.php"
            >
                About
            </a>

            <a
                class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>"
                href="contact.php"
            >
                Contact
            </a>

        </nav>


        <!-- =================================================
             NAVIGATION ACTIONS
        ================================================== -->

        <div class="nav-actions">

            <!-- SEARCH -->

            <button
                class="icon-btn"
                aria-label="Search"
                data-search-toggle
                type="button"
            >
                ⌕
            </button>


            <!-- ACCOUNT -->

            <?php if ($isAdmin): ?>

                <div class="account-area">

                    <div class="account-user admin-account">

                        <svg
                            width="21" height="21"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                            aria-hidden="true"
                        >
                            <circle
                                cx="12" cy="8" r="4"
                                stroke="currentColor"
                                stroke-width="1.8"
                            />
                            <path
                                d="M4 21C4.8 16.8 7.5 14.5 12 14.5C16.5 14.5 19.2 16.8 20 21"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                            />
                        </svg>

                        <span>
                            <span class="account-role">ADMIN</span>
                            <span class="account-name">Admin</span>
                        </span>

                    </div>

                    <a href="logout.php" class="logout-link">
                        Logout
                    </a>

                </div>

            <?php elseif ($isUser): ?>

                <div class="account-area">

                    <div class="account-user">

                        <svg
                            width="21" height="21"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                            aria-hidden="true"
                        >
                            <circle
                                cx="12" cy="8" r="4"
                                stroke="currentColor"
                                stroke-width="1.8"
                            />
                            <path
                                d="M4 21C4.8 16.8 7.5 14.5 12 14.5C16.5 14.5 19.2 16.8 20 21"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                            />
                        </svg>

                        <span>
                            <span class="account-role">ACCOUNT</span>
                            <span class="account-name">
                                <?= htmlspecialchars(
                                    $_SESSION['user_name'] ?? 'User',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </span>

                    </div>

                    <a href="logout.php" class="logout-link">
                        Logout
                    </a>

                </div>

            <?php else: ?>

                <div class="account-area guest-account">

                    <a
                        href="login.php"
                        class="account-user login-link"
                        aria-label="Login"
                    >

                        <svg
                            width="21" height="21"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                            aria-hidden="true"
                        >
                            <circle
                                cx="12" cy="8" r="4"
                                stroke="currentColor"
                                stroke-width="1.8"
                            />
                            <path
                                d="M4 21C4.8 16.8 7.5 14.5 12 14.5C16.5 14.5 19.2 16.8 20 21"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                            />
                        </svg>

                        <span>
                            <span class="account-role">ACCOUNT</span>
                            <span class="account-name">Login</span>
                        </span>

                    </a>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 SHOPPING BAG  (ONLY ONE CART BUTTON)
            ================================================== -->

            <button
                type="button"
                class="cart-icon-btn"
                data-cart-open
                aria-label="Open shopping bag"
            >

                <span class="cart-icon">

                    <svg
                        width="22" height="22"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true"
                    >
                        <path
                            d="M6 8H18L20 21H4L6 8Z"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M9 8C9 5.8 10.3 4 12 4C13.7 4 15 5.8 15 8"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>

                </span>

                <span
                    class="cart-count"
                    data-cart-count
                >
                    0
                </span>

            </button>

        </div>
        <!-- /.nav-actions -->

    </div>
    <!-- /.nav-wrap -->


    <!-- SEARCH PANEL -->

    <div class="search-panel" data-search-panel>

        <div class="container search-inner">

            <input
                type="search"
                id="siteSearch"
                placeholder="Search lipsticks, tints, glosses..."
                autocomplete="off"
            >

            <button
                type="button"
                data-search-close
                aria-label="Close search"
            >
                ×
            </button>

        </div>

    </div>

</header>


<!-- =========================================================
     SHOPPING BAG POPUP  (SINGLE INSTANCE — OUTSIDE HEADER)
     Must stay OUTSIDE <header> so it is not trapped inside
     the header's stacking context.
========================================================= -->

<div
    class="cart-overlay"
    data-cart-overlay
    aria-hidden="true"
></div>


<aside
    class="cart-popup"
    data-cart-popup
    aria-hidden="true"
>

    <!-- CART HEADER -->

    <div class="cart-header">

        <div>

            <span class="cart-eyebrow">
                YOUR BAG
            </span>

            <h2>
                Shopping Bag
            </h2>

        </div>

        <button
            type="button"
            class="cart-close"
            data-cart-close
            aria-label="Close shopping bag"
        >
            ×
        </button>

    </div>


    <!-- CART ITEMS (filled by JS) -->

    <div
        class="cart-items"
        data-cart-items
    ></div>


    <!-- CART FOOTER -->

    <div class="cart-footer">

        <div class="cart-total-row">

            <span>
                Subtotal
            </span>

            <strong data-cart-total>
                ₹0.00
            </strong>

        </div>

        <button
            type="button"
            class="cart-checkout-btn"
            data-cart-checkout
        >
            Checkout
        </button>

        <a
            href="cart.php"
            class="cart-view-btn"
        >
            View Full Cart
        </a>

    </div>

</aside>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main>