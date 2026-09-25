<?php

require_once "../config/database.php";
require_once "../config/constants.php";
require_once "../includes/role-check.php";


require_role("donor");


$message = "";
$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $food_name =
        trim($_POST["food_name"] ?? "");


    $description =
        trim($_POST["description"] ?? "");


    $quantity =
        (float)(
            $_POST["quantity"] ?? 0
        );


    $unit =
        trim($_POST["unit"] ?? "");


    $city =
        trim($_POST["city"] ?? "");


    $area =
        trim($_POST["area"] ?? "");


    $food_photo = "";


    /* PHOTO UPLOAD */

    if (
        isset($_FILES["food_photo"]) &&
        $_FILES["food_photo"]["error"]
        === UPLOAD_ERR_OK
    ) {

        $extension =
            strtolower(
                pathinfo(
                    $_FILES["food_photo"]["name"],
                    PATHINFO_EXTENSION
                )
            );


        $allowed_extensions = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];


        if (
            in_array(
                $extension,
                $allowed_extensions,
                true
            )
        ) {

            if (
                !is_dir(
                    UPLOAD_FOOD
                )
            ) {

                mkdir(
                    UPLOAD_FOOD,
                    0777,
                    true
                );

            }


            $food_photo =
                uniqid("food_")
                . "."
                . $extension;


            move_uploaded_file(

                $_FILES["food_photo"]["tmp_name"],

                UPLOAD_FOOD .
                $food_photo

            );

        }

    }


    if (
        $food_name === "" ||
        $quantity <= 0
    ) {

        $error =
            "Please enter food name and valid quantity.";

    } else {


        $stmt = $conn->prepare(

            "INSERT INTO food_donations
            (
                donor_id,
                food_name,
                description,
                quantity,
                unit,
                food_photo,
                city,
                area,
                delivery_preference
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, 'any')"

        );


        $stmt->bind_param(

            "issdssss",

            $_SESSION["user_id"],
            $food_name,
            $description,
            $quantity,
            $unit,
            $food_photo,
            $city,
            $area

        );


        if ($stmt->execute()) {

            $message =
                "Food donation posted successfully.";

        } else {

            $error =
                "Unable to post donation.";

        }

    }

}


$page_title =
    "Donate Food";

require_once "../includes/header.php";

?>


<div class="form">

    <h1>
        Donate Surplus Food
    </h1>


    <?php if ($message): ?>

        <div class="alert success">

            <?= e($message) ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert">

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        enctype="multipart/form-data"
    >


        <div class="group">

            <label>
                Food Name
            </label>

            <input
                type="text"
                name="food_name"
                placeholder="Example: Rice and Sambar"
                required
            >

        </div>


        <div class="group">

            <label>
                Description
            </label>

            <textarea
                name="description"
                placeholder="Describe the food"
            ></textarea>

        </div>


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


        <div class="group">

            <label>
                Unit
            </label>

            <input
                type="text"
                name="unit"
                placeholder="kg / plates / packets"
            >

        </div>


        <div class="group">

            <label>
                Food Photo
            </label>

            <input
                type="file"
                name="food_photo"
                accept=".jpg,.jpeg,.png,.webp"
            >

        </div>


        <div class="group">

            <label>
                City
            </label>

            <input
                type="text"
                name="city"
                placeholder="Example: Tumkur"
            >

        </div>


        <div class="group">

            <label>
                Area
            </label>

            <input
                type="text"
                name="area"
                placeholder="Example: Tumkur Town"
            >

        </div>


        <button type="submit">
            Post Food Donation
        </button>


    </form>

</div>


<?php

require_once "../includes/footer.php";

?>
