
<?php
session_start();

// =====================================
// DATABASE CONNECTION
// =====================================

$conn = new mysqli(
    "127.0.0.1",
    "root",
    "Mahi@0211",
    "department_db",
    3306
);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$message = "";
$messageType = "";

$page = $_GET["page"] ?? "home";

$allowedPages = ["home", "about", "register", "login"];

if (!in_array($page, $allowedPages, true)) {
    $page = "home";
}

// =====================================
// LOGOUT
// =====================================

if (isset($_GET["logout"])) {
    session_unset();
    session_destroy();

    header("Location: index.php");
    exit;
}

// =====================================
// REGISTRATION
// =====================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {

    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    $errors = [];

    if (!preg_match("/^[a-zA-Z ]{2,100}$/", $name)) {
        $errors[] = "Enter a valid name using alphabets only.";
    }

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150
    ) {
        $errors[] = "Enter a valid email address.";
    }

    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Phone number must contain exactly 10 digits.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must contain at least 8 characters.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {

        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();

        $existingUser = $check->get_result();

        if ($existingUser->num_rows > 0) {

            $errors[] = "Email address is already registered.";

        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (full_name, email, phone, password)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $phone,
                $hashedPassword
            );

            if ($stmt->execute()) {

                header("Location: index.php?page=login&registered=1");
                exit;

            } else {

                $errors[] = "Registration failed. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }

    if (!empty($errors)) {
        $message = implode(" ", $errors);
        $messageType = "error";
        $page = "register";
    }
}

// =====================================
// LOGIN
// =====================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login"])) {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {

        $message = "Please enter a valid email and password.";
        $messageType = "error";
        $page = "login";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password FROM users WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["full_name"];
                $_SESSION["user_email"] = $user["email"];

                header("Location: index.php?login=success");
                exit;

            } else {

                $message = "Incorrect email or password.";
                $messageType = "error";
                $page = "login";
            }

        } else {

            $message = "Incorrect email or password.";
            $messageType = "error";
            $page = "login";
        }

        $stmt->close();
    }
}

// =====================================
// LOGIN / REGISTRATION NOTIFICATIONS
// =====================================

if (isset($_GET["registered"])) {
    $message = "Registration successful! Please login.";
    $messageType = "success";
    $page = "login";
}

if (isset($_GET["login"]) && $_GET["login"] === "success") {
    $message = "Welcome! Login successful.";
    $messageType = "success";
}

// =====================================
// FETCH TOTAL REGISTERED USERS
// =====================================

$countResult = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $countResult->fetch_assoc()["total"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Department Website</title>

<style>

/* GENERAL */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f3f6fb;
    color: #263445;
}

/* NAVBAR */

