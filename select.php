<table border="1">
    <tr>
        <th>No</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Password</th>
        <th>Update</th>
        <th>Delete</th>
    </tr>

<?php
include("connection.php");
$sql="SELECT * FROM user";
$res=mysqli_query($con,$sql);
$i = 1;
while ($row=mysqli_fetch_array($res)) 
{
?>
    <tr>
        <td><?php echo $i; ?></td>
        <td><?php echo $row['name']; ?></td>
        <td><?php echo $row['email']; ?></td>
        <td><?php echo $row['ph']; ?></td>
        <td><?php echo $row['password']; ?></td>
        <td><a href="delete.php?abc=<?php echo $row['id']; ?>">Delete</a></td>
        <td><a href="update.php?cc=<?php echo $row['id']; ?>">Update</a></td>
    </tr>
<?php
    $i++;
}
?>
</table>