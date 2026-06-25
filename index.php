<?php
echo "<h1>Student Registration System</h1>";
?>

<form method="POST">
    Name: <input type="text" name="name"><br><br>
    <input type="submit" value="Register">
</form>

<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = $_POST['name'];
    echo "<h3>Welcome $name</h3>";
}
?>

<h2>Login Section</h2>

<form method="POST">
    Username: <input type="text" name="username"><br><br>
    Password: <input type="password" name="password"><br><br>
    <input type="submit" value="Login">
</form>

<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(isset($_POST['username']) && isset($_POST['password'])){
        $user = $_POST['username'];
        $pass = $_POST['password'];

        echo "<h3>Login Successful for: $user</h3>";
    }
}
?>

