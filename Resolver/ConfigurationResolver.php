<?php

namespace Alks\HttpExtraBundle\Resolver;

use Alks\HttpExtraBundle\Negotiation\NegotiationInterface;
use Alks\HttpExtraBundle\Negotiation\NegotiationResult;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Container service class to supply the extension with configuration values. The service will resolve all configuration
 * to acceptable formats.
 *
 * Class ConfigurationResolver
 * @package Alks\HttpExtraBundle\Resolver
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class ConfigurationResolver
{
    /**
     * Default and fallback values for every negotiation context.
     *
     * @var NegotiationResult[]
     */
    protected $defaults;
    /**
     * Contains the raw extension configuration as defined in the yml/xml configuration.
     *
     * @var array
     */
    protected $raw;
    /**
     * Array of negotiation priorities. Priorities are set as defined in configuration.
     *
     * @var array
     */
    protected $priorities;
    /**
     * Array of parsed configuration. Merges the raw and default config values.
     *
     * @var array
     */
    protected $parsed;
    /**
     * Flag to check if the configuration is initialized.
     *
     * @var boolean
     */
    protected $initialized;
    /**
     * The extensions default negotiator
     *
     * @var NegotiationInterface
     */
    protected $negotiation;
    /**
     * Config option to enable/disable the negotiator
     *
     * @var boolean
     */
    protected $negotiationEnabled;
    /**
     * Config option to enable/disable the serializer
     *
     * @var boolean
     */
    protected $serializerEnabled;
    /**
     * Config option to enable/disable the normalizer
     *
     * @var boolean
     */
    protected $normalizerEnabled;
    /**
     * Config option to enable/disable the validator
     *
     * @var boolean
     */
    protected $validatorEnabled;
    /**
     * Result of the negotiation, default value or annotation property for content type
     *
     * @var NegotiationResult|null
     */
    private $_contentType;
    /**
     * Result of the negotiation, default value or annotation property for accept type
     *
     * @var NegotiationResult|null
     */
    private $_acceptType;

    /**
     * ConfigurationResolver constructor.
     * @param NegotiationInterface $negotiation
     */
    public function __construct(NegotiationInterface $negotiation)
    {
        $this->priorities = self::emptyNegotiationContext();
        $this->parsed = self::emptyNegotiationContext();
        $this->defaults = [];
        $this->initialized = false;
        $this->raw = [];
        $this->negotiation = $negotiation;
        $this->negotiationEnabled = false;
        $this->serializerEnabled = true;
        $this->validatorEnabled = false;
        $this->normalizerEnabled = false;
        $this->_contentType = null;
        $this->_acceptType = null;
    }

    /**
     * In order to provide lazy loading initializing on the configuration values (initialization will happen only on requests
     * that the extension is required) each public method of the resolver must call this method first to verify that configuration
     * values are set correctly.
     */
    protected function initialize()
    {
        if(!$this->initialized)
        {
            $configuration = $this->merge($this->raw,$this->defaultRawConfiguration());
            foreach($configuration['types'] as $type)
            {
                $this->parsed['type'][$type['name']] = $type['values'];
                $this->priorities['type'] = array_merge($this->priorities['type'],$type['values']);
                if(!isset($this->defaults['type']))
                {
                    $this->defaults['type'] = new NegotiationResult($type['name'],$type['values'][0]);
                }
            }
            $this->parsed['headers'] = $configuration['headers'];
            $this->initialized = true;
        }
        return $this;
    }

    /**
     * Merges the defined extension configuration with the default values.
     *
     * @param array $rawConfiguration
     * @param array $defaultConfiguration
     * @return array
     */
    protected function merge(array $rawConfiguration, array $defaultConfiguration)
    {
        foreach($defaultConfiguration as $section=>$configuration)
        {
            if($this->isNegotiationConfiguration($section))
            {
                if(isset($rawConfiguration[$section]))
                {
                    if(count($rawConfiguration[$section]) > 0)
                    {
                        $defaultConfiguration[$section] = $rawConfiguration[$section];
                    }
                }
                $appendSection = 'append_'.$section;
                if(isset($rawConfiguration[$appendSection]))
                {
                    if(count($rawConfiguration[$appendSection]) > 0)
                    {
                        $defaultConfiguration[$section] = $this->mergeSection($rawConfiguration[$appendSection],$defaultConfiguration[$section]);
                    }
                }
            }
            else
            {
                if(isset($rawConfiguration[$section]))
                {
                    if(is_array($rawConfiguration[$section]))
                    {
                        foreach($configuration as $index=>$item)
                        {
                            if(isset($rawConfiguration[$section][$index]))
                            {
                                $defaultConfiguration[$section][$index] = $rawConfiguration[$section][$index];
                            }
                        }
                    }
                    else
                    {
                        $defaultConfiguration[$section] = $rawConfiguration[$section];
                    }
                }
            }
        }
        $this->negotiationEnabled = $defaultConfiguration['negotiation']['enabled'];
        $this->serializerEnabled = $defaultConfiguration['serializer']['enabled'];
        $this->normalizerEnabled = $defaultConfiguration['normalizer']['enabled'];
        $this->validatorEnabled = $defaultConfiguration['validator']['enabled'];
        return $defaultConfiguration;
    }

    /**
     * Merges a raw configuration section with the default values.
     *
     * @param array $raw
     * @param array $default
     * @return array
     */
    protected function mergeSection(array $raw, array $default)
    {
        foreach($raw as $rawItem)
        {
            if($this->isValidNegotiationNode($rawItem))
            {
                $append = true;
                foreach($default as $defaultIndex=>$defaultItem)
                {
                    if($defaultItem['name'] == $rawItem['name'])
                    {
                        $default[$defaultIndex] = $rawItem;
                        $append = false;
                        break;
                    }
                }
                if($append)
                {
                    $default[] = $rawItem;
                }
            }
        }
        return $default;
    }

    /**
     * Checks if a negotiation configuration node is valid.
     *
     * @param $node
     * @return bool
     */
    protected function isValidNegotiationNode($node)
    {
        return isset($node['name']) && isset($node['values']);
    }

    /**
     * Checks if the tag refers to a negotiation label inside the configuration.
     *
     * @param $tag
     * @return bool
     */
    protected function isNegotiationConfiguration($tag)
    {
        return $tag === 'types';
    }

    /**
     * Creates an empty negotiation structure.
     *
     * @return array
     */
    protected static function emptyNegotiationContext()
    {
        return [
            'type' => []
        ];
    }

    /**
     * Provides a default configuration to merge with the extension configuration.
     *
     * @return array
     */
    protected function defaultRawConfiguration()
    {
        return [
            'negotiation' => [
                'enabled' => false,
            ],
            'serializer' => [
                'enabled' => true
            ],
            'normalizer' => [
                'enabled' => false
            ],
            'validator' => [
                'enabled' => false
            ],
            'types' =>
            [
                [
                    'name' => 'json',
                    'values'=> [
                        'application/json','text/json'
                    ],
                    'restrict' => null
                ],
                [
                    'name' => 'xml',
                    'values' => [
                        'application/xml','text/xml'
                    ],
                    'restrict' => null
                ]
            ],
            'headers' =>
            [
                'content_type' => 'content-type',
                'accept_type' => 'accept'
            ]
        ];
    }

    /**
     * Loads the symfony parsed configuration inside the resolver.
     *
     * @param array $configuration
     * @return ConfigurationResolver
     * @throws InvalidTypeException
     */
    public function load(array $configuration)
    {
        $this->raw = $configuration;
        return $this;
    }

    /**
     * Generates a NegotiationResult based on the values provided and the configuration options.
     *
     * @param $value
     * @param $context
     * @return NegotiationResult|null
     */
    protected function resolveNegotiatedValue($value, $context)
    {
        $this->initialize();
        if($value === null)
        {
            return $this->defaults[$context];
        }
        foreach($this->parsed[$context] as $name=>$types)
        {
            foreach($types as $type)
            {
                if($value === $type)
                {
                    return new NegotiationResult($name,$type);
                }
            }
        }
        return null;
    }

    /**
     * Returns a resolved header. The method accepts dashes and underscores as well.
     *
     * @param $header
     * @return string
     */
    protected function getHeader($header)
    {
        $this->initialize();
        $header = str_replace('-','_',$header);
        return isset($this->parsed['headers'][$header]) ? $this->parsed['headers'][$header] : null;
    }

    /**
     * Returns the content type of the request based on the configuration and the header values
     *
     * @param Request $request
     * @return NegotiationResult
     */
    public function resolveContentType(Request $request)
    {
        $this->initialize();
        if($this->_contentType !== null)
        {
            return $this->_contentType;
        }
        if($this->negotiationEnabled)
        {
            $this->_contentType = $this->resolveNegotiatedValue(
                $this->negotiation->negotiateType(
                    is_array($this->priorities['type']) ? $this->priorities['type'] : [],
                    $request->headers->get($this->getHeader('content-type'))
                ),
                'type'
            );
            if($this->_contentType !== null)
            {
                return $this->_contentType;
            }
        }
        return $this->defaults['type'];
    }

    /**
     * Resolves the type of the response based on the configuration and the header values
     *
     * @param Request $request
     * @return NegotiationResult
     */
    public function resolveAcceptType(Request $request)
    {
        $this->initialize();
        if($this->_acceptType !== null)
        {
            return $this->_acceptType;
        }
        if($this->negotiationEnabled)
        {
            $this->_acceptType = $this->resolveNegotiatedValue(
                $this->negotiation->negotiateType(
                    is_array($this->priorities['type']) ? $this->priorities['type'] : [],
                    $request->headers->get($this->getHeader('accept-type'))
                ),
                'type'
            );
            if($this->_acceptType !== null)
            {
                return $this->_acceptType;
            }
        }
        return $this->defaults['type'];
    }

    /**
     * Returns the NegotiationResult the corresponds to the type key (eg 'json')
     *
     * @param $key
     * @param $priority
     * @return NegotiationResult|null
     */
    public function getTypeFromKey($key, $priority=0)
    {
        $this->initialize();
        if(isset($this->parsed['type'][$key]))
        {
            return new NegotiationResult($key,$this->parsed['type'][$key][$priority]);
        }
        return null;
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getAcceptType(Request $request)
    {
        return $this->resolveAcceptType($request)->getName();
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getContentType(Request $request)
    {
        return $this->resolveContentType($request)->getName();
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        $this->initialize();
        return $this->parsed;
    }

    /**
     * Returns the serializer-enabled config option
     *
     * @return bool
     */
    public function isSerializerEnabled()
    {
        $this->initialize();
        return $this->serializerEnabled;
    }

    /**
     * Returns the normalizer-enabled config option
     *
     * @return bool
     */
    public function isNormalizerEnabled()
    {
        $this->initialize();
        return $this->normalizerEnabled;
    }

    /**
     * Returns the validator-enabled config option
     *
     * @return bool
     */
    public function isValidatorEnabled()
    {
        $this->initialize();
        return $this->validatorEnabled;
    }
}