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

  6. Add this configuration if you want to use the `security component` (currently disabled!):
          
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

See [AMFBundle-sandbox][1]

[1]: https://github.com/tecbot/AMFBundle-sandbox