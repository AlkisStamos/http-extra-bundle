HttpExtraBundle
===============

Introduction
------------
HttpExtraBundle adds some extra features to the Symfony3 framework. Its main purpose is to auto bind request data to
controller parameters and automate procedures like deserializing/serializing, resolve query parameters, validate and
resolve the request body to custom type controller arguments, autohandle response formats and headers through annotation
configurations.

Requirements
------------
* PHP 5.4 or higher
* Symfony framework 3.1 or higher

Installation
------------
1 Install the bundle using composer

.. code-block :: bash

    composer require alks/http-extra-bundle

2 Enable the bundle in your symfony project

.. code-block :: php

    <?php
    // app/AppKernel.php
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Alks\HttpExtraBundle\HttpExtraBundle(),
            // ...
        );
    }

Configuration
-------------
The bundle does not require any initial configuration. It comes with some predefined options which you may tweak. For
detailed configuration options check the :doc:`configuration options <configuration>`.

Usage
-----
Features of the bundle are enabled using several annotations to controller methods. Please note that the bundle so far
uses *only annotation configuration*. For example say we have a method for the /posts that returns paginated data of the
all posts stored in the database, using query parameters to apply filters. The method declaration in a symfony3 project
would look like this:

.. code-block :: php

    /**
     * @Route("/posts", methods={"GET"})
     */
    public function getPostsAction()
    {
        //Implementation here
    }

Normally we would parse pagination data from the symfony request. Using the bundle we may instruct the framework that the
action requires extra properties from the request.

.. code-block :: php

    /**
     * @Route("/posts", methods={"GET"})
     * @Http\RequestParam(name="p", bindTo="page")
     * @Http\RequestParam(name="l", bindTo="limit")
     */
    public function getPostsAction($page, $limit=10)
    {
        //Implementation here
    }

In the above example we expect that the query string may have two parameters ('l' and 'p') which may be bound to the
arguments page and limit.

Notice that the limit argument has a default value and the request won't fail if omitted from the query string but the
page argument is required and if missing the client would get a Bad Request response.

Same value request parameters may be grouped like this:

.. code-block :: php

    /**
     * @Route("/posts", methods={"GET"})
     * @Http\RequestParams({"page","limit"})
     */
    public function getPostsAction($page, $limit=10)
    {
        //Implementation here
    }

The above action expects two query string parameters named page and limit.

In the example below the action handles and stores a comment to the local database:

.. code-block :: php

    /**
     * @Route("/comment", methods={"POST"})
     * @Http\RequestBody(bindTo="comment", validate=true)
     */
    public function storeCommentAction(Comment $comment)
    {
        //Implementation here
    }

The bundle will parse and deserialize the request body, using the serializer defined in the configuration and will auto
validate the content of the object. If the content is not valid (or the request body may be empty) the client may get
a Bad Request response. Otherwise the data will be ready to use in the comment argument.

For more examples and use cases check the :doc:`Usage <usage>`.