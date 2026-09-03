<?php
include("database/database.php");

$username = $_POST["username"];
$first_name = $_POST["first_name"];
$middle_name = $_POST["middle_name"];
$last_name = $_POST["last_name"];
$email = $_POST["email"];
$address = $_POST["address"];
$password = $_POST["password"];
$phone_number = $_POST["phone_number"];



$sql = "INSERT INTO `user`(`username`, `first_name`, `middle_name`, `last_name`, `email`, `address`, `password`, `phone_number`)
        VALUES('$username', '$first_name', '$middle_name', '$last_name', '$email', '$address', '$password', '$phone_number')";

try {
    mysqli_query($conn, $sql);
    }
    catch(mysqli_sql_exception ) {
        echo "couldnt register. try again";
}


mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <main class="register-page">

    <section class="register-container">

        <header class="register-header">
            <h1>Create Your Account</h1>

            <p>
                Join Mletchido Financial Group
            </p>
        </header>


        <form action="register.php" method="POST">

            <fieldset>

                <legend>Personal Information</legend>

                <div class="form-row">

                    <div class="form-group">
                        <label for="first_name">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            placeholder="First Name"
                            required
                        >
                    </div>


                    <div class="form-group">
                        <label for="middle_name">
                            Middle Name
                        </label>

                        <input
                            type="text"
                            id="middle_name"
                            name="middle_name"
                            placeholder="Middle Name"
                        >
                    </div>


                    <div class="form-group">
                        <label for="last_name">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            placeholder="Last Name"
                            required
                        >
                    </div>

                </div>

            </fieldset>


            <fieldset>

                <legend>Contact Information</legend>


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="example@email.com"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="phone_number">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone_number"
                        name="phone_number"
                        placeholder="+63 912 345 6789"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="address">
                        Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        placeholder="Country, House No., Street, Barangay, Municipality, Province"
                        required
                    ></textarea>

                </div>

            </fieldset>


            <fieldset>

                <legend>Account Information</legend>


                <div class="form-group">

                    <label for="username">
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Choose a username"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your password"
                        required
                    >

                </div>

            </fieldset>


            <div class="form-actions">

                <button type="submit">
                    Create Account
                </button>

            </div>


            <p>
                Already have an account?
                <a href="login.php">Log In</a>
            </p>

        </form>

    </section>

</main>
</body>
</html>