<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/role-check.php";

require_role("donor");

$error = "";
$success = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $food_name = trim($_POST["food_name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $quantity = (float)($_POST["quantity"] ?? 0);
    $unit = trim($_POST["unit"] ?? "");
    $food_type = trim($_POST["food_type"] ?? "");
    $people_served = (int)($_POST["people_served"] ?? 0);

    $city = trim($_POST["city"] ?? "");
    $area = trim($_POST["area"] ?? "");
    $pincode = trim($_POST["pincode"] ?? "");

    $delivery_preference =
        $_POST["delivery_preference"] ?? "collector";


    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($food_name === "") {

        $error = "Please enter food name.";

    } elseif ($quantity <= 0) {

        $error = "Please enter a valid quantity.";

    } elseif ($unit === "") {

        $error = "Please select quantity unit.";

    } elseif (!isset($_FILES["food_photo"])) {

        $error = "Please select a food image.";

    } elseif (
        $_FILES["food_photo"]["error"]
        !== UPLOAD_ERR_OK
    ) {

        $error = "There was a problem uploading the image.";

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE UPLOAD
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $file = $_FILES["food_photo"];

        $file_name = $file["name"];
        $file_tmp = $file["tmp_name"];
        $file_size = $file["size"];


        /*
        | Get extension
        */

        $extension =
            strtolower(
                pathinfo(
                    $file_name,
                    PATHINFO_EXTENSION
                )
            );


        /*
        | Allowed extensions
        */

        $allowed_extensions = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];


        if (
            !in_array(
                $extension,
                $allowed_extensions
            )
        ) {

            $error =
                "Only JPG, JPEG, PNG and WEBP images are allowed.";

        }


        /*
        | Maximum size = 5 MB
        */

        elseif ($file_size > 5 * 1024 * 1024) {

            $error =
                "Image size must be less than 5 MB.";

        }


        /*
        | Check actual image
        */

        elseif (
            getimagesize($file_tmp) === false
        ) {

            $error =
                "The selected file is not a valid image.";

        }


        /*
        |--------------------------------------------------------------------------
        | SAVE IMAGE
        |--------------------------------------------------------------------------
        */

        if ($error === "") {

            $upload_directory =
                dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . "uploads"
                . DIRECTORY_SEPARATOR
                . "food"
                . DIRECTORY_SEPARATOR;


            /*
            | Create folder if missing
            */

            if (
                !is_dir($upload_directory)
            ) {

                mkdir(
                    $upload_directory,
                    0777,
                    true
                );

            }


            /*
            | Generate unique filename
            */

            $new_filename =
                "food_"
                . time()
                . "_"
                . bin2hex(
                    random_bytes(4)
                )
                . "."
                . $extension;


            $destination =
                $upload_directory
                . $new_filename;


            /*
            | Move uploaded image
            */

            if (
                !move_uploaded_file(
                    $file_tmp,
                    $destination
                )
            ) {

                $error =
                    "Unable to save the uploaded image.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT FOOD
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $stmt = $conn->prepare("

            INSERT INTO food_donations
            (
                donor_id,
                food_name,
                food_category,,
                description,
                quantity,
                unit,
                food_type,
                people_served,
                food_photo,
                city,
                area,
                pincode,
                delivery_preference,
                status,
                created_at
            )

            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, 'available', NOW()
            )

        ");


        $stmt->bind_param(

            "isssdssisssss",

            $_SESSION["user_id"],
            $food_name,
            $category,
            $description,
            $quantity,
            $unit,
            $food_type,
            $people_served,
            $new_filename,
            $city,
            $area,
            $pincode,
            $delivery_preference

        );


        if ($stmt->execute()) {

            $success =
                "Food donation added successfully.";

        } else {

            /*
            | If database insert fails,
            | remove uploaded image.
            */

            if (
                file_exists($destination)
            ) {

                unlink($destination);

            }

            $error =
                "Food donation could not be added.";

        }

    }

}


$page_title = "Donate Food";

require_once "../includes/header.php";

?>


<div class="form">

    <h1>
        🍱 Donate Food
    </h1>


    <?php if ($error !== ""): ?>

        <div class="alert">

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <?php if ($success !== ""): ?>

        <div class="alert success">

            <?= e($success) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        enctype="multipart/form-data"
    >


        <!-- FOOD NAME -->

        <div class="group">

            <label>
                Food Name
            </label>

            <input
                type="text"
                name="food_name"
                placeholder="Example: Vegetable Rice"
                required
            >

        </div>


        <!-- CATEGORY -->

        <div class="group">

            <label>
                Food Category
            </label>

            <select
                name="category"
            >

                <option value="">
                    Select Category
                </option>

                <option value="Rice">
                    Rice
                </option>

                <option value="Meals">
                    Meals
                </option>

                <option value="Snacks">
                    Snacks
                </option>

                <option value="Fruits">
                    Fruits
                </option>

                <option value="Vegetables">
                    Vegetables
                </option>

                <option value="Bakery">
                    Bakery
                </option>

                <option value="Other">
                    Other
                </option>

            </select>

        </div>


        <!-- DESCRIPTION -->

        <div class="group">

            <label>
                Description
            </label>

            <textarea
                name="description"
                placeholder="Describe the food..."
            ></textarea>

        </div>


        <!-- QUANTITY -->

        <div class="group">

            <label>
                Quantity
            </label>

            <input
                type="number"
                name="quantity"
                step="0.01"
                min="0.01"
                required
            >

        </div>


        <!-- UNIT -->

        <div class="group">

            <label>
                Unit
            </label>

            <select
                name="unit"
                required
            >

                <option value="">
                    Select Unit
                </option>

                <option value="kg">
                    Kilogram (kg)
                </option>

                <option value="litre">
                    Litre
                </option>

                <option value="packets">
                    Packets
                </option>

                <option value="plates">
                    Plates
                </option>

                <option value="pieces">
                    Pieces
                </option>

            </select>

        </div>


        <!-- FOOD TYPE -->

        <div class="group">

            <label>
                Food Type
            </label>

            <select
                name="food_type"
            >

                <option value="">
                    Select Food Type
                </option>

                <option value="Vegetarian">
                    Vegetarian
                </option>

                <option value="Non-Vegetarian">
                    Non-Vegetarian
                </option>

            </select>

        </div>


        <!-- PEOPLE SERVED -->

        <div class="group">

            <label>
                Approximate People Served
            </label>

            <input
                type="number"
                name="people_served"
                min="1"
            >

        </div>


        <!-- FOOD IMAGE -->

        <div class="group">

            <label>
                Food Photo
            </label>

            <input
                type="file"
                name="food_photo"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                required
            >

            <small>
                JPG, JPEG, PNG or WEBP — maximum 5 MB
            </small>

        </div>


        <!-- CITY -->

        <div class="group">

            <label>
                City
            </label>

            <input
                type="text"
                name="city"
                placeholder="Example: Tumkur"
                required
            >

        </div>


        <!-- AREA -->

        <div class="group">

            <label>
                Area
            </label>

            <input
                type="text"
                name="area"
                placeholder="Example: Tumkur Town"
                required
            >

        </div>


        <!-- PINCODE -->

        <div class="group">

            <label>
                Pincode
            </label>

            <input
                type="text"
                name="pincode"
                maxlength="6"
            >

        </div>


        <!-- DELIVERY -->

        <div class="group">

            <label>
                Delivery Preference
            </label>

            <select
                name="delivery_preference"
                required
            >

                <option value="self_delivery">
                    I can deliver myself
                </option>

                <option value="collector">
                    Need a Collector
                </option>

                <option value="ngo">
                    Need an NGO
                </option>

            </select>

        </div>


        <button type="submit">
            Donate Food
        </button>


    </form>

</div>


<?php

require_once "../includes/footer.php";

?>
