<?php
  //
  // settings.php
  //
  //  This file loads all of the standard (normally not changed) settings for
  //  this appilication.  The settings are held in a database table and can
  //  be changed from the management screens.  The settings are placed in a
  //  global called SETTINGS[] and are accessed by name.
  //

// note that this "include" file calls a database call immediately to load the setings.

$SETTINGS = dbSettingsLoad();
?>