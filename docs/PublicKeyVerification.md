# Public key verification

Make sure that:
1. [Callback handler](ObtainingPrivateKey.md) is set up.
2. [Secret](SettingSecret.md) is already exists in the `Sribna\Licensee\Checker` class


    use Sribna\Licensee\Checker;
    
    $checker = new Checker();
    $checker->setKey('PUBLIC_KEY');
    $checker->setVerificationDomains(['licensor.example']);
    
    if($checker->requestVerification()->getStatusCode() === 200){
        // Activated!
    }
