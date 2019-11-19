# SPI Access Sync

## Endpoints

- POST /webhook  - The endpoint where the payload is received

The payload is verified in the `InPlayerVerificationMiddleware.php`. 
If the signature matches, then `WebhookController@store` saves the payload and revokes then grants access to the other platform.


### Test

- WebhookTest.php

