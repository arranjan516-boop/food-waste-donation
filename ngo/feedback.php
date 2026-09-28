<?php
require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("ngo");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $message = "Thank you. Your feedback has been received.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Feedback</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container">

    <h1>Feedback</h1>

    <?php if ($message): ?>

        <p>
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <label>Rating</label>

        <br>

        <select name="rating" required>

            <option value="">Select Rating</option>
            <option value="5">5 - Excellent</option>
            <option value="4">4 - Very Good</option>
            <option value="3">3 - Good</option>
            <option value="2">2 - Average</option>
            <option value="1">1 - Poor</option>

        </select>

        <br><br>

        <label>Your Feedback</label>

        <br>

        <textarea
            name="feedback"
            rows="6"
            cols="50"
            required
            placeholder="Write your feedback"
        ></textarea>

        <br><br>

        <button type="submit">
            Submit Feedback
        </button>

    </form>

</div>

</body>
</html>
