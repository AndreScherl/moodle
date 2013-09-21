
Grid Course Format
============================

Required version of Moodle
==========================
Requires Moodle version 2012120301.02 release 2.4.1+ (Build: 20130118).

Please ensure that your hardware and software complies with 'Requirements' in 'Installing Moodle' on
'docs.moodle.org/24/en/Installing_Moodle' / 'docs.moodle.org/dev/Moodle_2.4_release_notes'.

Free Software
=============
The Grid format is 'free' software under the terms of the GNU GPLv3 License, please see 'COPYING.txt'.

It can be obtained for free from:
https://moodle.org/plugins/view.php?plugin=format_grid
and
https://github.com/gjb2048/moodle-courseformat_grid/releases

You have all the rights granted to you by the GPLv3 license.  If you are unsure about anything, then the
FAQ - http://www.gnu.org/licenses/gpl-faq.html - is a good place to look.

If you reuse any of the code then I kindly ask that you make reference to the format.

If you make improvements or bug fixes then I would appreciate if you would send them back to me by forking from
https://github.com/gjb2048/moodle-courseformat_grid and doing a 'Pull Request' so that the rest of the
Moodle community benefits.

Installation
============
1. Ensure you have the version of Moodle as stated above in 'Required version of Moodle'.  This is essential as the
   format relies on underlying core code that is out of my control.
2. Put Moodle in 'Maintenance Mode' (docs.moodle.org/en/admin/setting/maintenancemode) so that there are no
   users using it bar you as the administrator - if you have not already done so.
3. Copy 'grid' to '/course/format/' if you have not already done so.
4. Login as an administrator and follow standard the 'plugin' update notification.  If needed, go to
   'Site administration' -> 'Notifications' if this does not happen.
5. Put Moodle out of Maintenance Mode.

Uninstallation
==============
1. Put Moodle in 'Maintenance Mode' so that there are no users using it bar you as the administrator.
2. It is recommended but not essential to change all of the courses that use the format to another.  If this is
   not done Moodle will pick the last format in your list of formats to use but display in 'Edit settings' of the
   course the first format in the list.  You can then set the desired format.
3. In '/course/format/' remove the folder 'grid'.
4. In the database, remove the row with the 'plugin' of 'format_grid' in the 'config_plugins' table and drop the
   'format_grid_icon' and 'format_grid_summary' tables.
5. Put Moodle out of Maintenance Mode.

Upgrade Instructions
====================
1. Ensure you have the version of Moodle as stated above in 'Required version of Moodle'.  This is essential as the
   format relies on underlying core code that is out of my control.
2. Put Moodle in 'Maintenance Mode' so that there are no users using it bar you as the administrator.
3. In '/course/format/' move old 'grid' directory to a backup folder outside of Moodle.
4. Copy new 'grid' to '/course/format/'.
5. Go back in as an administrator and follow standard the 'plugin' update notification.  If needed, go to
   'Site administration' -> 'Notifications' if this does not happen.
6. If automatic 'Purge all caches' appears not to work by lack of display etc. then perform a manual 'Purge all caches'
   under 'Home -> Site administration -> Development -> Purge all caches'.
7. Put Moodle out of Maintenance Mode.

Reporting Issues
================
Before reporting an issue, please ensure that you are running the latest version for your release of Moodle.  The primary
release area is located on https://moodle.org/plugins/view.php?plugin=format_grid.  It is also essential that you are
operating the required version of Moodle as stated at the top - this is because the format relies on core functionality that
is out of its control.

All 'Grid format' does is integrate with the course page and control it's layout, therefore what may appear to be an issue
with the format is in fact to do with a theme or core component.  Please be confident that it is an issue with 'Grid format'
but if in doubt, ask.

We operate a policy that we will fix all genuine issues for free.  Improvements are at our discretion.  We are happy to make bespoke
customisations / improvements for a negotiated fee.  We will endeavour to respond to all requests for support as quickly as possible,
if you require a faster service then offering payment for the service will expedite the response.

It takes time and effort to maintain the format, therefore donations are appreciated.

