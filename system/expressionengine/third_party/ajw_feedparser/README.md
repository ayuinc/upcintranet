# AJW Feed Parser #

Feed Parser is a plugin for ExpressionEngine 2 to fetch and read XML-based feeds and API results and display the contents in your templates.

The Magpie RSS/ATOM Parser plugin that comes with EE allows you to read RSS and ATOM files; Feed Parser takes the potential a bit further:

## Features ##

* It can read any XML format not just RSS and ATOM, so you can use it to retrieve more types of data including API responses
* You can access any element or attribute within the XML. All the data within the XML can be displayed or used in conditional ‘if’ statements
* Feeds are cached and you can set how frequently a new file should be fetched
* Common EE variables `{switch}`, `{count}`, `{total_results}` are available
* Standard date formatting can be used
* Parameters include: offset=, limit=
* A debug mode will help you set up the feed for the first time, alerting you of any issues and giving you a list of available parameters

## Usage ##

### Parameters ###

#### url= ####

	url="http://mysite.com/feed.xml

the url of the XML feed to fetch and parse

#### itempath= ####

	itempath="/root/item"

the XPath of the feed's entries (if not set, then the plugin will guess)

#### limit= ####

	limit="10"
		
the maximum number of entries to display

#### offset= ####

	offset="10"

the number of entries to skip at the start of the feed

#### cache_refresh= ####

	cache_refresh="10"

the time in minutes before fetching the file again

#### debug= ####

	debug="true"

adding this parameter will display debugging information (see below)

#### search:field_name= ####

	search:title="Sausages"

filter the results by keyword or phrase (see below for search/filtering)

#### method= ####

	method="curl"
	
provisional support for additional methods of fetching the feed (currently only
supports curl).

#### date= ####

	date="pubDate"
	
tells the plugin that pubDate is a date field.	

See _Dates_ below.

### Debug mode ###

By adding the `debug="true"` parameter, the plugin will display information as it 
fetches and parses the feed.

It will also display a list of available variables that you can use within the plugin.

### Dates ###

If you add a format parameter to a variable the plugin will parse it as a date, 
using the default EE formatting, eg, 

	{published format="%D, %F %d, %Y - %g:%i:%s %a"}

This will work, by default, for _timestamps_. If your date is in a different format, use the 
date= parameter to tell the plugin to convert it to a timestamp.

### Search/filter ###

You can filter the entries by using a search:field parameter, eg:

	search:summary="rhubarb"
  
will only return items that contain the word rhubarb in the summary field.

You can use the '|' character to specify additional terms, eg:

	search:summary="rhubarb|custard" 
	
will find entries containing rhubarb OR custard

You can also preceed the keyword with 'not' to find items that don't contain 
the word(s), eg:

	search:summary="not rhubarb|custard"
	
will find entries that contain neither rhubarb or custard.

## Examples ##

### A simple RSS feed  ###

	{exp:ajw_feedparser 
	    url="http://brandnewbox.co.uk/v8/rss"
	    cache_refresh="60"
	    limit="8"
	}

	<h3 class="title">{title}</h3>
	
	{description}
	
	{/exp:ajw_feedparser} 

### Flickr

Display images and descriptions from your flickr RSS feed. This example demonstrates how to access other XML elements and attributes, and using the date formatting feature.

	{exp:ajw_feedparser 
	    url="http://api.flickr.com/services/feeds/photos_public.gne?id=25509357@N00&lang=en-us&format=rss_200"
	    cache_refresh="60"
	    limit="5"
	}
	
	<h3 class="title">{count}/{total_results}: {title}</h3>
	
	<h4>Tags: {media:category}</h4>
	
	{description}
	
	<p>{media:thumbnail@url}</p>
	
	<p>Posted by <em>{author}</em> on {dc:date.Taken format='%l, %F %j%S, %Y at %g:%i %A'}</p>
	
	{/exp:ajw_feedparser} 

### Tumblr

Show entries from your tumblr blog. You can use conditionals to display the data depending on the type.

	{exp:ajw_feedparser 
	    url="http://the-fan.tumblr.com/api/read"
	    itempath="/tumblr/posts/post"
	    cache_refresh="60"
	}
	
	<h3 class="title">Post from {@date-gmt}</h3>
	
	{if @type == "photo"}<p><img src="{photo-url#4}"/></p>{/if}
	
	{if @type == "quote"}<blockquote><p>{quote-text}</p></blockquote>{/if}
	
	{if @type == "video"}<p>{video-source}</p>{/if}
	
	{if @type == "link"}<p><a href="{link-url}">{link-text}</a></p>{/if}
	
	<p><a href="{@url}">{@url}</a></p>
	
	{/exp:ajw_feedparser}

### Yahoo Weather ###

Yahoo provides an easy to access weather forecast. The URL for your local feed can be found by 
going to:

[Yahoo Developer Network](http://developer.yahoo.com/yql/console/?q=select%20*%20from%20weather.bylocation%20where%20location%3D'kingsbridge%2Cuk'%20and%20unit%3D'c'%3B&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys)

and changing the location='' value to your desired location. Click on Test and copy-and-paste 
the _REST query_ into Feed Parser.

	{exp:ajw_feedparser 
		url="http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20weather.bylocation%20where%20location%3D'kingsbridge%2Cuk'%20and%20unit%3D'c'%3B&diagnostics=true&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys"
		itempath="/query/results"
		cache_refresh="60"
		limit="1"
		}
		
		<p>{weather/rss/channel/item/yweather:condition@text} - {weather/rss/channel/item/yweather:condition@temp}&deg;{weather/rss/channel/yweather:units@temperature}</p>
		
	{/exp:ajw_feedparser}
