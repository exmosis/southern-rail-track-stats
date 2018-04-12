# southern-rail-track-stats

*Looking for the stats themselves rather than the script to generate them? This script has been running daily from 20th June 2016 to 11th November 2017, followed by a resinstatement from 11th APril 2018. Collected results available as a single JSON file at: http://exmosis.net/southern-rail-track-stats/stats*

Southern Rail provide daily performance stats at https://www.southernrailway.com/about-us/how-were-performing/daily-performance-report, but sadly no archive of previous performance. This script is a quick attempt to archive perfomance stats in the interest of public transparency, and publish them in a more usable format.

Currently it's all very basic. To run it regularly, set up a cron job or other task scheduler to run it once a day. By default, the data will be stored against a key of yesterday's date in a "southern-rail-performance..json" file in the same directory.

**WARNING:** Currently the script does no date validation, and assumes that the data for yesterday is live on the SR site. ie when you run it, it will store whatever data is on the site against yesterday's date. Southern say they updae the page by midday 12 noon, so you *should* be safe so long as you run it in the afternoon.

Please do let me know if this data is available elsewhere already...


