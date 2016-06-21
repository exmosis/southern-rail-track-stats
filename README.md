# southern-rail-track-stats

Southern Rail provide daily performance stats at http://www.southernrailway.com/your-journey/performance-results/daily/, but sadly no archive of previous performance. This script is a quick attempt to archive perfomance stats in the interest of public transparency, and publish them in a more usable format.

Currently it's all very basic. To run it regularly, set up a cron job or other task scheduler to run it once a day. By default, the data will be stored against a key of yesterday's date in a "southern-rail-performance..json" file in the same directory.

**WARNING:** Currently the script does no date validation, and assumes that the data for yesterday is live on the SR site. ie when you run it, it will store whatever data is on the site against yesterday's date. Southern say they updae the page by midday 12 noon, so you *should* be safe so long as you run it in the afternoon.

Please do let me know if this data is available elsewhere already...


