<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/role-check.php";

require_role("donor");

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* ---------------------------------------
       Get form data
    --------------------------------------- */

    $donor_id = $_SESSION["user_id"];

    $food_name = trim($_POST["food_name"] ?? "");
    $food_category = trim($_POST["food_category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $quantity = (float)($_POST["quantity"] ?? 0);
    $unit = trim($_POST["unit"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $area = trim($_POST["area"] ?? "");
    $delivery_preference = trim($_POST["delivery_preference"] ?? "any");

    /* ---------------------------------------
       Validation
    --------------------------------------- */

    if ($food_name === "") {
        $error = "Please enter food name.";
    } elseif ($quantity <= 0) {
        $error = "Please enter a valid quantity.";
    }

    /* ---------------------------------------
       Food image upload
    --------------------------------------- */

    $food_photo = "";

    if ($error === "" && isset($_FILES["food_photo"])) {

        if ($_FILES["food_photo"]["error"] === UPLOAD_ERR_OK) {

            $original_name = $_FILES["food_photo"]["name"];
            $tmp_name = $_FILES["food_photo"]["tmp_name"];
            $file_size = $_FILES["food_photo"]["size"];

            $extension = strtolower(
                pathinfo($original_name, PATHINFO_EXTENSION)
            );

            $allowed_extensions = [
                "jpg",
                "jpeg",
                "png",
                "webp"
            ];

            /* Maximum 5 MB */
            if ($file_size > 5 * 1024 * 1024) {

                $error = "Image size must be less than 5 MB.";

            } elseif (!in_array($extension, $allowed_extensions, true)) {

                $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

            } elseif (getimagesize($tmp_name) === false) {

                $error = "The uploaded file is not a valid image.";

            } else {

                /* ---------------------------------------
                   Create upload folder
                --------------------------------------- */

                $upload_folder = __DIR__ . "/../uploads/food/";

                if (!is_dir($upload_folder)) {
                    mkdir($upload_folder, 0777, true);
                }

                /* ---------------------------------------
                   Create unique image name
                --------------------------------------- */

                $food_photo =
                    "food_" .
                    time() .
                    "_" .
                    bin2hex(random_bytes(5)) .
                    "." .
                    $extension;

                $destination = $upload_folder . $food_photo;

                if (!move_uploaded_file($tmp_name, $destination)) {

                    $error = "Failed to upload food image.";

                    $food_photo = "";
                }
            }
        }
    }

    /* ---------------------------------------
       Insert donation
       
       IMPORTANT:
       Database uses food_category,
       NOT category.
    --------------------------------------- */

    if ($error === "") {

        $sql = "
            INSERT INTO food_donations
            (
                donor_id,
                food_name,
                food_category,
                description,
                quantity,
                unit,
                food_photo,
                city,
                area,
                delivery_preference
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param(
                "isssdsssss",
                $donor_id,
                $food_name,
                $food_category,
                $description,
                $quantity,
                $unit,
                $food_photo,
                $city,
                $area,
                $delivery_preference
            );

            if ($stmt->execute()) {

                $message = "Food donation posted successfully!";

                /* Clear form values */
                $food_name = "";
                $food_category = "";
                $description = "";
                $quantity = "";
                $unit = "";
                $city = "";
                $area = "";

            } else {

                /* Delete uploaded image if database insert fails */

                if ($food_photo !== "") {

                    $uploaded_file =
                        __DIR__ .
                        "/../uploads/food/" .
                        $food_photo;

                    if (file_exists($uploaded_file)) {
                        unlink($uploaded_file);
                    }
                }

                $error = "Failed to save donation: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

$page_title = "Donate Food";

require_once "../includes/header.php";

?>

<div class="form">

    <h1>Donate Food</h1>

    <?php if ($message !== ""): ?>

        <div class="alert success">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <!-- Food Name -->

        <div class="group">

            <label for="food_name">
                Food Name
            </label>

            <input
                type="text"
                id="food_name"
                name="food_name"
                value="<?= htmlspecialchars($food_name ?? "") ?>"
                placeholder="Example: Rice, Biryani, Idli"
                required
            >

        </div>


        <!-- Food Category -->

        <div class="group">

            <label for="food_category">
                Food Category
            </label>

            <select
                id="food_category"
                name="food_category"
            >

                <option value="">
                    Select Category
                </option>

                <option value="Rice"
                    <?= (($food_category ?? "") === "Rice") ? "selected" : "" ?>>
                    Rice
                </option>

                <option value="Biryani"
                    <?= (($food_category ?? "") === "Biryani") ? "selected" : "" ?>>
                    Biryani
                </option>

                <option value="Meals"
                    <?= (($food_category ?? "") === "Meals") ? "selected" : "" ?>>
                    Meals
                </option>

                <option value="Breakfast"
                    <?= (($food_category ?? "") === "Breakfast") ? "selected" : "" ?>>
                    Breakfast
                </option>

                <option value="Snacks"
                    <?= (($food_category ?? "") === "Snacks") ? "selected" : "" ?>>
                    Snacks
                </option>

                <option value="Fruits"
                    <?= (($food_category ?? "") === "Fruits") ? "selected" : "" ?>>
                    Fruits
                </option>

                <option value="Vegetables"
                    <?= (($food_category ?? "") === "Vegetables") ? "selected" : "" ?>>
                    Vegetables
                </option>

                <option value="Other"
                    <?= (($food_category ?? "") === "Other") ? "selected" : "" ?>>
                    Other
                </option>

            </select>

        </div>


        <!-- Description -->

        <div class="group">

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="4"
                placeholder="Describe the food..."
            ><?= htmlspecialchars($description ?? "") ?></textarea>

        </div>


        <!-- Quantity -->

        <div class="group">

            <label for="quantity">
                Quantity
            </label>

            <input
                type="number"
                id="quantity"
                name="quantity"
                step="0.01"
                min="0.01"
                value="<?= htmlspecialchars($quantity ?? "") ?>"
                placeholder="Example: 20"
                required
            >

        </div>


        <!-- Unit -->

        <div class="group">

            <label for="unit">
                Unit
            </label>

            <select
                id="unit"
                name="unit"
            >

                <option value="">
                    Select Unit
                </option>

                <option value="kg">Kilograms (kg)</option>
                <option value="plates">Plates</option>
                <option value="packets">Packets</option>
                <option value="pieces">Pieces</option>
                <option value="litres">Litres</option>

            </select>

        </div>


        <!-- Food Photo -->

        <div class="group">

            <label for="food_photo">
                Food Photo
            </label>

            <input
                type="file"
                id="food_photo"
                name="food_photo"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            >

            <small>
                JPG, JPEG, PNG or WEBP. Maximum 5 MB.
            </small>

        </div>


        <!-- City -->

        <div class="group">

            <label for="city">
                City
            </label>

            <input
                type="text"
                id="city"
                name="city"
                value="<?= htmlspecialchars($city ?? "") ?>"
                placeholder="Example: Tumkur"
            >

        </div>


        <!-- Area -->

        <div class="group">

            <label for="area">
                Area
            </label>

            <input
                type="text"
                id="area"
                name="area"
                value="<?= htmlspecialchars($area ?? "") ?>"
                placeholder="Example: SIT"
            >

        </div>


        <!-- Delivery Preference -->

        <div class="group">

            <label for="delivery_preference">
                Delivery Preference
            </label>

            <select
                id="delivery_preference"
                name="delivery_preference"
            >

                <option value="any">
                    Any Available Method
                </option>

                <option value="self">
                    I can deliver the food myself
                </option>

                <option value="collector">
                    Need a Collector
                </option>

                <option value="ngo">
                    Need NGO / Organization
                </option>

            </select>

        </div>


        <!-- Submit -->

        <button type="submit">
            Donate Food
        </button>

    </form>

</div>


<?php

require_once "../includes/footer.php";

?>
