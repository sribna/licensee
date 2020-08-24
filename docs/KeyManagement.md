# Key management

It's up to you how to store public and private keys. You can use either database of simple file storage.
The `Sribna\Licensee\Key` class helps to store your keys in simple files.

    use new Sribna\Licensee\Key;
    
    $key = new Key;
    $key->setStorage('/absolute/path/to/key/storage/directory');
    
    // Add public key
    $key->set('KEY_CODE');
    
    // Add private key
    $key->setPrivate('Private key');
    
    // Get public key
    $key->get();
    
    // Get private key
    $key->getPrivate();
    
    // Delete public key
    $key->delete();
    
    // Delete private key
    $key->deletePrivate();
    
    
