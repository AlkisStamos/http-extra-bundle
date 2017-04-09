<?php

namespace Alks\HttpExtraBundle\EventListener;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class responsible to override the default doctrine param converter in case an ActionParam has been defined
 * for the current configuration.
 *
 * Class EntityParamConverter
 * @package Alks\HttpExtraBundle\EventListener
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class EntityParamConverter implements ParamConverterInterface
{
    /**
     * The main action listener that would parse the controllers arguments for ActionParam references.
     *
     * @var ActionListener
     */
    private $actionListener;

    /**
     * EntityParamConverter constructor.
     * @param ActionListener $actionListener
     */
    public function __construct(ActionListener $actionListener=null)
    {
        $this->actionListener = $actionListener;
    }

    /**
     * Overrides any default param converters in favour of action params. In order to support multiple routing on the
     * same controller method, the apply will test if the ActionParam references are bound to a unique route name in
     * order to run.
     *
     * @param Request $request
     * @param ParamConverter $configuration
     * @return bool
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $param = $this->actionListener->getActionParameters()[$configuration->getName()];
        if($param->getRoute() !== null && $request->attributes->has('_route'))
        {
            if($param->getRoute() != $request->attributes->get('_route'))
            {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks whether an ActionParam has been defined that references the configuration in order for the converter
     * to be applied to the request.
     *
     * @param ParamConverter $configuration
     * @return bool
     */
    public function supports(ParamConverter $configuration)
    {
        return array_key_exists($configuration->getName(),$this->actionListener->getActionParameters());
    }
}