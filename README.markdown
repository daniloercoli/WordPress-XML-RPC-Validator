# WordPress XML-RPC validator #

WordPress plugin that checks the validity of the XML-RPC Endpoint of WordPress sites.

If you're having throubles login into your site by using one of the WordPress mobile apps, this plugin can help you to find the real cause of the issue.  A live version of the plugin is deployed on the following site: http://xmlrpc.eritreo.it
Just insert your address there, and a check will be stared against your site. (No data will be collected on our side. I completely delete the logs on the server without even taking a look at them).

**Advanced usage**

It's possible to launch the validator by passing parameters to it.

Available parameter are _site_url_ and _user_agent_.
EX: http://xmlrpc.eritreo.it?user_agent=my-user-agent-here&site_url=daniloercoli.com

**Note**

Plugins and incompatible themes can also cause issues when using your site on a mobile app.
There’s a list of known plugin conflicts here: http://ios.forums.wordpress.org/topic/app-blocking-plugin-list?replies=1#post-5985.

If deactivating all the plugins doesn’t help then suggest they try a default theme. Also check what user role they’re signing in with.
Sometimes signing in as an unusual user (something other than administrator) can cause strange things with the app.


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