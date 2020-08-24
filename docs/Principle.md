# Principle of operation

There are two types of keys used: public and private. The public key is issued to the client by the licensor when choosing a product.
The private key is stored on the client's server. In fact, the private key is a BASE64 encoded MD5 hash, constructed in the following way:

        $jsonSettings = json_encode($settings);
        $privateKey = base64_encode(md5($jsonSettings . $secret) . "|$jsonSettings");
        
The `$settings` array has the following required keys:

    - key - client's public key
    - domain - domain to which the public key is bound
    - shutdown_offset - time in seconds that will elapse from the moment the private key expires until the application is off
    - expires_at - expiration time of the private key

The `$secret` is a secret phrase written in the class `Sribna\Licensee\Checker`. Of course, this file
must be encoded via IonCube, Zend Guard, etc. Make sure the secret is also known to the [licensor](https://github.com/sribna/licensor/blob/master/docs/recipes/secrets.md)

As you can see, the private key requires regular verification. Every time `Sribna\Licensee\Checker::check()` is called
the current time on the server is checked against the time in `expires_at` and, if necessary, a fresh private key is requested.
If the private key is not updated during `shutdown_offset`, the application is shutdown.

### Important points
 - The file with class `Sribna\Licensee\Checker` must be encoded
 - All files with a private key check (i.e. those who have `Sribna\Licensee\Checker::check()` call) must be encoded too
 - The secret phrase should be written in the class `Sribna\Licensee\Checker` and known to the licensor
