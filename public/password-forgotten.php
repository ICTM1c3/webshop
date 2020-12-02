<?php
ob_start();
include 'header.php';

$errors = [];
$success_messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $email = $_POST['email'];
    } else $errors[] = "Het e-mailadres veld is verplicht.";

    if (count($errors) === 0 && emailExists($email)) {
        $random_string = willekeurig(200);
        $date = date('Y-m-d H:i:s');

        $reset_url = 'http://' . $_SERVER['HTTP_HOST'] . str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) . '/password-reset.php?token=' . $random_string;

        $body = file_get_contents(__DIR__ . '/../email-templates/password-reset-inline.html');
        $body = str_replace('{{RESET_URL}}', $reset_url, $body);

        $mail = sendMail([$email], "Wachtwoord vergeten | NerdyGadgets", $body);

        if ($mail) {
            $stmt = $connection->prepare("INSERT INTO password_resets VALUES (?, ?, ?);");
            $stmt->bind_param("sss", $email, $random_string, $date);
            $result = $stmt->execute();
            $stmt->close();
            $connection->close();
        }
    }

    $success_messages[] = "Als dit e-mailadres bekend is bij ons, is er zojuist een wachtwoord herstel e-mail verstuurd.";
}

function emailExists($email) {
    include 'config.php';

    $stmt = $connection->prepare("SELECT email FROM users WHERE deleted_at IS NULL AND email = ?;");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $count = $stmt->get_result()->num_rows;

    $stmt->close();
    $connection->close();

    return $count > 0;
}
?>
<div class="container">
    <form method="POST">
        <h4>Wachtwoord vergeten</h4>
        <p>Ben je je wachtwoord vergeten?<br>Vul dan hieronder je e-mailadres in en we sturen een verificatiemail met daarin een link om je wachtwoord opnieuw in te stellen.</p>
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
            <label for="email">E-mailadres
                <input type="email" name="email" id="email" class="form-control" required>
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Verstuur verificatie e-mail</button>
    </form>

    <p class="mt-3">Weet je je wachtwoord weer? <u><a class="text-white" href="login.php">Inloggen</a></u></p>
</div>
<?php
include 'footer.php';
?>
