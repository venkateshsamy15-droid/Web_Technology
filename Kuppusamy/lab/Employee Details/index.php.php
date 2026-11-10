
<?php
$conn = new mysqli(
    "sql209.infinityfree.com",
    "if0_43401511",
    "LocL5inG2GJEZ",
    "if0_43401511_employee_db"
);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");

$message = "";
$messageType = "";


// =====================================
// INSERT EMPLOYEE
// =====================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_employee"])) {

    $name = trim($_POST["employee_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $gender = $_POST["gender"] ?? "";
    $department = $_POST["department"] ?? "";
    $designation = trim($_POST["designation"] ?? "");
    $salary = trim($_POST["salary"] ?? "");
    $joiningDate = $_POST["joining_date"] ?? "";

    $errors = [];

    // Employee Name Validation
    if (!preg_match("/^[a-zA-Z ]{2,100}$/", $name)) {
        $errors[] = "Name must contain alphabets only and minimum 2 characters.";
    }

    // Email Validation
    if (
        strlen($email) > 150 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        preg_match('/[\s<>]/', $email)
    ) {
        $errors[] = "Please enter a valid email address.";
    }

    // Phone Validation - Exactly 10 Digits
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Phone number must contain exactly 10 digits.";
    }

    // Gender Validation
    if (!in_array($gender, ["Male", "Female", "Other"], true)) {
        $errors[] = "Please select a valid gender.";
    }

    // Department Validation
    $allowedDepartments = [
        "IT",
        "HR",
        "Finance",
        "Marketing",
        "Sales",
        "Management"
    ];

    if (!in_array($department, $allowedDepartments, true)) {
        $errors[] = "Please select a valid department.";
    }

    // Designation Validation
    if (!preg_match("/^[a-zA-Z ]{2,100}$/", $designation)) {
        $errors[] = "Designation must contain alphabets only.";
    }

    // Salary Validation
    if (
        $salary === "" ||
        !is_numeric($salary) ||
        !is_finite((float)$salary) ||
        (float)$salary < 0 ||
        (float)$salary > 99999999.99
    ) {
        $errors[] = "Please enter a valid salary.";
    }

    // Joining Date Validation
    $dateObject = DateTime::createFromFormat("!Y-m-d", $joiningDate);

    if (
        !$dateObject ||
        $dateObject->format("Y-m-d") !== $joiningDate ||
        $joiningDate > date("Y-m-d")
    ) {
        $errors[] = "Please select a valid joining date. Future dates are not allowed.";
    }

    // If validation fails
    if (!empty($errors)) {

        $message = implode(" ", $errors);
        $messageType = "error";

    } else {

        // Insert into database
        $stmt = $conn->prepare(
            "INSERT INTO employees
            (employee_name, email, phone, gender, department, designation, salary, joining_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssssds",
            $name,
            $email,
            $phone,
            $gender,
            $department,
            $designation,
            $salary,
            $joiningDate
        );

        if ($stmt->execute()) {

            header("Location: index.php?success=1");
            exit;

        } else {

            if ($stmt->errno == 1062) {
                $message = "This email address already exists!";
            } else {
                $message = "Database Error: " . $stmt->error;
            }

            $messageType = "error";
        }

        $stmt->close();
    }
}


// =====================================
// SUCCESS MESSAGE
// =====================================

if (isset($_GET["success"])) {
    $message = "Employee details saved successfully!";
    $messageType = "success";
}


// =====================================
// DELETE EMPLOYEE
// =====================================

if (isset($_GET["delete"])) {

    $id = filter_var($_GET["delete"], FILTER_VALIDATE_INT);

    if ($id && $id > 0) {

        $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: index.php?deleted=1");
        exit;
    }
}

if (isset($_GET["deleted"])) {
    $message = "Employee deleted successfully!";
    $messageType = "success";
}


// =====================================
// FETCH EMPLOYEE DETAILS
// =====================================

$result = $conn->query("SELECT * FROM employees ORDER BY id DESC");

$totalEmployees = $result->num_rows;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Employee Management System</title>

<style>

/* GENERAL */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #eef2f7;
    padding: 30px 15px;
    color: #263445;
}

.container {
    max-width: 1150px;
    margin: auto;
}

/* HEADER */

.header {
    background: linear-gradient(120deg, #173b70, #2875b9);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.header h1 {
    margin-bottom: 10px;
    font-size: 30px;
}

.header p {
    color: #e0edff;
}

/* STATISTICS */

.stats {
    background: white;
    padding: 22px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px #00000010;
}

.stats h2 {
    color: #1769aa;
    font-size: 30px;
    margin-top: 8px;
}

/* CARD */

.card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px #00000010;
}

.card h2 {
    margin-bottom: 22px;
    color: #173b70;
}

/* FORM */

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #34445a;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 13px;
    border: 1px solid #ccd5df;
    border-radius: 6px;
    font-size: 15px;
    background: white;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #2875b9;
    box-shadow: 0 0 4px #2875b955;
}

/* BUTTON */

.submit-btn {
    margin-top: 25px;
    background: #1769aa;
    color: white;
    border: none;
    padding: 14px 30px;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}

.submit-btn:hover {
    background: #104d80;
}

/* MESSAGE */

.message {
    padding: 14px;
    margin-bottom: 20px;
    border-radius: 6px;
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

/* TABLE */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
}

th, td {
    padding: 13px;
    text-align: left;
    border-bottom: 1px solid #e5eaf0;
}

th {
    background: #173b70;
    color: white;
}

tr:hover {
    background: #f4f8fc;
}

/* DELETE BUTTON */

