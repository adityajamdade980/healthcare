<!-- filepath: c:\xampp\htdocs\healthCare\frontend\backend\admin-nav.php -->
<nav class="admin-nav">
    <ul>
<!--  <li><a href="adminpage.php">Dashboard</a></li>  -->
 <!--       <li><a href="manage-users.php">Manage Users</a></li> -->
        <!--  <li><a href="managearticles.php">Manage Articles</a></li> -->
       <!--   <li><a href="settings.php">Settings</a></li> -->
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<style>
    .admin-nav {
        background-color: #333;
        padding: 1rem;
    }
    .admin-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 1rem;
    }
    .admin-nav ul li {
        display: inline;
    }
    .admin-nav ul li a {
        color: white;
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        transition: background-color 0.3s;
    }
    .admin-nav ul li a:hover {
        background-color: #555;
    }
</style>