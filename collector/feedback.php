<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/functions.php";
require_once "../includes/role-check.php";

require_role("collector");

$page_title = "Feedback";

require_once "../includes/header.php";
?>

<div class="container">

    <h1>Feedback</h1>

    <div class="feedback-card">

        <p>
            Your feedback helps us improve the food donation system.
        </p>

        <form method="POST">

            <label>
                Rating
            </label>

            <select name="rating" required>
                <option value="">Select Rating</option>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Very Good</option>
                <option value="3">3 - Good</option>
                <option value="2">2 - Average</option>
                <option value="1">1 - Poor</option>
            </select>


            <label>
                Feedback
            </label>

            <textarea
                name="message"
                rows="6"
                placeholder="Write your feedback..."
                required
            ></textarea>


            <button type="submit">
                Submit Feedback
            </button>

        </form>

    </div>

</div>


<style>

.container {
    max-width: 700px;
    margin: 30px auto;
    padding: 20px;
}

.feedback-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 3px 12px rgba(0,0,0,.08);
}

label {
    display: block;
    margin-top: 20px;
    margin-bottom: 8px;
    font-weight: bold;
}

select,
textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
}

button {
    margin-top: 20px;
    padding: 12px 22px;
    background: #333;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

</style>

<?php require_once "../includes/footer.php"; ?>
