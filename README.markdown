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

  3. Extends your Kernel from Tecbot\AMFBundle\Amf\Kernel

          // app/AppKernel.php
          class BeachvilleKernel extends Tecbot\AMFBundle\Amf\Kernel
          {
              // ...
          }

  4. Add this bundle to your application's kernel:

          // app/AppKernel.php
          public function registerBundles()
          {
              return array(
                  // ...
                  new Tecbot\AMFBundle\TecbotAMFBundle(),
                  // ...
              );
          }

  5. Configure the `amf` service in your config:

          # app/config/config.yml
          amf.config:
              services: # Services
                  FooService: FooBarBundle:Foo # Map FooService (alias for AMF) to FooBarBundle:Foo class
              mapping: # class mapping. Actionscript (FooClassVO) to PHP (Foo\BarBundle\VO\FooClassVO)
                  FooClassVO : Foo\BarBundle\VO\FooClassVO

  6. Add this configuration if you want to use the `security component`:
          
          # app/config/config.yml
          # Create providers in the security config (add SecurityBundle to your application's kernel)
          security.config:
              providers:
                  foo_provider:
                      id: foo.bar.provider

          amf.config:
              services: # Services
                  FooService: FooBarBundle:Foo # Map FooService (alias for AMF) to FooBarBundle:Foo class
              mapping: # class mapping. Actionscript (FooClassVO) to PHP (Foo\BarBundle\VO\FooClassVO)
                  FooClassVO : Foo\BarBundle\VO\FooClassVO
              security:
                  provider: foo_provider # reference to the provider name
                  firewalls:
                      public:
                          # Firewall for the method bar in the FooService
                          service: FooService
                          method: bar
                          provider: foo_provider
                  access_control:
                      # only users with the role IS_AUTHENTICATED_FULLY has access to the method bar in the FooService
                      - { service: FooService, method: bar, role: [IS_AUTHENTICATED_FULLY] }

## Use

### PHP

add all your AMF services into FooBundle\AMF\ and

    # add the Service suffix to a class e.g. FooService and extends Tecbot\AMFBundle\Amf\Service\Service
    class FooService extends Tecbot\AMFBundle\Amf\Service\Service

    # add the Action suffix to a methode e.g. authenticateAction()
    public function authenticateAction()

    # and finally to handle AMF request, change the handle function in app*.php to handleAmf
    $kernel->handleAmf(Request::createfromGlobals())->send();

### Flex

    # create RemoteObject
    var userService:RemoteObject = new RemoteObject([DESTINATION]);
    userService.channelSet = [CHANNEL];
    userService.source = "FooService";

    # call remote service
    var asyncToken : AsyncToken = userService.authenticate([PARAMS]);
    asyncToken.addResponder(new Responder(this.onAuthenticateResultHandler, this.onAuthenticateFaultHandler));