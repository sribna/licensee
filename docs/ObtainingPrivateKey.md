# Obtaining a private key

In response to activation and verification requests, the licensor sends a new private key to the
`<public key domain>/key/callback` URL. You can change this path according to the application requirements,
but you cannot change the domain as it's defined by the public key.

    Sribna\Licensee\Checker::setCallbackUrlPath('another/callback/path');
    
The `another/callback/path` path will be passed to the licensor in the request header.

## Private key callback handler

Create `key/callback/index.php` in the application document root:

    use Exception;
    use Sribna\Licensee\Key;
    use Sribna\Licensee\Checker;
    
    (function() {
    
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            exit('Not allowed');
        }
    
        try {
    
            $key = new Key();
            $key->setStorage('absolute/path/to/key/dir');
    
            $checker = new Checker();
            $checker->setKey('PUBLIC_KEY');
    
            $privateKey = file_get_contents('php://input');
            $checker->validatePrivateKey($privateKey);
            
            // Save to file
            $key->setPrivate($privateKey);
    
        } catch (Exception $exception){
            error_log($exception->getMessage());
            exit('Error!');
        }
    
    })();