.navbar {
    background: #142f59;
    color: white;
    padding: 18px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.logo {
    font-size: 23px;
    font-weight: bold;
}

.logo span {
    color: #50b5f9;
}

.nav-links {
    display: flex;
    gap: 22px;
    align-items: center;
    flex-wrap: wrap;
}

.nav-links a {
    color: white;
    text-decoration: none;
    font-size: 15px;
    transition: 0.3s;
}

.nav-links a:hover {
    color: #50b5f9;
}

.nav-links .register-link {
    background: #1685d5;
    padding: 10px 17px;
    border-radius: 5px;
}

/* HERO */

.hero {
    min-height: 430px;
    background: linear-gradient(120deg, #142f59, #1769aa);
    color: white;
    display: flex;
    align-items: center;
    padding: 60px 8%;
}

.hero-content {
    max-width: 750px;
}

.hero h1 {
    font-size: 43px;
    margin-bottom: 20px;
    line-height: 1.2;
}

.hero p {
    font-size: 17px;
    line-height: 1.8;
    color: #e1edfa;
    margin-bottom: 25px;
}

.primary-btn {
    display: inline-block;
    background: #ffffff;
    color: #173b70;
    padding: 13px 23px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
}

.primary-btn:hover {
    background: #dcecff;
}

/* SECTION */

.section {
    padding: 55px 8%;
    text-align: center;
}

.section h2 {
    color: #173b70;
    font-size: 30px;
    margin-bottom: 15px;
}

.section-description {
    color: #64748b;
    line-height: 1.7;
    max-width: 750px;
    margin: auto;
}

/* CARDS */

.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
    margin-top: 35px;
}

.card {
    background: white;
    padding: 28px 22px;
    border-radius: 10px;
    box-shadow: 0 4px 15px #0000000c;
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
}

.card-icon {
    font-size: 38px;
    margin-bottom: 15px;
}

.card h3 {
    color: #1769aa;
    margin-bottom: 12px;
}

.card p {
    color: #64748b;
    line-height: 1.7;
}

/* ABOUT */

.about-box {
    background: white;
    max-width: 900px;
    margin: auto;
    padding: 35px;
    border-radius: 10px;
    box-shadow: 0 4px 15px #0000000c;
    text-align: left;
    line-height: 1.8;
}

.about-box h3 {
    color: #1769aa;
    margin: 20px 0 8px;
}

/* FORM */

.form-container {
    max-width: 480px;
    margin: 55px auto;
    background: white;
    padding: 35px;
    border-radius: 12px;
    box-shadow: 0 5px 20px #00000012;
}

.form-container h2 {
    text-align: center;
    color: #173b70;
    margin-bottom: 10px;
}

.form-subtitle {
    text-align: center;
    color: #777;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 17px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.form-group input {
    width: 100%;
    padding: 13px;
    border: 1px solid #ccd5df;
    border-radius: 6px;
    font-size: 15px;
}

.form-group input:focus {
    outline: none;
    border-color: #1685d5;
    box-shadow: 0 0 4px #1685d555;
}

.form-button {
    width: 100%;
    padding: 14px;
    background: #1769aa;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}

.form-button:hover {
    background: #104d80;
}

.form-bottom {
    text-align: center;
    margin-top: 20px;
    color: #64748b;
}

.form-bottom a {
    color: #1769aa;
    text-decoration: none;
    font-weight: bold;
}

/* MESSAGES */

.message {
    padding: 13px;
    border-radius: 6px;
    margin-bottom: 20px;
    line-height: 1.5;
}

.success {
    background: #d9f5e3;
    color: #146c36;
}

.error {
    background: #ffe0e0;
    color: #a32121;
}

/* USER DASHBOARD */

.welcome-box {
    background: white;
    max-width: 850px;
    padding: 35px;
    margin: 45px auto;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px #0000000c;
}

.welcome-box h2 {
    color: #1769aa;
    margin-bottom: 15px;
}

/* FOOTER */

footer {
    background: #142f59;
    color: white;
    text-align: center;
    padding: 25px;
    margin-top: 35px;
    line-height: 1.8;
}

/* RESPONSIVE */

@media(max-width: 750px) {

    .navbar {
        flex-direction: column;
    }

    .nav-links {
        justify-content: center;
        gap: 14px;
    }

    .hero h1 {
        font-size: 32px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    .form-container {
        margin: 30px 15px;
        padding: 25px;
    }

}

</style>

</head>

<body>


<!-- NAVIGATION -->

<nav class="navbar">

    <div class="logo">
        CS <span>Department</span>
    </div>

    <div class="nav-links">

        <a href="index.php">Home</a>

        <a href="index.php?page=about">About Us</a>

        <?php if (isset($_SESSION["user_id"])): ?>

            <a href="index.php?logout=1">Logout</a>

        <?php else: ?>

            <a href="index.php?page=login">Login</a>

            <a href="index.php?page=register" class="register-link">
                Register
            </a>

        <?php endif; ?>

    </div>

</nav>


<?php if ($message !== ""): ?>

    <div style="max-width:850px;margin:20px auto;padding:0 15px;">

        <div class="message <?php echo $messageType; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    </div>

<?php endif; ?>


<?php if ($page === "home"): ?>


    <!-- HOME PAGE -->

    <section class="hero">

        <div class="hero-content">

            <h1>Welcome to Computer Science Department</h1>

            <p>
                Explore the world of technology, programming,
                innovation and digital learning.
                Our department focuses on developing technical
                knowledge, practical skills and professional excellence.
            </p>

            <a href="index.php?page=about" class="primary-btn">
                Explore Department
            </a>

        </div>

    </section>


    <section class="section">

        <h2>Our Department Facilities</h2>

        <p class="section-description">
            We provide quality education and modern facilities
            to develop the knowledge and skills of students.
        </p>

        <div class="cards">

            <div class="card">

                <div class="card-icon">💻</div>

                <h3>Computer Labs</h3>

                <p>
                    Modern computer laboratories with
                    practical programming facilities.
                </p>

            </div>


            <div class="card">

                <div class="card-icon">📚</div>

                <h3>Learning Resources</h3>

                <p>
                    Educational resources and technical
                    learning materials for students.
                </p>

            </div>


            <div class="card">

                <div class="card-icon">🎓</div>

                <h3>Career Development</h3>

                <p>
                    Skill development and professional
                    training for future careers.
                </p>

            </div>

        </div>

    </section>


    <section class="section" style="background:#e5effb;">

        <h2>Student Registration</h2>

        <p class="section-description">
            Create your account and become part of our
            department community.
        </p>

        <br>

        <a href="index.php?page=register" class="primary-btn"
           style="background:#1769aa;color:white;">

            Register Now

        </a>

    </section>


<?php elseif ($page === "about"): ?>


    <!-- ABOUT US PAGE -->

    <section class="section">

        <h2>About Our Department</h2>

        <p class="section-description">
            Learn more about our Computer Science Department.
        </p>

        <br>

        <div class="about-box">

            <h3>Who We Are</h3>

            <p>
                The Computer Science Department aims to provide
                quality education in computer science and information
                technology. We encourage students to develop
                programming skills, logical thinking and creativity.
            </p>


            <h3>Our Vision</h3>

            <p>
                To become a centre of excellence in computer science
                education and technological innovation.
            </p>


            <h3>Our Mission</h3>

            <p>
                To provide effective learning opportunities,
                practical knowledge and professional skills
                to prepare students for the digital world.
            </p>


            <h3>Our Objectives</h3>

            <p>
                • Develop programming and problem-solving skills.<br>
                • Encourage technical innovation.<br>
                • Improve practical and communication skills.<br>
                • Prepare students for career opportunities.
            </p>

        </div>

    </section>


<?php elseif ($page === "register"): ?>


    <!-- REGISTRATION PAGE -->

    <div class="form-container">

        <h2>Create Account</h2>

        <p class="form-subtitle">
            Register to join our department community
        </p>


        <form method="POST" action="index.php?page=register"
              id="registerForm">


            <div class="form-group">

                <label>Full Name</label>

                <input
                    type="text"
                    name="full_name"
                    placeholder="Enter your full name"
                    pattern="[A-Za-z ]{2,100}"
                    minlength="2"
                    maxlength="100"
                    title="Use alphabets and spaces only"
                    required
                >

            </div>


            <div class="form-group">

                <label>Email Address</label>

                <input
                    type="email"
                    name="email"
                    placeholder="example@gmail.com"
                    maxlength="150"
                    required
                >

            </div>


            <div class="form-group">

                <label>Phone Number</label>

                <input
                    type="tel"
                    name="phone"
                    placeholder="Enter 10 digit phone number"
                    pattern="[0-9]{10}"
                    maxlength="10"
                    minlength="10"
                    inputmode="numeric"
                    required
                >

            </div>


            <div class="form-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Minimum 8 characters"
                    minlength="8"
                    required
                >

            </div>


            <div class="form-group">

                <label>Confirm Password</label>

                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    placeholder="Re-enter password"
                    minlength="8"
                    required
                >

            </div>


            <button type="submit" name="register" class="form-button">

                Register

            </button>


        </form>


        <div class="form-bottom">

            Already have an account?

            <a href="index.php?page=login">Login</a>

        </div>

    </div>


<?php elseif ($page === "login"): ?>


    <!-- LOGIN PAGE -->

    <div class="form-container">

        <h2>User Login</h2>

        <p class="form-subtitle">
            Login to access your account
        </p>


        <form method="POST" action="index.php?page=login">


            <div class="form-group">

                <label>Email Address</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

            </div>


            <div class="form-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>


            <button type="submit" name="login" class="form-button">

                Login

            </button>


        </form>


        <div class="form-bottom">

            Don't have an account?

            <a href="index.php?page=register">Register</a>

        </div>

    </div>


<?php endif; ?>


<?php if (isset($_SESSION["user_id"])): ?>

    <div class="welcome-box">

        <h2>
            Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!
        </h2>

        <p>
            You are successfully logged in to the Department Website.
        </p>

        <br>

        <p>
            Registered Email:
            <?php echo htmlspecialchars($_SESSION["user_email"]); ?>
        </p>

    </div>

<?php endif; ?>


<!-- FOOTER -->

<footer>

    <h3>Computer Science Department</h3>

    <p>
        Learning Today | Leading Tomorrow
    </p>

    <p>
        © 2026 Computer Science Department.
        All Rights Reserved.
    </p>

</footer>


<!-- JAVASCRIPT VALIDATION -->

<script>

const registerForm = document.getElementById("registerForm");

if (registerForm) {

    const nameInput = document.querySelector('[name="full_name"]');

    const phoneInput = document.querySelector('[name="phone"]');

    const passwordInput = document.getElementById("password");

    const confirmPasswordInput =
        document.getElementById("confirm_password");


    // Name: Alphabets only

    nameInput.addEventListener("input", function() {

        this.value = this.value.replace(/[^a-zA-Z ]/g, "");

    });


    // Phone: Exactly 10 digits

    phoneInput.addEventListener("input", function() {

        this.value = this.value.replace(/[^0-9]/g, "").slice(0, 10);

    });


    // Confirm Password

    registerForm.addEventListener("submit", function(event) {

        if (passwordInput.value !== confirmPasswordInput.value) {

            event.preventDefault();

            alert("Password and Confirm Password do not match!");

            confirmPasswordInput.focus();

        }

    });

}

</script>


</body>

</html>

<?php
$conn->close();
?>
