<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("collector");

$page_title = "Available Tasks";

$sql = "
    SELECT
        d.delivery_id,
        d.donation_id,
        d.request_id,
        d.method,
        d.status,

        fd.food_name,
        fd.food_category,
        fd.quantity,
        fd.unit,
        fd.city,
        fd.area,
        fd.food_photo,

        u.name AS donor_name

    FROM deliveries d

    INNER JOIN food_donations fd
        ON d.donation_id = fd.donation_id

    INNER JOIN users u
        ON fd.donor_id = u.user_id

    WHERE
        d.collector_id IS NULL
        AND d.status NOT IN ('completed', 'delivered', 'cancelled')

    ORDER BY d.created_at DESC
";

$result = $conn->query($sql);

require_once "../includes/header.php";
?>

<div class="container">

    <h1>Available Collection Tasks</h1>

    <p>
        These food donations are waiting for a collector.
    </p>


    <?php if (!$result): ?>

        <div class="alert error">
            Database error:
            <?= htmlspecialchars($conn->error) ?>
        </div>

    <?php elseif ($result->num_rows === 0): ?>

        <div class="alert">
            No collection tasks are currently available.
        </div>

    <?php else: ?>

        <div class="task-grid">

            <?php while ($task = $result->fetch_assoc()): ?>

                <div class="task-card">

                    <?php if (!empty($task["food_photo"])): ?>

                        <img
                            src="../uploads/food/<?= htmlspecialchars($task["food_photo"]) ?>"
                            alt="Food"
                            class="task-image"
                        >

                    <?php endif; ?>


                    <h2>
                        <?= htmlspecialchars($task["food_name"]) ?>
                    </h2>

                    <p>
                        <strong>Category:</strong>
                        <?= htmlspecialchars($task["food_category"]) ?>
                    </p>

                    <p>
                        <strong>Quantity:</strong>
                        <?= htmlspecialchars($task["quantity"]) ?>
                        <?= htmlspecialchars($task["unit"]) ?>
                    </p>

                    <p>
                        <strong>Donor:</strong>
                        <?= htmlspecialchars($task["donor_name"]) ?>
                    </p>

                    <p>
                        <strong>Location:</strong>
                        <?= htmlspecialchars($task["area"]) ?>,
                        <?= htmlspecialchars($task["city"]) ?>
                    </p>

                    <a
                        href="task-details.php?id=<?= (int)$task["delivery_id"] ?>"
                        class="btn"
                    >
                        View Task
                    </a>

                </div>

            <?php endwhile; ?>

        </div>

    <?php endif; ?>

</div>


<style>

.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

.task-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 25px;
}

.task-card {
    padding: 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 3px 12px rgba(0,0,0,.08);
}

.task-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 10px;
}

.task-card h2 {
    margin-top: 15px;
}

.task-card p {
    line-height: 1.5;
}

.btn {
    display: inline-block;
    margin-top: 10px;
    padding: 10px 18px;
    background: #333;
    color: white;
    text-decoration: none;
    border-radius: 8px;
}

@media(max-width:900px) {
    .task-grid {
        grid-template-columns: repeat(2,1fr);
    }
}

@media(max-width:600px) {
    .task-grid {
        grid-template-columns: 1fr;
    }
}

</style>

<?php require_once "../includes/footer.php"; ?>
