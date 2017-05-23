Configuration
=============

The bundle comes with a predefined configuration using the minimum specs. Though this may be enough for many systems,
some applications may find useful some of the features that are turned off by default.

Negotiation
-----------

Though turned off by default, the bundle uses content negotiation (with the willdurand/negotiation package). For now
negotiation options are limited to type negotiation (Accept, Content-Type). In order to turn the negotiator on the
add the following configuration to your config.yml file:

.. code-block :: yaml

    alks_http_extra:
        negotiation:
            enabled: true

For the Type negotiation to make sense a list of acceptable types should be defined in the configuration. By default the
bundle sets the json and xml types in its configuration, so by only enabling the negotiation both of those types should be
available. In order provide custom types (and override the default ones) you need to add the following to your config.yml:

.. code-block :: yaml

    alks_http_extra:
        negotiation:
            enabled: true
        types:
            - { name: custom, value: ["text/custom","application/custom"] }

In order to keep the default types and append new ones use the following configuration:

.. code-block :: yaml

    alks_http_extra:
        negotiation:
            enabled: true
        append_types:
            - { name: custom, value: ["text/custom","application/custom"] }

Serializer
----------

By default the serialization/deserialization features are enabled and the bundle requires a serializer to work with in
order to handle request body data. It works both with the symfony serializer and the JMSSerializer out of the box. In
order to turn off serialization features use the following configuration:

.. code-block :: yaml

    alks_http_extra:
        serializer:
            enabled: false

If you have your own serializer working in your project, just make sure it implements the *Symfony\Component\Serializer\SerializerInterface*.
In order to instact the bundle to use a different serializer than the default one of your project, just define a service in
your services.yml named *alks_http.serializer* and again make sure that it implements the *Symfony\Component\Serializer\SerializerInterface*.

Validator
---------

In order to enable auto validation features provide the following configuration

.. code-block :: yaml

    alks_http_extra:
        validator:
            enabled: true

This will just enable the validator inside the bundle but it will not auto validate each request. You will still have to
enable it wherever you want to force auto validation by using annotations.