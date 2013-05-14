==Category backup local plugin==

This can be used to backup a course category, with all its sub-categories and courses in one go and then restore the entire category on a new site.

==Recent changes==

2012-02-08 - Initial version

==Installation==

Place all the files in local/categorybackup
Log in as a site admin and click through the install process

==Usage==

Visit http://[siteurl]/local/categorybackup/
Select the category to backup and click 'start backup'
Once the backup is complete, the full path to the created file will be displayed - copy this file to the new server.

On the new server (assuming you have already installed the plugin), visit http://[siteurl]/local/categorybackup
Select the category within which to recreate the category structure you backed up (choose 'Top' to place the category you backed up at the top level of the category tree).
Type in the full path on your server to where you have placed the backup file (e.g. '/var/moodle_data/backup-category1.zip')
Click on 'Start restore' (then wait)

==Contact details==

This plugin was created by Synergy Learning.
For more information contact info@synergy-learning.com
