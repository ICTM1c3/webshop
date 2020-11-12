<?php
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? "";
    $password = $_POST['password'] ?? "";

    $query = "SELECT HashedPassword FROM people WHERE LogonName = ? AND LogonName != 'NO LOGON' AND IsPermittedToLogon = 1";
    $statement = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($statement, "s", $email);
    mysqli_stmt_execute($statement);
    $result = mysqli_fetch_all(mysqli_stmt_get_result($statement), MYSQLI_ASSOC);

    $hash = $result['HashedPassword'] ?? false;

    if (password_verify($hash, $password)) {
        echo "Password valid!";
    } else {
        echo "Password invalid";
    }
}
?>
<div>
    <form action="login.php" method="POST">
        <label for="email">E-mailadres:
            <input type="email" name="email" id="email">
        </label>

        <label for="email">Wachtwoord:
            <input type="password" name="password" id="password">
        </label>

        <button type="submit">Login</button>
    </form>
</div>
<?php
include 'footer.php';
?>
