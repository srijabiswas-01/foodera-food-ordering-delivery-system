-- Run once for databases imported from the original Foodera dump.
ALTER TABLE `admins` MODIFY `password` VARCHAR(255) NOT NULL;
-- Existing plain text passwords are upgraded to hashes when those admins next sign in.
-- For a new installation, leave admins empty and use /admin/login.php to create the first admin.
