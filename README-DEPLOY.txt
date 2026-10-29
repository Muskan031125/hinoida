HiNoida — deploy package, 13 August 2026
========================================

Covers everything changed on 12 and 13 August: 57 code files and one SQL file
that puts the live data on the server.


WHAT IS IN HERE
---------------

  app/        57 changed PHP files — controllers, helpers, views, config
  assets/     theme.css, dash.css, favicon
  tools/      the scripts that built the SQL (not needed on the server)

  hinoida-live-complete.sql    24 MB — the whole live database


ORDER
-----

1. DATABASE

   phpMyAdmin -> select the database the site uses -> Import ->
   hinoida-live-complete.sql -> Go.

   The file drops every existing table first, so whatever demo data is in
   there now is replaced. What remains is exactly the shared live database:
   1,847 listings, 489 users, 118 categories, 215 localities, 208 posts.

   You may see "Duplicate column name" on a few ALTER lines near the end.
   That is expected and harmless — MySQL has no "ADD COLUMN IF NOT EXISTS",
   so those lines are written to run whether or not the column is already
   there. The import continues.

   Prefer a NEW database rather than overwriting the one you have, so you
   can switch back by changing one line of .env if anything looks wrong.

2. FILES

   Upload app/ and assets/ over the top of what is on the server.
   Do NOT upload .env — the server's own copy has the live credentials.
   tools/ is only needed if you want to rebuild the SQL; it can be skipped.

3. CHECK

   Visit https://new.hinoida.com/ and then /news.

   If you can run CLI (SSH or cPanel Terminal):

       php spark db:prepare

   It adds anything missing and prints what the database holds. Safe to run
   more than once. If you cannot run CLI, loading /news does the same work.


PHOTOS
------

Listing photos are served from https://new.hinoida.com/uploads/... because the
image files live on that server, not on osob.in. The database only stores
filenames.

To serve them from osob.in instead, copy the uploads/ folder across, then
clear the `media_base` row in the settings table:

    UPDATE settings SET description = '' WHERE type = 'media_base';

Until then the images load from new.hinoida.com and display correctly.


WHAT TO EXPECT
--------------

  /events and /offers look sparse — only 11 of 95 events and 5 of 65 offers
  are published in the live data (status = 1). That is the data, not a fault.

  398 of the 1,847 listings are pending, so 1,449 show publicly.


ROLLING BACK
------------

Point .env at the previous database. The code works against both the old
and the new schema — it checks which columns exist rather than assuming.
