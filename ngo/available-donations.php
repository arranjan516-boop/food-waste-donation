<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$search = trim($_GET["search"] ?? "");

$sql = "
    SELECT
        fd.donation_id,
        fd.food_name,
        fd.food_category,
        fd.description,
        fd.quantity,
        fd.unit,
        fd.food_photo,
        fd.city,
        fd.area,
        fd.delivery_preference,
        u.name AS donor_name
    FROM food_donations fd
    LEFT JOIN users u
        ON fd.donor_id = u.user_id
    LEFT JOIN deliveries d
        ON fd.donation_id = d.donation_id
        AND (d.ngo_id IS NOT NULL OR d.collector_id IS NOT NULL)
    WHERE d.delivery_id IS NULL
";

if ($search !== "") {

    $sql .= "
        AND (
            fd.food_name LIKE ?
            OR fd.food_category LIKE ?
            OR fd.city LIKE ?
            OR fd.area LIKE ?
        )
    ";
}

$sql .= " ORDER BY fd.donation_id DESC";

if ($search !== "") {

    $stmt = $conn->prepare($sql);

    $like = "%" . $search . "%";

    $stmt->bind_param(
        "ssss",
        $like,
        $like,
        $like,
        $like
    );

    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query($sql);
}

$ngo_page_title = "Available Donations";
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Available Donations</title>

    <link rel="stylesheet" href="../assets/css/ngo.css">

</head>

<body class="ngo-body">

<?php include "../includes/ngo-layout.php"; ?>

<main class="ngo-content">

    <section class="ngo-section">

        <div class="ngo-section-header">

            <div>
                <h2>Available Food Donations</h2>

                <p style="color:#6b7280;font-size:13px;">
                    Find surplus food available for NGO collection.
                </p>
            </div>

        </div>


        <!-- SEARCH -->

        <form method="GET" style="margin-bottom:25px;">

            <div style="display:flex;gap:10px;">

                <input
                    class="ngo-input"
                    type="text"
                    name="search"
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search food, category, city or area..."
                >

                <button
                    type="submit"
                    class="ngo-btn ngo-btn-primary"
                >
                    🔍 Search
                </button>

            </div>

        </form>


        <?php if ($result && $result->num_rows > 0): ?>

            <div class="ngo-donation-grid">

                <?php while ($food = $result->fetch_assoc()): ?>

                    <div class="ngo-donation-card">

                        <?php if (!empty($food["food_photo"])): ?>

                            <img
                                class="ngo-food-image"
                                src="../uploads/food/<?php echo htmlspecialchars($food["food_photo"]); ?>"
                                alt="Food"
                            >

                        <?php else: ?>

                            <div class="ngo-food-placeholder">
                                🍱
                            </div>

                        <?php endif; ?>


                        <div class="ngo-donation-body">

                            <span class="ngo-category">
                                <?php echo htmlspecialchars($food["food_category"]); ?>
                            </span>

                            <h3>
                                <?php echo htmlspecialchars($food["food_name"]); ?>
                            </h3>


                            <div class="ngo-food-info">

                                <div class="ngo-info-item">

                                    <small>Quantity</small>

                                    <strong>
                                        <?php echo htmlspecialchars($food["quantity"]); ?>
                                        <?php echo htmlspecialchars($food["unit"]); ?>
                                    </strong>

                                </div>

                                <div class="ngo-info-item">

                                    <small>Location</small>

                                    <strong>
                                        <?php echo htmlspecialchars($food["city"]); ?>
                                    </strong>

                                </div>

                            </div>


                            <p style="font-size:12px;color:#6b7280;">

                                Donor:
                                <strong>
                                    <?php echo htmlspecialchars($food["donor_name"]); ?>
                                </strong>

                            </p>


                            <a
                                href="donation-details.php?id=<?php echo $food["donation_id"]; ?>"
                                class="ngo-btn ngo-btn-primary"
                                style="width:100%;"
                            >
                                View Details →
                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="ngo-empty">

                <div class="ngo-empty-icon">
                    🔍
                </div>

                <h3>No Donations Found</h3>

                <p>
                    Try another search or check again later.
                </p>

            </div>

        <?php endif; ?>

    </section>

</main>

</div>

</body>

</html>
