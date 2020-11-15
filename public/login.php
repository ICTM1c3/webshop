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
        $password = hash('sha256', $_POST['password']);
    } else $errors[] = "Het wachtwoord veld is verplicht.";

    if (count($errors) === 0) {
        $stmt = $connection->prepare("SELECT PersonId, FullName, HashedPassword FROM people WHERE LogonName = ? AND LogonName != 'NO LOGON' AND IsPermittedToLogon = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $connection->close();

        $hash = $result['HashedPassword'] ?? false;

        if ($hash === strtoupper($password)) {
            $_SESSION['user'] = [
                    'id' => $result['PersonId'],
                    'name' => $result['FullName'],
            ];
            header("Location: /customer.php");
            exit();
        } else $errors[] = "De inloggegevens zijn onjuist.";
    }
}
?>
<div class="container">
    <form action="login.php" method="POST">
        <?php
        foreach($errors as $key => $value) {
            ?>
            <div class="alert alert-danger"><?=$value?></div>
            <?php
        }
        ?>
        <div class="form-group">
            <label for="email">E-mailadres:
                <input type="email" name="email" id="email">
            </label>
        </div>

        <div class="form-group">
            <label for="email">Wachtwoord:
                <input type="password" name="password" id="password">
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>
<?php
include 'footer.php';
?>