When reporting an issue you can post in the course format's forum on Moodle.org (currently 'moodle.org/mod/forum/view.php?id=47'), 
on Moodle tracker 'tracker.moodle.org' ensuring that you chose the 'Non-core contributed modules' and 'Course Format: Grid'
for the component or contact us direct (details at the bottom).

It is essential that you provide as much information as possible, the critical information being the contents of the format's 
version.php file.  Other version information such as specific Moodle version, theme name and version also helps.  A screen shot
can be really useful in visualising the issue along with any files you consider to be relevant.

Making Changes
==============

Changing the keyboard control code
----------------------------------
To change the 'gridkeys.js' code then you'll firstly need to read: http://docs.moodle.org/dev/YUI/Shifter
it is used to build the source in '/yui/src/gridkeys/js/gridkeys.js' and bring in the 'gallery-event-nav-keys' to build
the YUI module into 'yui/build/moodle-format_grid-gridkeys' and place a back port minified version in '/yui/gridkeys' for
use in Moodle 2.3 and 2.4 versions - so even if you have those versions you will need this Moodle 2.5 version to
make changes.  The compiled YUI module is then loaded in all versions (2.3, 2.4 and 2.5) in 'renderer.php' by the line:
$PAGE->requires->yui_module('moodle-format_grid-gridkeys', 'M.format_grid.gridkeys.init', null, null, true);
So even though the location is different for M2.3 / M2.4 than M2.5 it's the same - that's a M2.5+ thing.  There is no
rocket science to using / learning Shifter, I did so late on a Saturday night whilst half asleep - admittedly with Andrew's
on-line assistance.

Current selected colour
-----------------------
Edit 'styles.css', change the value in the '.course-content ul.gridicons li.currentselected' selector and perform a 'Purge all caches'
or override in your theme.

Current section
---------------
Edit 'styles.css', change the value in the '.course-content ul.gridicons li.current' selector and perform a 'Purge all caches' or
override in your theme.

Files
--------------

* grid/format.php

  Code that actually displays the course view page.

* grid/config.php

  Configuration file, mainly controlling default blocks for the format.

* grid/lang/en/format_grid.php
* grid/lang/ru/format_grid.php
* grid/lang/es/format_grid.php
* grid/lang/fr/format_grid.php

  Language file containing language strings for grid format.

  Note that existing formats store their language strings in the main
  moodle.php, which you can also do, but this separate file is recommended
  for contributed formats.

  Of course you can have other folders as well as just English and Russian
  if you want to provide multiple languages.

* grid/db/install.xml

  Database table definitions.

* grid/db/upgrade.php

  Database upgrade script.

* grid/version.php

  Required for using database tables. The file provides information 
  about plugin version (update when tables change) and required Moodle version.

* grid/styles.css

  The file include in the CSS Moodle generates.

* grid/backup/moodle2/backup_format_grid_plugin.class.php
  grid/backup/moodle2/restore_format_grid_plugin.class.php

  Backup and restore run automatically when backing up the course.
  You can't back up the course format data independently.

Roadmap
=============
1. Improved instructions including Moodle docs.
2. User definable grid row icon numbers - https://moodle.org/mod/forum/discuss.php?d=196716
3. CONTRIB-3240 - Gridview course format more accessible.
4. CONTRIB-4099 - Grid format does not allow the user to set the size of the image / box.
5. Use of crowd funding facility to support development.
6. Continued maintenance of issues: https://tracker.moodle.org/browse/CONTRIB/component/11231.
7. Add in grid format specific capabilities to change things.

Known Issues
=============
1. All listed on https://tracker.moodle.org/browse/CONTRIB/component/11231.
2. Unable to delete a grid icon image.


History
=============

18th December 2012 - Version 2.4.0.1 - Alpha version, not for production servers.
Change by G J Barnard
  1. First alpha release for Moodle 2.4

18th December 2012 - Version 2.4.0.2 - Alpha version, not for production servers.
Change by G J Barnard
  1. Second alpha release for Moodle 2.4

