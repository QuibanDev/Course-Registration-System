<?php
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    session_start();
}
if (isset($_SESSION['username'])) { header("Location: profile.php"); exit(); }

require 'db.php';
$conn = Database::getConnection();

$errorMsg  = "";
$loggedOut = isset($_GET['loggedout']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT id, studentId, email, password, firstName, lastName, role FROM students WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row["password"])) {
            session_regenerate_id(true);
            $_SESSION["username"]  = $row["email"];
            $_SESSION["firstName"] = $row["firstName"];
            $_SESSION["lastName"]  = $row["lastName"];
            $_SESSION["id"]        = $row["id"];
            $_SESSION["studentId"] = $row["studentId"];
            $_SESSION["role"]      = $row["role"];
            header("Location: profile.php"); exit();
        }
    }
    $errorMsg = "Invalid email or password. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php require 'master.php'; ?>

<div class="page-content">
<div class="container" style="max-width:460px; margin:0 auto; padding:0 20px;">
    <div class="ep-card">

        <div class="auth-icon-wrap">&#128274;</div>
        <h2 class="text-center" style="font-size:26px; margin-bottom:6px;">Welcome Back</h2>
        <p class="text-center text-muted" style="margin-bottom:24px;">Sign in to manage your course schedule</p>

        <?php if ($loggedOut): ?>
            <div class="ep-alert ep-alert-success"><span>&#10003;</span><span>You have been successfully logged out.</span></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="ep-alert ep-alert-danger"><span>&#9888;</span><span><?= htmlspecialchars($errorMsg) ?></span></div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <div class="ep-form-group">
                <label class="ep-label" for="email">Email Address</label>
                <div class="ep-input-wrap">
                    <input class="ep-input" type="email" id="email" name="email"
                           placeholder="Insert your email" required
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    <span class="ep-input-icon">&#9993;</span>
                </div>
            </div>

            <div class="ep-form-group">
                <label class="ep-label" for="password">Password</label>
                <div class="ep-input-wrap">
                    <input class="ep-input" type="password" id="password" name="password"
                           placeholder="Minimum 8 characters" required>
                    <span class="ep-input-icon">&#128274;</span>
                </div>
            </div>

            <button type="submit" class="ep-btn ep-btn-primary ep-btn-block" style="margin-top:8px;">
                &#8594;&nbsp; Sign In
            </button>
        </form>



        <p class="text-center mt-6 text-muted">
            Don't have an account? <a href="registration.php">Register here</a>
        </p>
    </div>
</div>
</div>

<?php require 'footer.php'; ?>
</body>
</html>
