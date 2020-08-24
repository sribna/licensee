# Code protection

Code protection comes down to including `Sribna\Licensee\Checker::check()` in critical parts of your application.
These are usually public routes. Keep in mind that the app administrator will need to have access
to the key administration pages.

    use Sribna\Licensee\Checker;
    
    class FrontController {
    
          public function __construct(Checker $checker){
                $checker->setKey('PUBLIC KEY')
                        ->setPrivateKey('PRIVATE KEY')
                        ->setVerificationDomains(['licensor1.example', 'licensor2.example'])
                        ->check();
          }
    }
    
In this case, each time the `FrontController` class is instantiated, the private key will be checked.
Of course, the class must be encoded in IonCube or similar encoder to prevent checker removal.
