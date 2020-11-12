<?php
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_report(MYSQLI_REPORT_ALL);
    $first_name = $_POST['first_name'] ?? "";
    $last_name = $_POST['last_name'] ?? "";
    $full_name = "$first_name $last_name";
    $email = $_POST['email'] ?? "";
    $password = $_POST['password'] ?? "";
    $phone = $_POST['phone'] ?? "";

    $query = "INSERT INTO people (FullName, PreferredName, SearchName, LogonName, EmailAddress, IsPermittedToLogon, HashedPassword, LastEditedBy, ValidFrom, ValidTo, IsExternalLogonProvider, IsSystemUser, IsEmployee, IsSalesperson)";
    $query .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

    $statement = mysqli_prepare($connection, $query);
    $hashed_password = hash_hmac('sha256', $password, null);

    var_dump($query);
    var_dump($hashed_password);
    var_dump($statement);

    $date = date('Y-m-d H:i:s');
    $one = 1;
    $zero = 0;
    $valid_to = '9999-12-31 23:59:59';

    mysqli_stmt_bind_param($statement, "sssssibissiiii", $full_name, $first_name, $full_name, $email, $email, $one, $hashed_password, $one, $date, $valid_to, $zero,$zero,$zero,$zero);
    var_dump($statement);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);

    dd($result);
}
?>
<div>
    <form action="register.php" method="POST">
        <label for="first_name">Voornaam:
            <input type="text" name="first_name" id="first_name">
        </label>

        <label for="first_name">Achternaam:
            <input type="text" name="first_name" id="first_name">
        </label>

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
