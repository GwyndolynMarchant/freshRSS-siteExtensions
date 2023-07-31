# Gwyn's FreshRSS Danbooru Site Extension

## Main features
- Uses API calls to recover full post information
- Tags are called through API and properly populated
- Full size images and videos are embedded
- Artist's comments are retrieved through a seperate API call. They are embedded in English if present, falling back to native language if nothing else is available.

## Limitations
Using public API calls so pausing one (1) second before retrieving data, for politeness. This means that ingesting a post will take two (2) seconds, because a seperate call is needed for artist comments. Initial feed ingestion takes a while, but the rest should be seamless behind the scenes after that.