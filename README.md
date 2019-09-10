# cult + pirata
Pirata Inversor y culture game!

# installation

- since we moved it to firebase ajaxphp is not needed
- Make sure you add the domain to the OAuth redirect domains list in the Firebase console -> Auth section -> Sign in method tab.
- Create a secrets/ folder containing info like 

exposed_gmail_cursos_psico.json

{
    "user": "info.cursos.psicologia@gmail.com",
    "pass": "xxxx",
    "smtp_host": "smtp.gmail.com"
}

- Install a cron job every 15min or 30min calling cult/www/backend/stock_cron.php
- Make sure you don't have CORS problem in your server
-- Some free servers would work perfectly e.g., 000webhost but some others add some js to the http request e.g., profreehost or capnix so with them you could only have the website but not an app or anotherwebsite accessing the same info (get_stock.php)

- For the app creation see stockionic repo in github



- DEPRECATED: REPLACED BY WEB php...
Create these folders:
destination="../../cult-data";
destination="../../cult-data-stock-google";
Add this to your crontab
19 20 1 * * YOUR-PATH-TO/cron-scripts/open-data-download.sh
05,35 3-18 * * 1-5 YOUR-PATH-TO/cron-scripts/stock-download-google.sh


