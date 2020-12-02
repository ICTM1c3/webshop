<?php
ob_start();

$errors = [];
$success_messages = [];
$address_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'config.php';

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
    // This code is executed if a post form is sent to the page
    
    if (isset($_POST['action']) && !empty($_POST['action'])) { // Check wether 'action' field is empty
        $action = $_POST['action'];
    } else {
        $errors[] = "Een actie is verplicht.";
    }
    
    if (isset($user["id"]) && !empty($user["id"])) { // Check wether 'user_id' field is empty
        $user_id = $user["id"];
    } else {
        $errors[] = "Een user_id is verplicht.";
    }
    
    if (isset($_POST["first_name"]) && !empty($_POST["first_name"])) {
        $first_name = $_POST["first_name"];
    } else {
        $errors[] = "Je moet een voornaam invoeren.";
    }
    
    
    if (isset($_POST["last_name"]) && !empty($_POST["last_name"])) {
        $last_name = $_POST["last_name"];
    } else {
        $errors[] = "Je moet een achternaam invoeren.";
    }

    if (isset($_POST["email"]) && !empty($_POST["email"])) {
        $email = $_POST["email"];
    } else {
        $errors[] = "Je moet een e-mailadres invoeren.";
    }
    
    if (isset($_POST["currentemail"]) && !empty($_POST["currentemail"])) {
        $currentemail = $_POST["currentemail"];
    } else {
        $errors[] = "Je moet een huidig e-mailadres invoeren.";
    }

    if (emailExists($email) && ($email != $currentemail)) {
        $errors[] = "Dit e-mailadres is al in gebruik.";
    }
    
    if (empty($errors)) {
        // This code executes only if no errors were encountered
        switch ($action) {
            case "update":
                $stmt = $connection->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?;");
                $stmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $success_messages[] = "De gegevens zijn aangepast.";
                $_SESSION["user"]["name"] = $first_name." ".$last_name;
            break;
            
            default:
        break;
    }
}
}
include 'header.php';
?>
<div class="container">
    <?php
    if (isset($user)) { // Only show the addresses if the user is signed in?>
        <h1 class="mb-3">Accountgegevens bewerken</h1>
        <p><u><a class="text-white" href='index.php'>Terug naar de homepagina</a></u>.</p>
        
        <div>
            <?php
            foreach ($errors as $key => $value) {
                ?>
                <div class="alert alert-danger"><?= $value ?></div>
                <?php
            }
            
            foreach ($success_messages as $key => $value) {
                ?>
                <div class="alert alert-success"><?= $value ?></div>
                <?php
            }
            ?>
        </div>
        
        <div class="col-12 bg-dark p-3">
            <?php
                // Load the user's data from the database.
                $stmt = $connection->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ?;");
                $stmt->bind_param("i", $user["id"]);
                $stmt->execute();
                $result = $stmt->get_result();
                $result = ($result) ? $result->fetch_assoc() : false;
                ?>
            <form method="POST" action="my-account.php">
                <div class="form-row pb-2">
                    <div class="col-sm-12 col-md-4 mb-3">
                        <label for="first_name">Voornaam</label>
                        <input type="text" name="first_name" value="<?= $result["first_name"] ?>" id="first_name"
                        class="form-control">
                    </div>
                    <div class="col-sm-12 col-md-4 mb-3">
                        <label for="last_name">Achternaam</label>
                        <input type="text" name="last_name" value="<?= $result["last_name"] ?>" id="last_name"
                        class="form-control">
                    </div>
                    <div class="col-sm-12 col-md-4 mb-3">
                        <label for="email">E-mailadres</label>
                        <input type="text" name="email" value="<?= $result["email"] ?>"
                        id="email" class="form-control">
                    </div>
                </div>
                <div class="form-row pb-2 pl-2">
                    <input type="hidden" name="currentemail" value="<?= $result["email"] ?>" id="currentemail">
                    <input type="hidden" name="action" value="update">
                    <button type="submit" class="btn btn-primary">Bevestigen</button>
                </div>
            </form>
        </div>
        <?php
    } else { ?>
        <h1 class="mb-3">U moet ingelogd zijn om deze pagina te gebruiken.</h1>
        <?php
    } ?>
</div>
<?php
include 'footer.php';
?>
