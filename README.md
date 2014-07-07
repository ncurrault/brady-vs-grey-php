Brady vs. Grey - PHP Version
=================

A project in learning PHP: converting a web app from Python to PHP.

## Things in $_ENV

### A url to the PostgreSQL database. (Heroku does this for me.)

### My YouTube API key.  If you with to replicate this, you will need to

1. [Get your own API key from Google](http://developers.google.com/youtube/v3/getting-started#intro)
2. Run `$ heroku config:set YOUTUBE_API_KEY=<your key>`

### An updating "secret"
So that the right to trigger a processing-intensive update is restricted, I have a secret key.  If you wish to replicate this, choose your own key and run `$ heroku config:set UPDATE_SECRET=<your "secret">`.

## Enjoy!