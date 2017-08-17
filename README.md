# cult
culture game!

# installation

- since we moved it to firebase ajaxphp is not needed
- Make sure you add the domain to the OAuth redirect domains list in the Firebase console -> Auth section -> Sign in method tab.

Create these folders:
destination="../../cult-data";
destination="../../cult-data-stock-google";


- TO BE REPLACED BY WEB php...
Add this to your crontab
19 20 1 * * YOUR-PATH-TO/cron-scripts/open-data-download.sh
05,35 3-18 * * 1-5 YOUR-PATH-TO/cron-scripts/stock-download-google.sh


