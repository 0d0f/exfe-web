http 'https://api.dropbox.com/1/metadata/?oauth_token_secret=8t83tviyvr4cyh1&oauth_token=095dr27hlfyturp&uid=856731&oauth_consumer_key=5exqb4gosclu5x0&oauth_signature=kr3oga1il0eu4mn'

http https://api.dropbox.com/1/metadata/ Authorization: 'OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="5exqb4gosclu5x0", oauth_token="095dr27hlfyturp", oauth_signature="kr3oga1il0eu4mn&8t83tviyvr4cyh1"'














ok:

http https://api.dropbox.com/1/metadata/dropbox Authorization:'OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="5exqb4gosclu5x0", oauth_token="095dr27hlfyturp", oauth_signature="kr3oga1il0eu4mn&8t83tviyvr4cyh1"' | subl


http https://api.dropbox.com/1/metadata/dropbox/Camera%20Uploads Authorization:'OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="5exqb4gosclu5x0", oauth_token="095dr27hlfyturp", oauth_signature="kr3oga1il0eu4mn&8t83tviyvr4cyh1"' | subl


http https://api-content.dropbox.com/1/files/dropbox/Camera%20Uploads/2013-01-05%2011.46.24.jpg Authorization:'OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="5exqb4gosclu5x0", oauth_token="095dr27hlfyturp", oauth_signature="kr3oga1il0eu4mn&8t83tviyvr4cyh1"'


http https://api-content.dropbox.com/1/thumbnails/dropbox/Camera%20Uploads/2013-01-05%2011.46.24.jpg Authorization:'OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="5exqb4gosclu5x0", oauth_token="095dr27hlfyturp", oauth_signature="kr3oga1il0eu4mn&8t83tviyvr4cyh1"'
