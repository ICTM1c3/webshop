<?php
ob_start();
include 'header.php';

$errors = [];
$success_messages = [];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (isset($_GET['token']) && !empty($_GET['token'])) {
        $token = $_GET['token'];

        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $password = $_POST['password'];

            if (strlen($password) < 8) {
                $errors[] = "Het wachtwoord moet minimaal bestaan uit acht karakters.";
            }
        } else $errors[] = "De wachtwoord bevestiging is verplicht.";

        if (isset($_POST['password_confirmation']) && !empty($_POST['password_confirmation'])) {
            $password_confirmation = $_POST['password_confirmation'];

            if (isset($password) && $password !== $password_confirmation) {
                $errors[] = "De wachtwoorden komen niet overeen.";
            } else if(isset($password)) {
                $password = password_hash($password, PASSWORD_BCRYPT);
            }
        } else $errors[] = "De wachtwoord bevestiging is verplicht.";

        $email = getToken($_GET['token']);

        if (!$email) {
            $errors[] = "Het token is niet geldig.";
        }

        if (empty($errors)) {
            $stmt = $connection->prepare("UPDATE users SET password = ? WHERE email = ?;");
            $stmt->bind_param("ss", $password, $email);

            $result = $stmt->execute();
            $stmt->close();
            $connection->close();

            if (!$result) {
                $errors[] = "Er is een fout opgetreden.";
            } else {
                $success_messages[] = "Het wachtwoord is bijgewerkt.";
                deleteToken($token);
            }
        }
    } else {
        header("Location: password-forgotten.php");
        exit();
    }
}

function getToken($token) {
    include 'config.php';

    $stmt = $connection->prepare("SELECT email, token FROM password_resets WHERE token = ? AND created_at > (NOW() - INTERVAL 1 HOUR);");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $result = $result->fetch_assoc();
    $stmt->close();
    $connection->close();

    return ($result) ? $result['email'] : false;
}

function deleteToken($token) {
    include 'config.php';

    $stmt = $connection->prepare("DELETE FROM password_resets WHERE token = ?;");
    $stmt->bind_param("s", $token);
    $result = $stmt->execute();
    $stmt->close();
    $connection->close();

    return $result;
}
?>
<div class="container">
    <form method="POST">
        <h4>Wachtwoord opnieuw instellen</h4>
        <?php
        foreach($errors as $key => $value) {
            ?>
            <div class="alert alert-danger"><?=$value?></div>
            <?php
        }

        foreach($success_messages as $key => $value) {
            ?>
            <div class="alert alert-success"><?=$value?></div>
            <?php
        }
        ?>
        <div class="form-group">
            <label for="password">Wachtwoord
                <input type="password" name="password" id="password" class="form-control" required>
            </label>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Wachtwoord bevestiging
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Verstuur</button>
    </form>

    <p class="mt-3">Weet je je wachtwoord weer? <u><a class="text-white" href="login.php">Inloggen</a></u></p>
</div>
<?php
include 'footer.php';
?>
