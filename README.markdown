# WordPress XML-RPC validator #

WordPress plugin that checks the validity of the XML-RPC Endpoint of WordPress sites.

This plugin is deployed on the following test site: http://www.eritreo.it/wp31es/

## Details ##

This plugin does the following:

= First Step
- Download the content at the URL specified on the web form
- Find-download-parse the RSD Document
- Test the XML-RPC endpoint calling system.listMethods
- Verify that all methods are all available
- Start a real call using dummy credentials and verify that the XML-RPC service is active

= Steps Two
- Get the user blogs
- Start few XML-RPC calls and analyses the server response
- Upload a small picture by using the metaWeblog.newMediaObject call (The picture is not published or attached to any post, but it will be available in the Media Library)

## External libs ##

This plugin uses the following libs:

- https://github.com/daniloercoli/php-mobile-useragent