<?php
// IMPORTANT: Replace 'YourSuperStrongAdminPassword' with the ACTUAL password you want for your admin.
// Choose a strong, unique password for security!
$admin_password_to_hash = '020802';
echo password_hash($admin_password_to_hash, PASSWORD_DEFAULT);
?>