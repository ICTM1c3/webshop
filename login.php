<?php
include 'header.php';
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
