# database-redis-async
Async Pho adapter for database service, Redis.

This adapter does not block the the application. Please note, not all of the methods provided here are truly async. 

The truly async ones are:
* set
* del
* expire

All others are sync.


