# Code protection

To protect some parts of your app simply call `Sribna\Licensee\Checker::check()` there.
Keep in mind that the app administrator will need to have access to the key administration pages.

    use Sribna\Licensee\Checker;
    
    class FrontController {
    
          public function __construct(Checker $checker){
                $checker->setKey('PUBLIC KEY')
                        ->setPrivateKey('PRIVATE KEY')
                        ->setVerificationDomains(['licensor1.example', 'licensor2.example'])
                        ->check();
          }
    }
    
In this case, we check the private key each time the `FrontController` class is instantiated.
Of course, the class must be encoded in IonCube or similar encoder to prevent removing the checker.
