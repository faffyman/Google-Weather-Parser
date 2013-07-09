
*GOOGLE KILLED THE WEATHER API*
===============================

This class will no longer work. Since the weather API was primarily for an iGoogle weather widget and google killed iGoogle, they have also withdrawn support for their weather API.

[The Next Web](http://thenextweb.com/google/2012/08/28/did-google-just-quietly-kill-private-weather-api/) has an article about it's demise.





Google-Weather-Parser
-----------------------

This class will grab a feed from Google Weather.

Caching is optional and achieved via apc / although it could just as easily be stored in any local database or key value store.

The class will try to output cached data rather than refreshing it with every page load.
Only if the data is older than $CACHETIME will it refresh


Usage of Google Weather API
------------------------------
Google has never released this API officially. 

It's main use is for showing weather on google search pages and for use within iGoogle widgets.

**N.B.** Google may change or withdraw the the xml feed at anytime.


