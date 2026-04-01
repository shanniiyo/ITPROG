<!DOCTYPE html>
<html>
<head>
<title>Add Locker</title>
</head>
<body>

<h2>Add Locker</h2>

<form action="insert_locker.php" method="POST">
  <input type="text" name="location" placeholder="Location" required>
  <input type="number" step="10.00" name="price" placeholder="Price per hour">
  <select name="size">
    <option value="small">Small</option>
    <option value="medium">Medium</option>
    <option value="large">Large</option>
  </select>

  <select name="status">
    <option value="available">Available</option>
    <option value="occupied">Occupied</option>
    <option value="out_of_service">Out of Service</option>
  </select>

  <button>Add Locker</button>
</form>

</body>
</html>