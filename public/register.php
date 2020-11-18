<?php
include 'header.php';

$errors = [];
$success_messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $email = $_POST['email'];
    } else $errors[] = "Het e-mailadres veld is verplicht.";

    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    } else $errors[] = "Het wachtwoord veld is verplicht.";

    if (isset($_POST['first_name']) && !empty($_POST['first_name'])) {
        $first_name = $_POST['first_name'];
    } else $errors[] = "Het voornaam veld is verplicht.";

    if (isset($_POST['last_name']) && !empty($_POST['last_name'])) {
        $last_name = $_POST['last_name'];
    } else $errors[] = "Het achternaam veld is verplicht.";

    if (isset($_POST['phone']) && !empty($_POST['phone'])) {
        $phone = $_POST['phone'];
    } else $errors[] = "Het telefoonnummer veld is verplicht.";

    if (isset($email)) {
        if (emailExists($email)) {
            $errors[] = "Dit e-mailadres is al in gebruik.";
        }
    }

    if (count($errors) === 0) {
        mysqli_report(MYSQLI_REPORT_ALL);

        $stmt = $connection->prepare("INSERT INTO users (first_name, last_name, email, password, created_at) VALUES (?, ?, ?, ?, ?);");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $password, $date);
        $date = date('Y-m-d H:i:s');

        $result = $stmt->execute();
        $stmt->close();
        $connection->close();

        if (!$result) {
            $errors[] = "Er is een fout opgetreden.";
        } else $success_messages[] = "Het account is aangemaakt, je kunt nu inloggen.";
    }
}

function emailExists($email) {
    include 'config.php';

    $stmt = $connection->prepare("SELECT email FROM users WHERE deleted_at IS NULL;");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $count = $stmt->get_result()->num_rows;

    $stmt->close();
    $connection->close();

    return $count > 0;
}
?>
<div class="container">
    <form action="register.php" method="POST">
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
            <label for="first_name">Voornaam
                <input type="text" name="first_name" id="first_name" class="form-control">
            </label>
        </div>

        <div class="form-group">
            <label for="last_name">Achternaam
                <input type="text" name="last_name" id="last_name" class="form-control">
            </label>
        </div>

        <div class="form-group">
            <label for="email">E-mailadres
                <input type="email" name="email" id="email" class="form-control">
            </label>
        </div>

        <div class="form-group">
            <label for="phone">Telefoonnummer
                <input type="tel" name="phone" id="phone" class="form-control">
            </label>
        </div>

        <div class="form-group">
            <label for="email">Wachtwoord
                <input type="password" name="password" id="password" class="form-control">
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Registeren</button>
    </form>
</div>
<?php
include 'footer.php';
?>
