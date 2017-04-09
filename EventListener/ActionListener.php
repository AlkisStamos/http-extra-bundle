<?php

namespace Alks\HttpExtraBundle\EventListener;
use Doctrine\Common\Annotations\Reader;
use Alks\HttpExtraBundle\Annotation\RequestParam;
use Alks\HttpExtraBundle\Annotation\RequestParams;
use Alks\HttpExtraBundle\Annotation\ActionParam;
use Alks\HttpExtraBundle\Annotation\Response;
use Alks\HttpExtraBundle\Resolver\ConfigurationResolver;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Listener class that listens on symfony's main events to parse annotations and generate
 * appropriate responses  
 * 
 * Class ActionListener
 * @package Alks\HttpExtraBundle\EventListener
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class ActionListener
{
    /**
     * The doctrine annotation reader instance
     * 
     * @var Reader
     */
    protected $annotationReader;
    /**
     * Collection of request annotations
     * 
     * @var ActionParam[]
     */
    protected $actionParameters;
    /**
     * Collection of response annotations
     * 
     * @var Response[]
     */
    protected $responseParameters;
    /**
     * @var ConfigurationResolver
     */
    private $configuration;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * ActionListener constructor.
     * @param Reader $annotationReader
     * @param ConfigurationResolver $configuration
     */
    public function __construct(Reader $annotationReader, ConfigurationResolver $configuration)
    {
        $this->annotationReader = $annotationReader;
        $this->actionParameters = [];
        $this->responseParameters = [];
        $this->configuration = $configuration;
        $this->serializer = null;
    }

    /**
     * Sets the listener serializer (expected to be called or removed by the di container)
     *
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Method responsible to generate the controller action through reflection. The method will accept only valid 
     * ReflectionMethod related arguments in order to comply with the annotation reader's getMethodAnnotations method.
     * 
     * @param $controller
     * @return \ReflectionFunction|\ReflectionMethod
     */
    protected function generateAction($controller)
    {
        if($controller instanceof \ReflectionMethod)
        {
            return $controller;
        }
        if (is_array($controller))
        {
            return new \ReflectionMethod($controller[0], $controller[1]);
        }
        elseif (is_object($controller) && !$controller instanceof \Closure)
        {
            return (new \ReflectionObject($controller))->getMethod('__invoke');
        }
        throw new \RuntimeException();
    }

    /**
     * @return \Alks\HttpExtraBundle\Annotation\ActionParam[]
     */
    public function getActionParameters()
    {
        return $this->actionParameters;
    }

    /**
     * Method that binds symfony's controller found event
     * 
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        try
        {
            $action = $this->generateAction($event->getController());
        }
        catch(\Exception $e)
        {
            return;
        }
        $annotations = $this->annotationReader->getMethodAnnotations($action);
        foreach($annotations as $annotation)
        {
            if($annotation instanceof RequestParams)
            {
                /** @var RequestParam $param */
                foreach($annotation->getParams() as $param)
                {

                    $this->actionParameters[$param->getBindTo()] = $param;
                }
            }
            if($annotation instanceof ActionParam)
            {
                $this->actionParameters[$annotation->getBindTo()] = $annotation;
            }
            if($annotation instanceof Response)
            {
                $this->responseParameters[] = $annotation;
            }
        }
    }

    /**
     * Binds the kernel response event. Will apply any response annotation references to the Response object
     * 
     * 
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        foreach($this->responseParameters as $parameter)
        {
            if($parameter->getHeaders() !== null)
            {
                foreach($parameter->getHeaders() as $header)
                {
                    $event->getResponse()->headers->add([
                        $header->getName() => $header->getValue()
                    ]);
                }
            }
        }
    }

    /**
     * Binds the kernel view event. Will handle any data that are not Response formats
     * 
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $acceptType = null;
        $context= [];
        if(count($this->responseParameters) > 0)
        {
            $response = $this->responseParameters[0];
            if($response->getType() !== null)
            {
                $acceptType = $this->configuration->getTypeFromKey($response->getType());
                if($acceptType === null)
                {
                    throw new \RuntimeException(sprintf('Response type "%s" cannot be resolved', $response->getType()));
                }
            }
            if($response->getContext() !== null)
            {
                $context = $response->getContext();
            }
        }
        if($acceptType === null)
        {
            $acceptType = $this->configuration->resolveAcceptType($event->getRequest());
        }
        $responseContent = $this->serializer === null ? $event->getControllerResult() : $this->serializer->serialize(
            $event->getControllerResult(), $acceptType->getName(), $context
        );
        $response = new \Symfony\Component\HttpFoundation\Response($responseContent);
        $response->headers->add([
            'Content-Type' => $acceptType->getValue()
        ]);
        $event->setResponse($response);
    }
}