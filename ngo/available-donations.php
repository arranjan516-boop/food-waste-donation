<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

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
    ORDER BY fd.donation_id DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Available Donations</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/food.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>Available Donations</h1>

    <p>
        Food donations available for NGO collection.
    </p>

    <?php if ($result && $result->num_rows > 0): ?>

        <div class="food-grid">

            <?php while ($food = $result->fetch_assoc()): ?>

                <div class="food-card">

                    <?php if (!empty($food["food_photo"])): ?>

                        <img
                            src="../uploads/food/<?php echo htmlspecialchars($food["food_photo"]); ?>"
                            alt="Food Image"
                            class="food-image"
                        >

                    <?php else: ?>

                        <img
                            src="../assets/images/default-food.jpg"
                            alt="Food Image"
                            class="food-image"
                        >

                    <?php endif; ?>

                    <h2>
                        <?php echo htmlspecialchars($food["food_name"]); ?>
                    </h2>

                    <p>
                        <strong>Category:</strong>
                        <?php echo htmlspecialchars($food["food_category"]); ?>
                    </p>

                    <p>
                        <strong>Quantity:</strong>
                        <?php echo htmlspecialchars($food["quantity"]); ?>
                        <?php echo htmlspecialchars($food["unit"]); ?>
                    </p>

                    <p>
                        <strong>Location:</strong>
                        <?php echo htmlspecialchars($food["city"]); ?>,
                        <?php echo htmlspecialchars($food["area"]); ?>
                    </p>

                    <p>
                        <strong>Donor:</strong>
                        <?php echo htmlspecialchars($food["donor_name"]); ?>
                    </p>

                    <a href="donation-details.php?id=<?php echo $food["donation_id"]; ?>">
                        View Details
                    </a>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <p>No donations are currently available.</p>

    <?php endif; ?>

</div>

</body>
</html>
