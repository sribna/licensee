# Obtaining a private key

In response to activation and verification requests, the licensor sends a new private key to the URL
`<public key domain>/key/callback`. You can change the `key/callback` path according to the requirements of the application,
but you cannot change the domain as it is defined by the public key.

    Sribna\Licensee\Checker::setCallbackUrlPath('another/key/callback/path');
    
The path will be passed to the licensor in the request header.

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
