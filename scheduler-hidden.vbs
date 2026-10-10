Set sh = CreateObject("WScript.Shell")
sh.Run """C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"" ""C:\laragon\www\barbara-pos\artisan"" boutique:backup-auto", 0, False