# southern-rail-track-stats

Southern Rail provide daily performance stats at http://www.southernrailway.com/your-journey/performance-results/daily/, but sadly no archive of previous performance. This script is a quick attempt to archive perfomance stats in the interest of public transparency, and publish them in a more usable format.

Currently it's all very basic. To run it regularly, set up a cron job or other task scheduler to run it once a day. By default, the data will be stored against a key of yesterday's date in a "southern-rail-performance..json" file in the same directory.

Please do let me know if this data is available elsewhere already...


