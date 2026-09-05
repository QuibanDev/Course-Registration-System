<?php
error_reporting(E_ALL ^ E_NOTICE);

require 'db.php';
$conn = Database::getConnection();

$successMsg = "";
$errorMsg   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email     = trim($_POST["email"]);
    $password  = trim($_POST["password"]);
    $firstName = trim($_POST["firstName"]);
    $lastName  = trim($_POST["lastName"]);
    $major     = trim($_POST["major"]);
    $phone     = trim($_POST["phone"]);
    $role      = "student";

    if (empty($email) || empty($firstName) || empty($lastName) || strlen($password) < 8) {
        $errorMsg = "Email, first name, last name, and an 8+ character password are required.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Generate the next sequential student ID, e.g. S00003
        $maxIdResult = $conn->query("SELECT studentId FROM students ORDER BY id DESC LIMIT 1");
        $nextNum = 1;
        if ($maxIdResult && $maxIdResult->num_rows === 1) {
            $lastId  = $maxIdResult->fetch_assoc()['studentId'];
            $nextNum = (int) substr($lastId, 1) + 1;
        }
        $studentId = 'S' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO students (studentId,email,password,firstName,lastName,major,phone,role)
                                 VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssss", $studentId, $email, $hashedPassword, $firstName, $lastName, $major, $phone, $role);

        try {
            $stmt->execute();
            $successMsg = "Registration successful! Your Student ID is <strong>" . htmlspecialchars($studentId) . "</strong>. You may now <a href='login.php'>log in</a>.";
        } catch (mysqli_sql_exception $e) {
            $errorMsg = ($e->getCode() === 1062)
                ? "That email address is already registered."
                : "Registration failed. Please try again.";
        }
    }
}

$majors = [
    "Computer Science", "Mathematics", "English", "Biology",
    "Art", "Business", "Physics", "Psychology", "Undeclared"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php require 'master.php'; ?>

<div class="page-content">
<div class="container" style="max-width:680px; margin:0 auto; padding:0 20px;">
    <div class="ep-card">

        <div class="auth-icon-wrap">&#127891;</div>
        <h2 class="text-center" style="font-size:26px; margin-bottom:6px;">Create Your Student Account</h2>
        <p class="text-center text-muted" style="margin-bottom:24px;">Register to browse the catalog and enroll in courses</p>

        <?php if ($successMsg): ?>
            <div class="ep-alert ep-alert-success"><span>&#10003;</span><span><?= $successMsg ?></span></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="ep-alert ep-alert-danger"><span>&#9888;</span><span><?= htmlspecialchars($errorMsg) ?></span></div>
        <?php endif; ?>

        <form method="POST" action="registration.php">

            <div class="ep-section-label">Account Credentials</div>

            <div class="ep-form-group">
                <label class="ep-label" for="email">Email Address <span class="req">*</span></label>
                <div class="ep-input-wrap">
                    <input class="ep-input" type="email" id="email" name="email"
                           placeholder="Insert your email" required
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    <span class="ep-input-icon">&#9993;</span>
                </div>
            </div>

            <div class="ep-form-group">
                <label class="ep-label" for="password">Password <span class="req">*</span></label>
                <div class="ep-input-wrap">
                    <input class="ep-input" type="password" id="password" name="password"
                           placeholder="Minimum 8 characters" minlength="8" required>
                    <span class="ep-input-icon">&#128274;</span>
                </div>
            </div>

            <div class="ep-section-label">Personal Information</div>

            <div class="row-2col">
                <div class="ep-form-group">
                    <label class="ep-label" for="firstName">First Name <span class="req">*</span></label>
                    <div class="ep-input-wrap">
                        <input class="ep-input" type="text" id="firstName" name="firstName"
                               placeholder="First Name" required
                               value="<?= isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : '' ?>">
                        <span class="ep-input-icon">&#128100;</span>
                    </div>
                </div>
                <div class="ep-form-group">
                    <label class="ep-label" for="lastName">Last Name <span class="req">*</span></label>
                    <div class="ep-input-wrap">
                        <input class="ep-input" type="text" id="lastName" name="lastName"
                               placeholder="Last Name" required
                               value="<?= isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : '' ?>">
                        <span class="ep-input-icon">&#128100;</span>
                    </div>
                </div>
            </div>

            <div class="row-2col">
                <div class="ep-form-group">
                    <label class="ep-label" for="major">Major</label>
                    <div class="ep-input-wrap">
                        <select class="ep-input no-icon" id="major" name="major">
                            <?php foreach ($majors as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="ep-form-group">
                    <label class="ep-label" for="phone">Phone Number</label>
                    <div class="ep-input-wrap">
                        <input class="ep-input" type="tel" id="phone" name="phone"
                               placeholder="(000) 000-0000">
                        <span class="ep-input-icon">&#128222;</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="ep-btn ep-btn-primary ep-btn-block" style="margin-top:8px;">
                &#10003;&nbsp; Create Account
            </button>
        </form>

        <p class="text-center mt-6 text-muted">
            Already have an account? <a href="login.php">Log in here</a>
        </p>
    </div>
</div>
</div>

<?php require 'footer.php'; ?>
</body>
</html>
