Copy the contents of this folder into PHP Desktop's folder root.
Place the phpdesktop runtime next to this folder and run phpdesktop-chrome.exe to launch the app.


IMPORTANT NOTES (auto-generated):
1. A SQLite database was created at: www/data/petlandia.db (best-effort conversion from your SQL).
   - If you need to re-import or the database is incomplete, edit www/data/petlandia.db or provide a corrected SQLite dump.

2. Compatibility layer:
   - A compatibility file `www/db_compat.php` was added. To use it, add this line at the top of your entry script (login.php) AFTER any auto-generated connection:
     require_once __DIR__ . '/db_compat.php';
   - This layer provides basic replacements for common mysqli_* functions by executing queries via PDO/SQLite.
   - It is a best-effort shim; complex queries or use of mysqli-specific features may still need manual adjustments.

3. How to run:
   - Copy the contents of this package into PHP Desktop root or place the phpdesktop runtime next to this folder.
   - Run phpdesktop-chrome.exe (from the phpdesktop runtime folder). The app will start at login.php.

4. Packaging:
   - Use Inno Setup (petlandia_inno.iss included) to create an installer if you want a setup EXE.
