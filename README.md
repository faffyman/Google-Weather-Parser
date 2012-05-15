Google-Weather-Parser
=====================

This class will grab a feed from Google Weather
caching is optional and achieved via apc / although it could just as easily be stored in a local database.

the class will try to output cached data rather than refreshing it with every page load.
Only if the data is older than $CACHETIME will it refresh

@author faffyman@gmail.com
@since 1st July 2008

Usage of Google Weather API
Google has never released this APi officially. It's main use is for showing weather on google search pages
and for use within iGoogle widgets.
**N.B.** Google may change or withdraw the the xml feed at anytime.