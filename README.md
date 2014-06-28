Brady vs. Grey - PHP Version
=================

A project in learning PHP: converting a web app from Python to PHP.

##Note

I have a url to the PostgreSQL database stored in `$_ENV` (Heroku does this for me.)

I also added my YouTube API key to `$_ENV`.  To do this yourself, you will need to

1. [Get your own API key](http://developers.google.com/youtube/v3/getting-started#intro)
2. Run `$ heroku config:set YOUTUBE_API_KEY=<your key>`