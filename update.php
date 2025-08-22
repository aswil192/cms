<?php
include("connection.php");

$sql = "SELECT * FROM user WHERE id='$_REQUEST[cc]'";
$result = mysqli_query($con, $sql);
$row = mysqli_fetch_array($result);
?>

<form method="post">
    <label>Name</label>
    <input type="text" name="name" value="<?php echo $row['name']; ?>"><br>

    <label>Email</label>
    <input type="text" name="email" value="<?php echo $row['email']; ?>"><br>

    <label>Phone</label>
    <input type="text" name="ph" value="<?php echo $row['ph']; ?>"><br>

    <label>Password</label>
    <input type="text" name="password" value="<?php echo $row['password']; ?>"><br>

    <input type="submit" name="update" value="Update"><br>
</form>

<?php
if (isset($_POST['update'])) 
{
    $name = $_POST['name'];
    $email = $_POST['email'];
    $ph = $_POST['ph']
    $password=$_POST['password']

    $b = "UPDATE user SET name='$name', email='$email', ph='$ph', password='$password' WHERE id='$_REQUEST[cc]'";
    mysqli_query($con, $b);
 
}
?>