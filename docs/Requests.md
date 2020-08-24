# Requests

BODY of requests is a hash constructed as follows:

        $data = "$key|$domain";
        $body = base64_encode(md5($secret . $data) . "|$data");   
where:
 - $key - client's public key
 - $domain - current application domain
 - $secret - secret phrase known to the licensor from the class `Sribna\Licensee\Checker`
 
Make sure that domains for activation and verification are set:

    Sribna\Licensee\Checker::setVerificationDomains()
    Sribna\Licensee\Checker::setActivationDomains()

## Public key activation

**Endpoint:** `key/activate`

**Method:** `POST`

**Body:** Hash

**Success response:**

    Code: 200
    Content: {"success" : "Callback URL where private key was sent"}
    
**Error response:**

    Code: 4xx OR 5xx
    Content: {"error": "Error message"}
    
## Private key verification

**Endpoint:** `key/check`

**Method:** `POST`

**Body:** Hash

**Success response:**

    Code: 200
    Content: {"success" : "Callback URL where private key was sent"}
    
**Error response:**

    Code: 4xx OR 5xx
    Content: {"error": "Error message"}
    
The above URL endpoints are default for [Licensor](https://github.com/sribna/licensor) and can be adjusted:

    Sribna\Licensee\Checker::setVerificationUrlPath()
    Sribna\Licensee\Checker::setActivationUrlPath()
    Sribna\Licensee\Checker::setCallbackUrlPath()
