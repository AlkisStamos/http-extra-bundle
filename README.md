# HttpExtraBundle
Adds the following annotations to the Symfony3 framework:
* **@RequestParam** : Matches any query parameter to an action argument.
* **@RequestBody** : Matches the request body (content) to an action argument.
* **@RequestData** : Matches any request data parameter ($request->request) to an action argument.
* **@Response** : Provide additional info (headers etc) for the response object.

The bundle also:
* automatically deserializes and validates the request body/data to an action argument
* generates a response when an action returns anything but a Response object
* resolves doctrine entities from a query parameter
* automatically denormalizes the request content to a class

which (if properly configured) leaves the controllers to work only with valid and structured request data. Useful when
you like working with DTOs and you are not a big fan of forms.

Requirements
-------------
* PHP 5.4 or higher
* Symfony 3.1 or higher

The bundle uses value resolvers which were introduced in Symfony 3.1

Basic examples
-------------
Please note the examples below only demonstrate the bundles basic usage they are not suitable for proper application use.
For more detailed examples please check the documentation.
```php
<?php
use Alks\HttpExtraBundle\Annotation as Http;
class FooController extends \Symfony\Bundle\FrameworkBundle\Controller\Controller
{
    /**
     * This will match the "/user?username=foo" and will automatically call the user repository to find a user with the foo
     * username.
     * 
     * @Route("/user", methods={"GET"})
     * @Http\RequestParam(name="username", bindTo="user", repository="AppBundle\Repository\UserRepository")
     * @return User 
     */
    public function getUserByUsernameAction(User $user)
    {
        return $user;
    }
    
    /**
     * This will match a GET request with optional page and limit query parameters like "/posts?page=3&limit=20"
     * 
     * @Route("/posts", methods={"GET"})
     * @Http\RequestParams({"page","limit"})
     * @Http\Response(context={"groups":{"list"}}, type="json")
     */
    public function getPostsAction($page=1, $limit=10)
    {
        return $this->getDoctrine()->getRepository('AppBundle:Post')->findAllByPage($page,$limit);
    }
}
```
