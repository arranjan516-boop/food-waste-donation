<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('ngo_header')) {

    function ngo_header($page_title = 'NGO Dashboard')
    {
        $current_page = basename($_SERVER['PHP_SELF']);

        $ngo_name = $_SESSION['name'] ?? 'NGO User';
        $ngo_role = $_SESSION['role'] ?? 'NGO';

        ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta name="viewport"
                  content="width=device-width, initial-scale=1.0">

            <title>
                <?= htmlspecialchars($page_title) ?> | FoodShare
            </title>

            <!-- Google Font -->
            <link rel="preconnect"
                  href="https://fonts.googleapis.com">

            <link rel="preconnect"
                  href="https://fonts.gstatic.com"
                  crossorigin>

            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
                  rel="stylesheet">

            <!-- Font Awesome -->
            <link rel="stylesheet"
                  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

            <!-- NGO CSS -->
            <link rel="stylesheet"
                  href="../assets/css/ngo.css">

        </head>

        <body>

        <div class="ngo-app">

            <!-- ==========================================
                 SIDEBAR
            =========================================== -->

            <aside class="ngo-sidebar" id="ngoSidebar">

                <!-- Logo -->

                <div class="ngo-brand">

                    <div class="ngo-brand-icon">
                        <i class="fa-solid fa-leaf"></i>
                    </div>

                    <div class="ngo-brand-text">
                        <h2>FoodShare</h2>
                        <span>NGO Portal</span>
                    </div>

                </div>


                <!-- User Profile -->

                <div class="ngo-sidebar-user">

                    <div class="ngo-avatar">

                        <i class="fa-solid fa-building"></i>

                    </div>

                    <div class="ngo-user-text">

                        <strong>
                            <?= htmlspecialchars($ngo_name) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars($ngo_role) ?>
                        </span>

                    </div>

                    <i class="fa-solid fa-chevron-down ngo-user-arrow"></i>

                </div>


                <!-- Navigation -->

                <div class="ngo-menu">

                    <div class="ngo-menu-label">
                        MAIN MENU
                    </div>


                    <a href="dashboard.php"
                       class="ngo-menu-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">

                        <span class="ngo-menu-icon">
                            <i class="fa-solid fa-house"></i>
                        </span>

                        <span>Dashboard</span>

                    </a>


                    <a href="available-donations.php"
                       class="ngo-menu-item <?= $current_page == 'available-donations.php' ? 'active' : '' ?>">

                        <span class="ngo-menu-icon">
                            <i class="fa-solid fa-utensils"></i>
                        </span>

                        <span>Available Donations</span>

                    </a>


                    <a href="my-donations.php"
                       class="ngo-menu-item <?= $current_page == 'my-donations.php' ? 'active' : '' ?>">

                        <span class="ngo-menu-icon">
                            <i class="fa-solid fa-box-open"></i>
                        </span>

                        <span>My Donations</span>

                    </a>


                    <a href="pickup-schedule.php"
                       class="ngo-menu-item <?= $current_page == 'pickup-schedule.php' ? 'active' : '' ?>">

                        <span class="ngo-menu-icon">
                            <i class="fa-solid fa-calendar-check"></i>
                        </span>

                        <span>Pickup Schedule</span>

                    </a>


                    <a href="collection-status.php"
                       class="ngo-menu-item <?= $current_page == 'collection-status.php' ? 'active' : '' ?>">

                        <span class="ngo-menu-icon">
                            <i class="fa-solid fa-truck"></i>
                        </span>

                        <span>Collection Status</span>

                    </a>


                    <a href="distribution.php"
                       class="ngo-menu-item <?= $current_page == 'distribution.php' ? 'active' : '' ?>">

                        <span class="ngo-menu-icon">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                        </span>

                        <span>Distribution</span>

                    </a>


                    <a href="completed-donations.php"
                       class="ngo-menu-item <?= $current_page == 'completed-donations.php' ? 'active' : '' ?>">

                        <span class="ngo-menu-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </span>

                        <span>Completed</span>

                    </a>


                    <div class="ngo-menu-label ngo-account-label">
                        ACCOUNT
                    </div>


                    <a href="notifications.php"
                       class="ngo-menu-item <?= $current_page == 'notifications.php' ? 'active' : '' ?>">

                        <span class="ngo-menu-icon">
                            <i class="fa-regular fa-bell"></i>
                        </span>

                        <span>Notifications</span>

                        <span class="ngo-notification-count">
                            3
                        </span>

                    </a>


                    <a href="history.php"
                       class="ngo-menu-item <?= $current_page == 'history.php' ? 'active' : '' ?>">

                        <span class="ngo-menu-icon">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </span>

                        <span>History</span>

                    </a>


                    <a href="feedback.php"
                       class="ngo-menu-item <?= $current_page == 'feedback.php' ? 'active' : '' ?>">

                        <span class="ngo-menu-icon">
                            <i class="fa-regular fa-comment-dots"></i>
                        </span>

                        <span>Feedback</span>

                    </a>


                    <a href="profile.php"
                       class="ngo-menu-item <?= ($current_page == 'profile.php' || $current_page == 'edit-profile.php') ? 'active' : '' ?>">

                        <span class="ngo-menu-icon">
                            <i class="fa-regular fa-user"></i>
                        </span>

                        <span>Profile</span>

                    </a>


                    <a href="../logout.php"
                       class="ngo-menu-item ngo-logout">

                        <span class="ngo-menu-icon">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </span>

                        <span>Logout</span>

                    </a>

                </div>

            </aside>


            <!-- ==========================================
                 MAIN AREA
            =========================================== -->

            <main class="ngo-main">


                <!-- TOPBAR -->

                <header class="ngo-topbar">

                    <div class="ngo-top-left">

                        <button type="button"
                                class="ngo-mobile-menu"
                                onclick="toggleNGOMenu()">

                            <i class="fa-solid fa-bars"></i>

                        </button>

                        <div>

                            <div class="ngo-breadcrumb">
                                FoodShare / NGO
                            </div>

                            <h1>
                                <?= htmlspecialchars($page_title) ?>
                            </h1>

                        </div>

                    </div>


                    <div class="ngo-top-right">

                        <button class="ngo-top-icon"
                                onclick="window.location='notifications.php'">

                            <i class="fa-regular fa-bell"></i>

                            <span class="ngo-red-dot"></span>

                        </button>


                        <div class="ngo-top-divider"></div>


                        <a href="profile.php"
                           class="ngo-top-profile">

                            <div class="ngo-top-avatar">
                                <i class="fa-solid fa-building"></i>
                            </div>

                            <div class="ngo-top-profile-info">

                                <strong>
                                    <?= htmlspecialchars($ngo_name) ?>
                                </strong>

                                <span>
                                    NGO Partner
                                </span>

                            </div>

                            <i class="fa-solid fa-chevron-down"></i>

                        </a>

                    </div>

                </header>


                <!-- CONTENT -->

                <div class="ngo-content">

        <?php
    }
}


if (!function_exists('ngo_footer')) {

    function ngo_footer()
    {
        ?>

                </div>

            </main>

        </div>


        <script>

            function toggleNGOMenu()
            {
                const sidebar =
                    document.getElementById('ngoSidebar');

                sidebar.classList.toggle('mobile-open');
            }


            document.addEventListener(
                'click',
                function(event)
                {
                    const sidebar =
                        document.getElementById('ngoSidebar');

                    const button =
                        document.querySelector('.ngo-mobile-menu');

                    if (
                        window.innerWidth <= 900 &&
                        sidebar.classList.contains('mobile-open') &&
                        !sidebar.contains(event.target) &&
                        !button.contains(event.target)
                    )
                    {
                        sidebar.classList.remove('mobile-open');
                    }
                }
            );

        </script>

        </body>

        </html>

        <?php
    }
}
?>
