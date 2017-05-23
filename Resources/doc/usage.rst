Usage
=====

The bundle uses **only** annotations to configure controller actions with extra metadata.

The main annotations are:

- RequestParam
- RequestBody
- RequestData
- Response

RequestParam
------------

Refers to query string parameters. Each RequestParam must define name (the name of the parameter in the request) and
bindTo properties (the name of the action argument which will take the parameter value). For simplicity the default value
of a RequestParam refers to the bindTo property and if no name property is defined the bundle assumes that the query
parameter will have the same name as the controller argument:

.. code-block :: php

    @RequestParam("id")

Will match a "?id=foo" to the $id argument.

A RequestParam can also be used to convert a query string directly to a doctrine entity (just like symfony does in route
parameters with the ParamConverterInterface) using the repository, findBy, manager properties:

.. code-block :: php

    /**
     * @Route("/user", methods={"GET"})
     * @RequestParam(name="username", bindTo="user", findBy="username")
     */
    public function userRelatedAction(User $user)
    {
        //Implementation code here
    }

The above configuration expects a query string like "?username=..." and will convert the query param username to a User
entity (running a findBy username doctrine query).

RequestBody
-----------

Refers to the body of the request. Usually the body of Http request is a serialized version of a populated class instance.
The bundle can deserialize the request into a class instance (using the project's serializer) and directly mount the instance
to a controller argument:

.. code-block :: php

    /**
     * @Route("/user", methods={"POST"})
     * @RequestBody(bindTo="user")
     */
    public function createUserAction(UserDTO $user)
    {
        //Implementation code here
    }

If you want to auto validate the object before passing it to the controller use the validate option:

.. code-block :: php

    /**
     * @Route("/user", methods={"POST"})
     * @RequestBody(bindTo="user", validate=true)
     */
    public function createUserAction(UserDTO $user)
    {
        //Implementation code here
    }

Note that if the validator returns any errors, the execution will stop with a Bad Request response.

RequestData
-----------

Works the same as the RequestParam annotation except that it uses data from the request property of the Symfony request.
It may be useful for applications that work with standard html forms or the default jQuery ajax calls. The request data
though they come in an array format (ParameterBag in Symfony) the can be transformed into classes using the bundles normalizer
by enabling the option in the configuration:

.. code-block :: yaml

    alks_http_extra:
        normalizer:
            enabled: true

and defining a method using the RequestData annotation:

.. code-block :: php

    /**
     * @Route("/user", methods={"POST"})
     * @RequestData(bindTo="user", validate=true)
     */
    public function createUserAction(UserDTO $user)
    {
        //Implementation code here
    }

The annotation may also work for a single data property with the name/bindTo options.

Responses
---------

The bundle is able to handle controller responses without putting much logic to it. If any controller action returns
a non Response type the bundle will try to serialize the data and send it back with a proper Content Type header. This
may be useful when working on a **REST api with content negotiation** or if you just want controller methods to return
info and data based on the logic they implement and handle the Response and format generation elsewhere.

In order to further automate this logic the bundle uses the Response annotation which may contain information to include
in the final Response object. The Response also includes a flat array context property which may be used to pass context
to the serializer.

.. code-block :: php

    /**
     * @Route("/user", methods={"GET"})
     * @Response(type="json", context={"groups":{"details"}}, headers={
     *      @ResponseHeader(name="Custom-Header", value="a_custom_value")
     * })
     */
    public function userRelatedAction(User $user)
    {
        //Implementation code here
        return $user;
    }

Note that the annotation above forces a json response. If omitted and the negotiation option is enabled in the bundle,
the User Entity will be serialized in the format negotiated by the Accept header of the request.