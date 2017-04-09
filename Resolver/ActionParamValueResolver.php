<?php

namespace Alks\HttpExtraBundle\Resolver;

use Alks\HttpExtraBundle\Annotation\ActionParam;
use Alks\HttpExtraBundle\Annotation\Annotation;
use Alks\HttpExtraBundle\Annotation\RequestBody;
use Alks\HttpExtraBundle\Annotation\RequestData;
use Alks\HttpExtraBundle\Annotation\RequestParam;
use Alks\HttpExtraBundle\EventListener\ActionListener;
use Alks\HttpExtraBundle\Negotiation\NegotiationResult;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Main class that will handle the binding of the action parameters.
 *
 * Class ActionParamValueResolver
 * @package Alks\HttpExtraBundle\Resolver
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class ActionParamValueResolver implements ArgumentValueResolverInterface
{
    /**
     * Access to the default extension serializer.
     *
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * Access to the doctrine registry.
     *
     * @var RegistryInterface
     */
    private $doctrine;
    /**
     * Access to the extension configuration parameters.
     *
     * @var ConfigurationResolver
     */
    private $configuration;
    /**
     * The main action listener that would parse the controllers arguments for ActionParam references.
     *
     * @var ActionListener
     */
    private $actionListener;
    /**
     * The content type as negotiated from the request.
     *
     * @var NegotiationResult|null
     */
    protected $contentType;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var DenormalizerInterface|null
     */
    private $denormalizer;

    /**
     * ActionParamValueResolver constructor.
     * @param ConfigurationResolver $configuration
     * @param ActionListener $actionListener
     */
    public function __construct(ConfigurationResolver $configuration, ActionListener $actionListener)
    {
        $this->serializer = null;
        $this->doctrine = null;
        $this->configuration = $configuration;
        $this->actionListener = $actionListener;
        $this->contentType = null;
        $this->validator = null;
        $this->denormalizer = null;
    }

    /**
     * Sets the optional denormalizer service
     *
     * @param DenormalizerInterface $denormalizer
     */
    public function setDenormalizer(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * Sets the optional serializer service
     *
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Sets the optional doctrine registry service
     *
     * @param RegistryInterface $registry
     */
    public function setDoctrineRegistry(RegistryInterface $registry)
    {
        $this->doctrine = $registry;
    }

    /**
     * Sets the optional validator service
     *
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Checks if the resolver can resolve the value with the given metadata
     *
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        if(array_key_exists($argument->getName(),$this->actionListener->getActionParameters()))
        {
            $param = $this->actionListener->getActionParameters()[$argument->getName()];
            if(!$param instanceof ActionParam)
            {
                return false;
            }
            if($param->getRoute() !== null && $request->attributes->has('_route'))
            {
                if($param->getRoute() != $request->attributes->get('_route'))
                {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Returns the possible value(s).
     *
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return \Generator
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $actionParam = $this->actionListener->getActionParameters()[$argument->getName()];
        $resolved = false;
        $value = null;
        if($actionParam instanceof RequestData)
        {
            $value = $this->invokeDataParam($actionParam,$argument,$request,$resolved);
        }
        else if($actionParam instanceof RequestBody)
        {
            if($this->contentType === null)
            {
                if($actionParam->getFormat() !== null)
                {
                    $this->contentType = $this->configuration->getTypeFromKey($actionParam->getFormat());
                    if($this->contentType === null)
                    {
                        throw new \RuntimeException(sprintf('Content type "%s" cannot be resolved',$actionParam->getFormat()));
                    }
                }
                else
                {
                    $this->contentType = $this->configuration->resolveContentType($request);
                }
            }
            $value = $this->invokeRequestBody($actionParam,$argument,$this->contentType,$request,$resolved);
        }
        else if($actionParam instanceof RequestParam)
        {
            $value = $this->invokeQueryParam($actionParam,$argument,$request,$resolved);
        }
        if(!$resolved)
        {
            if($actionParam instanceof Annotation)
            {
                throw new \RuntimeException(sprintf('%s "%s" cannot be resolved. The request does not contain relevant data and the "$%s" argument does not have a default value',get_class($actionParam),$actionParam->getBindTo(),$argument->getName()));
            }
            else
            {
                throw new \RuntimeException(sprintf('%s cannot be resolved',$argument->getName()));
            }
        }
        yield $value;
    }

    /**
     * Resolves the type of a parameter based on the ActionParam reference or the ArgumentMetadata provided.
     *
     * @param ActionParam $param
     * @param ArgumentMetadata $metadata
     * @return null|string
     */
    protected function resolveType(ActionParam $param, ArgumentMetadata $metadata)
    {
        if($param->getType() === null)
        {
            if($metadata->getType() === null)
            {
                return null;
            }
            if(!class_exists($metadata->getType()))
            {
                return null;
            }
            return $metadata->getType();
        }
        else
        {
            if(!class_exists($param->getType()))
            {
                return null;
            }
            return $param->getType();
        }
    }

    /**
     * Checks if a RequestParam has any reference to a doctrine entity and searches for the entity reference using the
     * doctrine registry.
     *
     * @param RequestParam $param
     * @param ArgumentMetadata $metadata
     * @param $value
     * @return array|null|object
     */
    protected function resolveDoctrineEntity(RequestParam $param, ArgumentMetadata $metadata, $value)
    {
        if($this->doctrine === null)
        {
            return null;
        }
        $findBy = $param->getFindBy() === null ? $param->getName() : $param->getFindBy();
        $type = $this->resolveType($param,$metadata);
        if($type === null)
        {
            return null;
        }
        try
        {
            $repository = $param->getManager() === null ?
                $this->doctrine->getRepository($type) : $this->doctrine->getManager($param->getManager())->getRepository($type);
            if($metadata->getType() === 'array')
            {
                return $repository->findBy([
                    $findBy => $value
                ]);
            }
            return $repository->findOneBy([
                $findBy => $value
            ]);
        }
        catch(\Exception $e)
        {
            return null;
        }
    }

    /**
     * Checks if the param/argument can be bound to a default value instead of request data.
     *
     * @param ArgumentMetadata $metadata
     * @param ActionParam $param
     * @param $resolved
     * @return mixed|null|string
     */
    protected function resolveDefaultValue(ArgumentMetadata $metadata, ActionParam $param, &$resolved)
    {
        if($metadata->hasDefaultValue())
        {
            $resolved = true;
            return $metadata->getDefaultValue();
        }
        else if($param->isHasDefaultValue())
        {
            $resolved = true;
            return $param->getDefaultValue();
        }
        $resolved = false;
        return null;
    }

    /**
     * Method responsible for resolving RequestParam references.
     *
     * @param RequestParam $param
     * @param ArgumentMetadata $argumentMetadata
     * @param Request $request
     * @param $resolved
     * @return array|mixed|null|object|string
     */
    protected function invokeQueryParam(RequestParam $param, ArgumentMetadata $argumentMetadata, Request $request, &$resolved)
    {
        $resolved = true;
        $value = $request->query->get($param->getName());
        if($value === null)
        {
            return $this->resolveDefaultValue($argumentMetadata,$param,$resolved);
        }
        if($param->getRepository() !== null || $param->getFindBy() !== null)
        {
            $value = $this->resolveDoctrineEntity($param,$argumentMetadata,$value);
            if($value === null)
            {
                return $this->resolveDefaultValue($argumentMetadata,$param,$resolved);
            }
            return $value;
        }
        return $value;
    }

    /**
     * Method responsible for resolving RequestData references.
     *
     * @param RequestData $data
     * @param ArgumentMetadata $argumentMetadata
     * @param Request $request
     * @param $resolved
     * @return array|mixed|null|object|string
     */
    protected function invokeDataParam(RequestData $data, ArgumentMetadata $argumentMetadata, Request $request, &$resolved)
    {
        $resolved = true;
        if($data->getName() === null)
        {
            if(count($request->request) === 0)
            {
                return $this->resolveDefaultValue($argumentMetadata,$data,$resolved);
            }
            $type = $this->resolveType($data,$argumentMetadata);
            if($type === null)
            {
                return $request->request->all();
            }
            if($this->denormalizer !== null && $this->configuration->isNormalizerEnabled())
            {
                if($this->denormalizer->supportsDenormalization($request->request,$type))
                {
                    try
                    {
                        $value = $this->denormalizer->denormalize($request->request->all(),$type);
                        if(empty($value))
                        {
                            return $this->resolveDefaultValue($argumentMetadata,$data,$resolved);
                        }
                        return $value;
                    }
                    catch (\Exception $e)
                    {
                        return $this->resolveDefaultValue($argumentMetadata,$data,$resolved);
                    }
                }
            }
        }
        else
        {
            $value = $request->request->get($data->getName());
            if($value === null)
            {
                return $this->resolveDefaultValue($argumentMetadata,$data,$resolved);
            }
            return $request->request->get($data->getName());
        }
        return $this->resolveDefaultValue($argumentMetadata,$data,$resolved);
    }

    /**
     * Method responsible for resolving RequestBody references.
     *
     * @param RequestBody $body
     * @param ArgumentMetadata $argument
     * @param NegotiationResult $contentType
     * @param Request $request
     * @param $resolved
     * @return mixed|null|object|resource|string
     */
    protected function invokeRequestBody(RequestBody $body, ArgumentMetadata $argument, NegotiationResult $contentType, Request $request, &$resolved)
    {
        $content = $request->getContent();
        if(empty($content))
        {
            return $this->resolveDefaultValue($argument,$body,$resolved);
        }
        $type = $this->resolveType($body,$argument);
        if($type === null)
        {
            $resolved = true;
            return $content;
        }
        if(!$this->configuration->isSerializerEnabled() || $this->serializer === null)
        {
            $resolved = true;
            return $content;
        }
        try
        {
            $value = $this->serializer->deserialize($request->getContent(),$type,$contentType->getName());
            if(empty($value))
            {
                return $this->resolveDefaultValue($argument,$body,$resolved);
            }
            if($body->shouldValidate() && $this->configuration->isValidatorEnabled())
            {
                if($this->validator !== null)
                {
                    $errors = $this->validator->validate($value);
                    if(count($errors) > 0)
                    {
                        $message = '';
                        /** @var ConstraintViolationInterface $error */
                        foreach($errors as $error)
                        {
                            $parameters = '';
                            foreach($error->getParameters() as $parameter)
                            {
                                $parameters .= $parameter.',';
                            }
                            $parameters = rtrim($parameters.',');
                            $message .= $error->getMessage().','.$parameters.'--'.$error->getPropertyPath();
                        }
                        $message = rtrim($message,',');
                        throw new BadRequestHttpException('Invalid request body. '.$message);
                    }
                }
            }
            $resolved = true;
            return $value;
        }
        catch(\Exception $e)
        {
            if($e instanceof HttpException)
            {
                throw $e;
            }
            return $this->resolveDefaultValue($argument,$body,$resolved);
        }
    }
}