@ECHO OFF
COPY /Y bootstrap\compiled.php bootstrap\compiled.php.bak
php artisan clear-compiled
php artisan dump-autoload
php artisan optimize --force
php -r "$s = file_get_contents('bootstrap/compiled.php'); $s = preg_replace(array('/[ \t]+$/m', '/(^|\G) {4}/m'), array('', chr(9)), $s); $s = str_replace(array('\'' . addslashes(__DIR__), addslashes('\\vendor\\laravel\\framework\\src\\Illuminate\\')), array('dirname(__DIR__) . \'', '/vendor/laravel/framework/src/Illuminate/'), $s); file_put_contents('bootstrap/compiled.php', $s);"
FC /B bootstrap\compiled.php bootstrap\compiled.php.bak >NUL && MOVE /Y bootstrap\compiled.php.bak bootstrap\compiled.php
