========
Overview
========

This bundle allows you to use AMF with the Symfony2 Framework.
Main features include:

- handle incoming AMF requests and routing it to the configured services.
- serializer to convert classes to virtual objects with the [JMSSerializerBundle][1]

Installation
------------
Checkout a copy of the code::

    git submodule add https://github.com/tecbot/AMFBundle.git src/Tecbot/AMFBundle

Then register the bundle with your kernel:

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new JMS\AMFBundle\TecbotAMFBundle(),
        // ...
    );

Make sure that you also register the namespaces with the autoloader:

    // app/autoload.php
    $loader->registerNamespaces(array(
        // ...
        'Tecbot'              => __DIR__.'/../vendor/bundles',
        // ...
    ));

Note: The serializer needs the [JMSSerializerBundle][1].

Configuration
-------------
Below is the default configuration, you don't need to change it unless it doesn't
suit your needs:

    tecbot_amf:
        use_serialization: false

Usage
-----

Configuring services and class mappings

- All mapped services must be added to the folder "Amf" in your bundle (e.g Foo\BarBundle\Amf\FooService).
- All public methods wich you want to access from Amf needs the "Action" suffix like controllers.

    tecbot_amf:
        services: # Services
            FooService: FooBarBundle:Foo # Map FooService (Actionscript alias) to Foo\BarBundle\Amf\Foo class
        mapping: # Class mapping. Map FooClassVO (Actionscript alias) to Foo\BarBundle\VO\FooClassVO
            FooClassVO: Foo\BarBundle\VO\FooClassVO

Add routing to the gateway controller

    # path is "/gateway"
    _tecbot_amf:
        resource: "@TecbotAMFBundle/Resources/config/routing.yml"

    # or when you want a another path
    amf:
        pattern: /amf
        defaults: { _controller: tecbot_amf.controller.gateway:gatewayAction }

Example App
-----------

See [AMFBundle-sandbox][2]

[1]: https://github.com/tecbot/JMSSerializerBundle
[2]: https://github.com/tecbot/AMFBundle-sandbox