.delete-btn {
    background: #dc3545;
    color: white;
    padding: 8px 12px;
    text-decoration: none;
    border-radius: 5px;
    display: inline-block;
}

.delete-btn:hover {
    background: #b52a37;
}

/* EMPTY */

.empty {
    text-align: center;
    padding: 25px;
    color: #777;
}

/* RESPONSIVE */

@media(max-width: 650px) {

    .form-grid {
        grid-template-columns: 1fr;
    }

    body {
        padding: 15px 8px;
    }

    .card {
        padding: 18px;
    }

    .header h1 {
        font-size: 23px;
    }
}

</style>

</head>


<body>

<div class="container">


    <!-- HEADER -->

    <div class="header">

        <h1>Employee Management System</h1>

        <p>Enter, manage and view employee information</p>

    </div>


    <!-- TOTAL EMPLOYEES -->

    <div class="stats">

        <p>Total Registered Employees</p>

        <h2><?php echo $totalEmployees; ?></h2>

    </div>


    <!-- EMPLOYEE FORM -->

    <div class="card">

        <h2>Employee Registration Form</h2>


        <?php if ($message !== ""): ?>

            <div class="message <?php echo $messageType; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <form method="POST" action="" id="employeeForm">

            <div class="form-grid">


                <!-- NAME -->

                <div class="form-group">

                    <label>Employee Name</label>

                    <input
                        type="text"
                        name="employee_name"
                        placeholder="Enter employee name"
                        pattern="[A-Za-z ]{2,100}"
                        minlength="2"
                        maxlength="100"
                        title="Only alphabets and spaces are allowed"
                        required
                    >

                </div>


                <!-- EMAIL -->

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


                <!-- PHONE -->

                <div class="form-group">

                    <label>Phone Number</label>

                    <input
                        type="tel"
                        name="phone"
                        placeholder="Enter 10 digit phone number"
                        pattern="[0-9]{10}"
                        minlength="10"
                        maxlength="10"
                        inputmode="numeric"
                        title="Enter exactly 10 digits"
                        required
                    >

                </div>


                <!-- GENDER -->

                <div class="form-group">

                    <label>Gender</label>

                    <select name="gender" required>

                        <option value="">Select Gender</option>

                        <option value="Male">Male</option>

                        <option value="Female">Female</option>

                        <option value="Other">Other</option>

                    </select>

                </div>


                <!-- DEPARTMENT -->

                <div class="form-group">

                    <label>Department</label>

                    <select name="department" required>

                        <option value="">Select Department</option>

                        <option value="IT">IT</option>

                        <option value="HR">Human Resources</option>

                        <option value="Finance">Finance</option>

                        <option value="Marketing">Marketing</option>

                        <option value="Sales">Sales</option>

                        <option value="Management">Management</option>

                    </select>

                </div>


                <!-- DESIGNATION -->

                <div class="form-group">

                    <label>Designation</label>

                    <input
                        type="text"
                        name="designation"
                        placeholder="Enter designation"
                        pattern="[A-Za-z ]{2,100}"
                        minlength="2"
                        maxlength="100"
                        title="Only alphabets and spaces are allowed"
                        required
                    >

                </div>


                <!-- SALARY -->

                <div class="form-group">

                    <label>Monthly Salary (₹)</label>

                    <input
                        type="number"
                        name="salary"
                        placeholder="Enter salary"
                        min="0"
                        max="99999999.99"
                        step="0.01"
                        required
                    >

                </div>


                <!-- JOINING DATE -->

                <div class="form-group">

                    <label>Joining Date</label>

                    <input
                        type="date"
                        name="joining_date"
                        max="<?php echo date('Y-m-d'); ?>"
                        required
                    >

                </div>


            </div>


            <button
                type="submit"
                name="save_employee"
                class="submit-btn"
            >

                Save Employee

            </button>


        </form>

    </div>



    <!-- EMPLOYEE DETAILS TABLE -->

    <div class="card">

        <h2>Employee Details</h2>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Name</th>

                        <th>Email</th>

                        <th>Phone</th>

                        <th>Gender</th>

                        <th>Department</th>

                        <th>Designation</th>

                        <th>Salary</th>

                        <th>Joining Date</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($result && $result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo (int)$row["id"]; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["employee_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["email"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["phone"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["gender"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["department"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["designation"]); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float)$row["salary"], 2); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["joining_date"]); ?>
                            </td>

                            <td>

                                <a
                                    class="delete-btn"
                                    href="index.php?delete=<?php echo (int)$row["id"]; ?>"
                                    onclick="return confirm('Are you sure you want to delete this employee?');"
                                >
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="10" class="empty">

                            No employee records found.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


</div>


<!-- JAVASCRIPT VALIDATION -->

<script>

const form = document.getElementById("employeeForm");

const nameInput = document.querySelector('[name="employee_name"]');
const phoneInput = document.querySelector('[name="phone"]');
const designationInput = document.querySelector('[name="designation"]');


// Name - Alphabets only
nameInput.addEventListener("input", function() {

    this.value = this.value.replace(/[^a-zA-Z ]/g, "");

});


// Phone - Numbers only, maximum 10 digits
phoneInput.addEventListener("input", function() {

    this.value = this.value.replace(/[^0-9]/g, "").slice(0, 10);

});


// Designation - Alphabets only
designationInput.addEventListener("input", function() {

    this.value = this.value.replace(/[^a-zA-Z ]/g, "");

});


// Final Form Validation
form.addEventListener("submit", function(event) {

    if (!form.checkValidity()) {

        event.preventDefault();

        form.reportValidity();

    }

});

</script>


</body>

</html>


<?php

$conn->close();

?>
