<?php

/*
|--------------------------------------------------------------------------
| NGO Layout
|--------------------------------------------------------------------------
| Common sidebar + topbar for all NGO pages
|--------------------------------------------------------------------------
*/

if (!function_exists('ngo_header')) {

    function ngo_header($page_title = "NGO Dashboard")
    {
        $current_page = basename($_SERVER['PHP_SELF']);

        $ngo_name = $_SESSION['name'] ?? 'NGO User';
        $ngo_role = $_SESSION['role'] ?? 'NGO';

        ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0">

            <title>
                <?= htmlspecialchars($page_title) ?> | FoodShare
            </title>

            <!-- NGO CSS -->
            <link
                rel="stylesheet"
                href="../assets/css/ngo.css">

            <!-- Font Awesome -->
            <link
                rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        </head>

        <body>

        <div class="ngo-app">

            <!-- ===================================================== -->
            <!-- SIDEBAR -->
            <!-- ===================================================== -->

            <aside class="ngo-sidebar" id="ngoSidebar">

                <!-- Logo -->

                <div class="ngo-logo">

                    <div class="ngo-logo-icon">
                        <i class="fa-solid fa-leaf"></i>
                    </div>

                    <div>
                        <h2>FoodShare</h2>
                        <span>NGO Portal</span>
                    </div>

                </div>


                <!-- NGO Profile -->

                <div class="ngo-user">

                    <div class="ngo-user-avatar">

                        <i class="fa-solid fa-building-ngo"></i>

                    </div>

                    <div class="ngo-user-info">

                        <strong>
                            <?= htmlspecialchars($ngo_name) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars($ngo_role) ?>
                        </span>

                    </div>

                </div>


                <!-- Navigation -->

                <nav class="ngo-navigation">

                    <p class="ngo-nav-title">
                        MAIN MENU
                    </p>


                    <!-- Dashboard -->

                    <a
                        href="dashboard.php"
                        class="ngo-nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">

                        <i class="fa-solid fa-chart-pie"></i>

                        <span>Dashboard</span>

                    </a>


                    <!-- Available Donations -->

                    <a
                        href="available-donations.php"
                        class="ngo-nav-link <?= $current_page === 'available-donations.php' ? 'active' : '' ?>">

                        <i class="fa-solid fa-bowl-food"></i>

                        <span>Available Donations</span>

                    </a>


                    <!-- My Donations -->

                    <a
                        href="my-donations.php"
                        class="ngo-nav-link <?= $current_page === 'my-donations.php' ? 'active' : '' ?>">

                        <i class="fa-solid fa-box-open"></i>

                        <span>My Donations</span>

                    </a>


                    <!-- Pickup Schedule -->

                    <a
                        href="pickup-schedule.php"
                        class="ngo-nav-link <?= $current_page === 'pickup-schedule.php' ? 'active' : '' ?>">

                        <i class="fa-solid fa-calendar-days"></i>

                        <span>Pickup Schedule</span>

                    </a>


                    <!-- Collection Status -->

                    <a
                        href="collection-status.php"
                        class="ngo-nav-link <?= $current_page === 'collection-status.php' ? 'active' : '' ?>">

                        <i class="fa-solid fa-truck"></i>

                        <span>Collection Status</span>

                    </a>


                    <!-- Distribution -->

                    <a
                        href="distribution.php"
                        class="ngo-nav-link <?= $current_page === 'distribution.php' ? 'active' : '' ?>">

                        <i class="fa-solid fa-hand-holding-heart"></i>

                        <span>Distribution</span>

                    </a>


                    <!-- Completed -->

                    <a
                        href="completed-donations.php"
                        class="ngo-nav-link <?= $current_page === 'completed-donations.php' ? 'active' : '' ?>">

                        <i class="fa-solid fa-circle-check"></i>

                        <span>Completed</span>

                    </a>


                    <p class="ngo-nav-title">
                        ACCOUNT
                    </p>


                    <!-- Notifications -->

                    <a
                        href="notifications.php"
                        class="ngo-nav-link <?= $current_page === 'notifications.php' ? 'active' : '' ?>">

                        <i class="fa-regular fa-bell"></i>

                        <span>Notifications</span>

                    </a>


                    <!-- History -->

                    <a
                        href="history.php"
                        class="ngo-nav-link <?= $current_page === 'history.php' ? 'active' : '' ?>">

                        <i class="fa-solid fa-clock-rotate-left"></i>

                        <span>History</span>

                    </a>


                    <!-- Feedback -->

                    <a
                        href="feedback.php"
                        class="ngo-nav-link <?= $current_page === 'feedback.php' ? 'active' : '' ?>">

                        <i class="fa-regular fa-comment-dots"></i>

                        <span>Feedback</span>

                    </a>


                    <!-- Profile -->

                    <a
                        href="profile.php"
                        class="ngo-nav-link <?= $current_page === 'profile.php' || $current_page === 'edit-profile.php' ? 'active' : '' ?>">

                        <i class="fa-regular fa-user"></i>

                        <span>Profile</span>

                    </a>


                    <!-- Logout -->

                    <a
                        href="../logout.php"
                        class="ngo-nav-link ngo-logout">

                        <i class="fa-solid fa-right-from-bracket"></i>

                        <span>Logout</span>

                    </a>

                </nav>

            </aside>


            <!-- ===================================================== -->
            <!-- MAIN -->
            <!-- ===================================================== -->

            <main class="ngo-main">


                <!-- TOP BAR -->

                <header class="ngo-topbar">

                    <div class="ngo-topbar-left">

                        <button
                            type="button"
                            class="ngo-menu-btn"
                            onclick="toggleNGOSidebar()">

                            <i class="fa-solid fa-bars"></i>

                        </button>

                        <div>

                            <span class="ngo-breadcrumb">
                                NGO Portal
                            </span>

                            <h3>
                                <?= htmlspecialchars($page_title) ?>
                            </h3>

                        </div>

                    </div>


                    <div class="ngo-topbar-right">

                        <!-- Notification -->

                        <a
                            href="notifications.php"
                            class="ngo-top-icon"
                            title="Notifications">

                            <i class="fa-regular fa-bell"></i>

                        </a>


                        <!-- Profile -->

                        <a
                            href="profile.php"
                            class="ngo-top-profile">

                            <div class="ngo-top-avatar">

                                <i class="fa-solid fa-building-ngo"></i>

                            </div>

                            <div>

                                <strong>
                                    <?= htmlspecialchars($ngo_name) ?>
                                </strong>

                                <small>
                                    NGO
                                </small>

                            </div>

                        </a>

                    </div>

                </header>


                <!-- PAGE CONTENT -->

                <div class="ngo-page">

        <?php
    }
}


/*
|--------------------------------------------------------------------------
| NGO Footer
|--------------------------------------------------------------------------
*/

if (!function_exists('ngo_footer')) {

    function ngo_footer()
    {
        ?>

                </div>
                <!-- /.ngo-page -->

            </main>
            <!-- /.ngo-main -->

        </div>
        <!-- /.ngo-app -->


        <script>

            function toggleNGOSidebar()
            {
                const sidebar =
                    document.getElementById('ngoSidebar');

                if (sidebar) {

                    sidebar.classList.toggle('show');

                }
            }


            /*
            |--------------------------------------------------------------------------
            | Close sidebar when clicking outside on mobile
            |--------------------------------------------------------------------------
            */

            document.addEventListener('click', function(event)
            {

                const sidebar =
                    document.getElementById('ngoSidebar');

                const menuButton =
                    document.querySelector('.ngo-menu-btn');


                if (
                    window.innerWidth <= 900 &&
                    sidebar &&
                    sidebar.classList.contains('show') &&
                    !sidebar.contains(event.target) &&
                    !menuButton.contains(event.target)
                ) {

                    sidebar.classList.remove('show');

                }

            });

        </script>


        </body>

        </html>

        <?php
    }
}
?>
