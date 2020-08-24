# Principle of operation

There are two types of keys used: public and private. The public key is issued to the client by the licensor when choosing a product.
The private key is stored on the client's server. Essentially, the private key is a BASE64 encoded MD5 hash, constructed in the following way:

        $jsonSettings = json_encode($settings);
        $privateKey = base64_encode(md5($jsonSettings . $secret) . "|$jsonSettings");
        
The `$settings` array has the following required keys:

    - key - client's public key
    - domain - domain to which the public key is bound
    - shutdown_offset - time in seconds that will elapse from the moment the private key expires until the application is off
    - expires_at - expiration time of the private key

The `$secret` string defined in the `Sribna\Licensee\Checker::$secret` property and isn't accessible from outside.
Of course, this file must be encoded via IonCube, Zend Guard, etc. Make sure the secret is also known to the [licensor](https://github.com/sribna/licensor/blob/master/docs/recipes/secrets.md)

As you can see, the private key requires periodic verification. Every time `Sribna\Licensee\Checker::check()` is called
the current time is checked against the time under `expires_at` key and, if needed, a fresh private key is requested.
If the private key is not updated during the time defined under the `shutdown_offset` key, the application is shutdown.

### Important points
 - The file containing the `Sribna\Licensee\Checker` class must be encoded
 - All files with a private key check (i.e. those who have `Sribna\Licensee\Checker::check()` call) must be encoded too
 - The secret phrase should be presented in the class `Sribna\Licensee\Checker` and known to the licensor
