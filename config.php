<?php
// runtime env
define('STATIC_CODE_TIMESTAMP', 1364888597);

// debug
define('DEBUG',    true);
define('JS_DEBUG', true);

// database
# define('DBHOST',   '127.0.0.1');
# define('DBUSER',   'leask');
# define('DBPASSWD', 'zatcby229az');
# define('DBNAME',   'exfe_dev');
# ---
# define('DBHOST',   '192.168.1.143');
# define('DBUSER',   'root');
# define('DBPASSWD', 'toor');
# define('DBNAME',   'exfe_dev');
# ---
define('DBHOST',   '192.168.1.89');
define('DBUSER',   'root');
define('DBPASSWD', '135qetadg');
define('DBNAME',   'exfe_dev');

// exfee quota
define('EXFEE_QUOTA_SOFT_LIMIT', 12);
define('EXFEE_QUOTA_HARD_LIMIT', 50);

// domian
define('ROOT_DOMAIN', '.0d0f.com');
define('SITE_URL',    'http://leask.0d0f.com');
define('IMG_URL',     'http://img.leask.0d0f.com');
define('API_URL',     'http://api.leask.0d0f.com');
define('IOM_URL',     'http://panda.0d0f.com:1234');
define('STREAMING_API_URL', 'http://panda.0d0f.com:23333');
# define('STREAMING_API_URL', 'http://ec2-54-251-199-79.ap-southeast-1.compute.amazonaws.com:23333');

// proxy
define('PROXY_TYPE', 'http');
define('PROXY_ADDR', '192.168.1.89');
define('PROXY_PORT', '6489');

// upload
define('IMG_FOLDER', 'eimgs');

// app
define('CLIENT_IOS_VERSION',         '2.0');
define('CLIENT_IOS_DESCRIPTION',     '');
define('CLIENT_IOS_URL',             'https://itunes.apple.com/us/app/exfe/id514026604');
define('CLIENT_ANDROID_VERSION',     '');
define('CLIENT_ANDROID_DESCRIPTION', '');
define('CLIENT_ANDROID_URL',         '');
define('APP_SCHEME',                 'exfe');

// cache
define('REDIS_SERVER_ADDRESS', '127.0.0.1');
define('REDIS_SERVER_PORT',    '6379');
define('REDIS_CACHE_ADDRESS',  '127.0.0.1');
define('REDIS_CACHE_PORT',     '6379');

// bus
define('EXFE_AUTH_SERVER',  'http://panda.0d0f.com:23333');
define('EXFE_GOBUS_SERVER', 'http://panda.0d0f.com:23334');

// i18n
define('INTL_RESOURCES', '/Users/leask/Documents/Working/exfe/exfeweb/exfeweb/intl');

// SALT
define('EXFE_PASSWORD_SALT', 'f(#)u&^c!k*G@F%W81_&0=ro-ifl+c');

// oauth - twitter
define('TWITTER_CONSUMER_KEY',     'VC3OxLBNSGPLOZ2zkgisA');
define('TWITTER_CONSUMER_SECRET',  'Lg6b5eHdPLFPsy4pI2aXPn6qEX6oxTwPyS0rr2g4A');
define('TWITTER_OAUTH_CALLBACK',   SITE_URL .'/OAuth/twitterCallBack');
define('TWITTER_API_URL',          'http://api.twitter.com/1');
define('TWITTER_OFFICE_ACCOUNT',   '0d0fdev');

// oauth - facebook
define('FACEBOOK_APP_ID',          '119145884898699');
define('FACEBOOK_SECRET_KEY',      '2413c9b93341b6c648dd8c343f0a9d4e');
define('FACEBOOK_OAUTH_CALLBACK',  SITE_URL .'/OAuth/facebookCallBack');

// oauth - dropbox
define('DROPBOX_APP_KEY',          '5exqb4gosclu5x0');
define('DROPBOX_APP_SECRET',       'kr3oga1il0eu4mn');
define('DROPBOX_OAUTH_CALLBACK',   SITE_URL .'/OAuth/dropboxCallBack');

// oauth - dropbox
define('INSTAGRAM_CLIENT_ID',      '821a4ee86a0a4f80a5d0390a8180e08e');
define('INSTAGRAM_CLIENT_SECRET',  'c63497820adf443c80e570a0d61fe163');
define('INSTAGRAM_REDIRECT_URI',   SITE_URL .'/OAuth/instagramCallBack');

// oauth - flickr
define('FLICKR_KEY',               '8351d6b143f39714db5ea5732ab76548');
define('FLICKR_SECRET',            '3927a68489806300');
define('FLICKR_OAUTH_CALLBACK',    SITE_URL .'/OAuth/flickrCallBack');

// oauth - google
define('GOOGLE_APP_NAME',          'EXFE');
define('GOOGLE_MAP_KEY',           'AIzaSyAgYKtU2lpDg_HYH_rP2MIna0DFyIoEGMs');
define('GOOGLE_CLIENT_ID',         '744297824584-gjg6alcou6h2nnde0q2qh17jakiaudqi.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET',     '_40miTtyXZdl2BapShN71g-O');
define('GOOGLE_TOKEN_LIFE',        1800); // 60 * 30
define('GOOGLE_REDIRECT_URIS',     SITE_URL .'/oauth/googlecallback');

// oauth - foursquare
define('FOURSQUARE_CLIENT_KEY',    'TGQU0UKUHS3H5WYG0KAWPRU2FY0RYVYSD3JJPXJTCKXTGG3K');
define('FOURSQUARE_CLIENT_SECRET', 'DEOLYIPX0J1EWCSCY03CMO3DH52H54KKA2HPSXHTATWAXWDJ');
