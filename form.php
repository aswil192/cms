<form method="POST" action="">

<label>name</label>
    <input type="text" name="name" placeholder="Enter name" required>

    <label>email</label>
    <input type="email" name="email" required>

    <label>ph</label>
    <input type="text" name="phone" placeholder="Enter phone" required>

    <label>Password</label>
    <input type="password" name="password" placeholder="Enter password" required>

    <input type="submit" name="submit" value="Register">
</form>

<?php
include("connection.php");

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $ph = $_POST['phone'];
    $password = $_POST['password'];

    // insert query
    $ins = "INSERT INTO user(name, email, ph, password) 
            VALUES('$name', '$email', '$ph', '$password')";

    // query execution
    mysqli_query($con, $ins);

}
?>