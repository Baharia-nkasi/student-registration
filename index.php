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