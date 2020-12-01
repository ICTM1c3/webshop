<?php
ob_start();
include 'header.php';

if ($authenticated) {
    header("Location: customer.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $email = $_POST['email'];
    } else $errors[] = "Het e-mailadres veld is verplicht.";

    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $password = $_POST['password'];
    } else $errors[] = "Het wachtwoord veld is verplicht.";

    if (count($errors) === 0) {
        $stmt = $connection->prepare("SELECT id, first_name, last_name, password FROM users WHERE email = ? AND deleted_at IS NULL;");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $connection->close();

        $hash = $result['password'] ?? false;

        if (password_verify($password, $hash)) {
            $_SESSION['user'] = [
                    'id' => $result['id'],
                    'name' => $result['first_name'] . " " . $result['last_name'],
            ];

            if (isset($_GET['goto'])) {
                header("Location: " . $_GET['goto']);
            } else {
                header("Location: customer.php");
            }

            exit();
        } else $errors[] = "De inloggegevens zijn onjuist.";
    }
}
?>
<div class="container">
    <form method="POST">
        <?php
        foreach($errors as $key => $value) {
            ?>
            <div class="alert alert-danger"><?=$value?></div>
            <?php
        }
        ?>
        <div class="form-group">
            <label for="email">E-mailadres
                <input type="email" name="email" id="email" class="form-control">
            </label>
        </div>

        <div class="form-group">
            <label for="email">Wachtwoord
                <input type="password" name="password" id="password" class="form-control">
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Login</button>
        <a href="password-forgotten.php" class="btn btn-secondary ml-2">Wachtwoord vergeten?</a>
    </form>

    <p class="mt-3">Heb je nog geen account? <u><a class="text-white" href="register.php">Registreren</a></u></p>
</div>
<?php
include 'footer.php';
?>
