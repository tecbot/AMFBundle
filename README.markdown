AMF for Symfony2 (not yet stable)

## Installation

  1. Add Tecbot\AMFBundle to your src/ dir

          git submodule add git://github.com/tecbot/AMFBundle.git src/Tecbot/AMFBundle
    
  2. Add the Tecbot namespace to your autoloader

          // src/autoload.php
          $loader->registerNamespaces(array(
              'Tecbot' => __DIR__,
              // your other namespaces
          ));

  3. Add this bundle to your application's kernel:

          // app/AppKernel.php
          public function registerBundles()
          {
              return array(
                  // ...
                  new Tecbot\AMFBundle\TecbotAMFBundle(),
                  // ...
              );
          }

  4. Update your config

          # app/config/config.yml
          amf.config:
              services: # Services
                  UserService: UserBundle:User # Map UserService id from AMF to UserBundle:UserService class
              class_map: # class mapping. Actionscript (UserVO) to PHP (Application\UserBundle\AMF\VO\UserVO)
                  UserVO: Application\UserBundle\AMF\VO\UserVO

## Use

### PHP

add all your AMF services into YourBundle\AMF\ and

    # add the Service suffix to a class e.g. UserService and extends Tecbot\AMFBundle\Service\Service
    class UserService extends Service

    # add the Action suffix to a methode e.g. authenticateAction()
    public function authenticateAction()

### Flex

    # create RemoteObject
    var userService:RemoteObject = new RemoteObject([DESTINATION]);
    userService.channelSet = [CHANNEL];
    userService.source = "UserService";

    # call remote service
    var asyncToken : AsyncToken = userService.authenticate([PARAMS]);
    asyncToken.addResponder(new Responder(this.onAuthenticateResultHandler, this.onAuthenticateFaultHandler));