21st December 2012 - Version 2.4.0.3 - Beta version, not for production servers.
Change by G J Barnard
  1. Hopefully eliminated BOM issue (http://docs.moodle.org/24/en/UTF-8_and_BOM) that was causing the failure of the images to display.

3rd January 2013 - Version 2.4.0.4 - Beta version, not for production servers.
Change by G J Barnard
  1. Fixed issue where the grid did not function in 'One section per page mode' on the course settings.

9th January 2013 - Version 2.4.0.5 - Beta version, not for production servers.
Change by G J Barnard
  1. Fixed issue in editimage.php where the GD library needs to be used for image conversion for transparent PNG's.
  2. Perform a 'Purge all caches' under 'Home -> Site administration -> Development -> Purge all caches' after this is installed.

21st January 2013 - Version 2.4.0.6 - Beta version, not for production servers.
Change by G J Barnard
  1. Changes to 'renderer.php' because of MDL-36095 hence requiring Moodle version 2012120301.02 release 2.4.1+ (Build: 20130118) and above.

24th February 2013 - Version 2.4.1 - Stable version.
Change by G J Barnard
  1. Changes because of MDL-37901.
  2. Invisible section fix for Tim Wilde - https://moodle.org/mod/forum/discuss.php?d=218505#p959249.
  3. This version considered 'Stable' from feedback of Theo Konings on CONTRIB-3534.

22nd August 2013 Version 2.4.2 - Stable
Change by G J Barnard
  1.  Fixed icon container size relative to icon size.
  2.  Added 'alt' image attribute information being that of the section name.
  3.  Fixed CONTRIB-4216 - Error importing quizzes with grid course format.
  4.  Fixed CONTRIB-4253 - mdl_log queried too often to generate New Activity tag.  This has been fixed by using the 'course_sections'
      table instead to spot when a new activity / resource has been added since last login.
  5.  Adapted the width of the shade box such that it is dynamic against the size of the window.

12th September 2013 Version 2.4.3 - Stable
Change by G J Barnard
  1.  Back ported CONTRIB-4580.
  2.  Back ported CONTRIB-4579, thanks to all who helped on https://moodle.org/mod/forum/discuss.php?d=236075.
  3.  At the request of Tim St.Clair I've changed the code such that the sections underneath the icons are hidden
      by CSS when JavaScript is enabled so that there is no 'flash' as previously JS would perform the hiding.
  4.  Added 'Upgrading' instructions above.
  5.  Added in code developed by Nadav Kavalerchik to facilitate multi-lingual support for the 'new activity' icon.  Thank
      you Nadav :).
  6.  Partial implementation of CONTRIB-3240 (back port).  Thanks to Andrew Nicols for helping with the YUI module code
      on: https://moodle.org/mod/forum/discuss.php?d=237275.
      This means that it is now possible to navigate using the keyboard with the 'left' / 'right' cursor keys
      being used to perform previous section / next section respectively and the 'esc' key to toggle open / closed
      the shade box.  As a bonus of this change I've added in navigation arrows to the shade box which appear when
      you hover over the middle of the sides - cool eh?
      Initially I also added Shift-TAB (previous section) / TAB (next section) / Enter (open shade box) /
      Shift-Enter (close shade box) keys to but after much deliberation (and logic issues) I have decided that until
      WIA-ARIA is fully understood I'll leave them out.  Once much more information is known I'll put them back in.
      Also thanks to Enrico Canale and Darren Britten of La Trobe University for their support and information.
  7.  'module.js' has been completely reworked so that it is efficient and documented.
  8.  Added Pirate language.
  9.  Changed 'JSON' code in 'module.js' to use the YUI library for JSON to support situations where the 'JSON'
      library is not built into the browser.  Thanks to Colin Taylor for providing information of a situation I
      could not have possibly tested.
 10.  Commented out 'console.log' code in 'module.js'.

Authors
-------
J Ridden - Moodle profile: https://moodle.org/user/profile.php?id=39680 - Web: http://www.moodleman.net
G J Barnard - Moodle profile: moodle.org/user/profile.php?id=442195 - Web profile: about.me/gjbarnard
